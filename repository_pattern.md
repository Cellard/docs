# Паттерн Repository

Штука хорошая.

Выше, в самом конце главы про [Custom Builders](local_scopes.md), 
я предположил, что Custom Builder может с успехом заменить репозиторий 
— но это касается только получения данных.

Для добавления и изменения записей используем традиционный подход:

> Далее я камня на камне не оставлю от реализации этих методов, но архитектурно — всё корректно.

```php
namespace App\Actions;

class HandlesUsers {
    
    public function update(User $user, array $data): User 
    {
        $user->update($data);
        
        return $user;
    }
    
    public function create(array $data): User 
    {
        return $this->update(new User, $data);
    }
}
```

Благодаря Dependency Injection мы с легкостью используем репозиторий из контроллеров:

```php
namespace App\Http\Controllers;

class UserController
{
    public function __construct(public HandlesUsers $users) {
        //
    }
    
    public function store(UserRequest $request) 
    {
        return $this->users->create($request->all());
    }
    
    public function update(UserRequest $request, User $user) 
    {
        return $this->users->update($user, $request->all());
    }
}
```

Следующий шаг — добавим нашему репозиторию строгости, применив паттерн [DTO](dto.md).