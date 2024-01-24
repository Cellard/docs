# Паттерн Repository

Штука хорошая.

Выше, в конце главы про [Custom Builders](local_scopes.md), 
я предположил, что `Custom Builder` может с успехом заменить репозиторий 
— но это касается только получения данных.

Для добавления и изменения записей используем традиционный подход:

> В главе про [DTO](dto.md) я изменю реализацию этих методов, но архитектурно — тут всё как надо.

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

Благодаря Dependency Injection мы с легкостью используем репозиторий в контроллере:

```php
namespace App\Http\Controllers;

class UserController extends Controller
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