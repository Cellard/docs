# Паттерн Data Transfer Object

Паттерн [DTO](https://ru.wikipedia.org/wiki/DTO) позволит нам пробросить надежный мост
от запроса до репозитория, до построителя запросов, до Laravel Scout — куда угодно.
При таком подходе мы никак не ограничиваем фактический пейлоад запроса и его валидатор 
— это, по большому счету, остается на вкус фронтэнд разработчика. Смотрите примеры:

```php
namespace App\Dto;

interface UserData 
{
    public function getName(): string;
    public function getEmail(): string;
    public function getEnabled(): bool;
    public function getRole(): ?RoleEnum;
    public function getBirthday(): ?Carbon;
}
```

Теперь применим этот контракт к запросу на добавление/обновление пользователя:

```php
namespace App\Http\Requests;

use App\Dto\UserData;

class UserRequest extends FormRequest implements UserData
{
    public function getName(): string
    {
        return $this->input('name');
    }
    
    public function getEmail(): string
    {
        return $this->input('email');
    }
    
    public function getEnabled(): bool
    {
        return $this->boolean('enabled');
    }
    
    public function getRole(): ?RoleEnum
    {
        return $this->enum('role', RoleEnum::class);
    }
    
    public function getBirthday(): ?Carbon
    {
        return $this->date('birthday');
    }
    
    public function rules(): array 
    {
        return [
            'name'     => 'required',
            'email'    => 'required|unique:users,email',
            'enabled'  => 'required|boolean',
            'birthday' => 'date',
            'role'     => Rule::enum(RoleEnum::class),
        ];   
    }
}
```

> Тут справедливо будет отметить, 
> что DTO не должен обладать собственной бизнес-логикой. 
> И совмещение функций запроса и DTO в одном классе — не по канону.
> Но я пока ничего дурного в этом не нашел.
> Если не хотите портить себе карму, можете реализовать в запросе метод `dto(): UserData`
> и пользоваться его результатами. Вот это будет по канону.

Как можно увидеть, наш запрос стал очень строго типизирован. 
Если какие-то данные не будут соответствовать контракту, приложение упадет с ошибкой еще до попытки 
записать неполные или некорректные данные в базу данных!

Теперь доработаем [репозиторий](repository_pattern.md):

```php
namespace App\Actions;

class HandlesUsers {
    
    public function update(User $user, UserData $dto): User 
    {
        $user->name = $dto->getName();
        $user->email = $dto->getEmail();
        $user->enabled = $dto->getEnabled();
        $user->role = $dto->getRole();
        $user->birthday = $dto->getBirthday();
        
        $user->save();
        
        return $user;
    }
    
    public function create(UserData $dto): User 
    {
        return $this->update(new User, $dto);
    }
}
```

Да, и что касается тестирования — пожалуйста, сколько угодно. 
Создайте класс соответствующий контракту, 
наполните его фейковыми данными и передайте в репозиторий. 

## Фильтрация и поиск

Аналогичным образом паттерн `DTO` можно применить 
для использования запроса непосредственно в Builder.

Объявим контракт.

```php
namespace App\Dto;

interface FilterUsers 
{
    public function filterByName(): ?string;
    public function filterByEmail(): ?string;
    public function filterByRole(): ?RoleEnum;
}
```

Напишем фильтрацию.

```php
namespace App\Builders;

class UserBuilder extends Builder {
    
    public function filterBy(FilterUsers $dto): static 
    {
        return $this
            ->when($dto->filterByName(), fn(self $builder, string $name) => $builder
                ->where('name', 'like', "%$name%"))
            ->when($dto->filterByEmail(), fn(self $builder, string $email) => $builder
                ->where('email', 'like', "$email%"))
            ->when($dto->filterByRole(), fn(self $builder, RoleEnum $role) => $builder
                ->where('role', $role));
    } 
}
```

И, после того, как мы применим контракт `FilterUsers` к нашему `IndexRequest`, сможем использовать его в контроллере:

```php
namespace App\Http\Controllers;

class UserController extends Controller
{   
    public function index(IndexRequest $request) 
    {
        return User::query()
            ->filterBy($request)
            ->paginate();
    }
}
```

## В заключение

Опасайтесь оперировать массивами. Они никак не афишируют свою внутреннюю структуру. Они могут выстрелить вам в ногу.

Безопасный массив — это массив, который не выходит за пределы метода; в крайнем случае — за пределы класса.
«Для внутреннего использования», что называется. 

Если вам необходимо передать массив из одного класса в другой класс или, 
что самое страшное, передать пользовательские данные в какой-то класс — всегда используйте паттерн `DTO`.
Это займет какое-то время, но только один раз. Зато вы будете уверены в данных, которые получили.
