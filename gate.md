# Выносим policy на свет божий

Ниже представлена попытка по максимуму достать `Laravel Policy` из под фасада `Gate`.
[Реализация тут](gate.php).

```php
gate([UserPolicy::class, 'viewAny']);

gate([PostPolicy::class, 'view'], $post);

gate([CommentPolicy::class, 'create'], $post);
```

Что тут примечательно.

Идет явное указание метода политики (массив — это callable). 
`IDE` понимает это и позволяет сделать переход к реализации. И наоборот,
от реализации к использованию. Какая-никакая, но связность.

Сигнатура функции `gate` один в один повторяет сигнатуру `call_user_func`,
и в этом смысле она соответствует опыту программиста.

Функция `gate` возвращает объект `Response`, 
который содержит все необходимые свойства и методы.

## Радикальный подход

Если вообще отказаться от использования `middleware` `can` и `cannot`
(а я бы и не рекомендовал их использование; лучше проверять авторизацию в контроллерах),
и отказаться от авторизации ресурсов целиком в пользу авторизации отдельных методов,
то тогда можно просто полностью отказаться от существующей парадигмы `Policy`.

В самом деле, чтобы авторизовать то или иное действие (или проверить доступ к ресурсу) 
— нам нужен объект `Response`, и ничего больше. А уж откуда именно он взялся — не так и важно.

Ну например, возьмем и напишем политику прямо в модель:

```php
namespace App\Models;
 
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    public function viewPolicy(): Response
    {
        return auth()->user() ?
            Response::allow() :
            Response::deny();
    }
    
    public function updatePolicy(): Response
    {
        return $this->authror->is(auth()->user()) ?
            Response::allow() :
            Response::deny();
    }
}
```

И используем её для авторизации метода:

```php
namespace App\Http\Controller;

class PostController extends Controller
{
    public function view(Post $post) 
    {
        $post->viewPolicy()->authorize();
        
        // etc
    }
    
    public function update(Post $post) 
    {
        $post->updatePolicy()->authorize();
        
        // etc
    }
}
```

Работает? Да.

Но я так не делаю. Слишком непривычно. Ссыкотно.