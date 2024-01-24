# Local Scopes

К [Local Scopes](https://laravel.com/docs/10.x/eloquent#local-scopes)
я отношусь резко отрицательно по следующим причинам:

* Без дополнительных phpdoc-инструкций этим методы не распознаются `IDE`.
* Упомянутые phpdoc-инструкции на самом деле — костыли; они объявляют методы, доступные только магически.
* Мы не можем средствами `IDE` перейти от реализации к использованию или от использования к реализации.
* Мы засоряем модель, хотя она должна отвечать только за свойства и связи.

Вместо Local Scope следует использовать Custom Builders — 
по аналогии с [Custom Collections](https://laravel.com/docs/10.x/eloquent-collections#custom-collections).

Используем команды для создания классов:

    composer require codewiser/laravel-make

    php artisan make:collection UserCollection
    php artisan make:builder UserBuilder

Это создаст класс коллекции (с типизированным phpdoc-описанием):

```php
namespace App\Collection;

/**
 * @method User first(callable $callback = null, $default = null)
 * 
 * etc
 */
class UserCollection extends Collection {
    
    public function customMethod(): static 
    {
        return $this->filter(...);
    } 
}
```

И билдера (с типизированным phpdoc-описанием):

```php
namespace App\Builders;

/**
 * @method User first($columns = ['*'])
 * @method UserCollection get($columns = ['*'])
 * 
 * etc
 */
class UserBuilder extends Builder {
    
    public function customMethod(): static 
    {
        return $this->where(...);
    } 
}
```

Вручную добавим в модель методы и объявим их статические аксессоры в phpdoc:

```php
namespace App\Models;
 
use App\Builders\UserBuilder;
use App\Collections\UserCollection;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static UserBuilder query()
 * @method static UserCollection all($columns = ['*'])
 */
class User extends Model
{
    /**
     * Create a new Eloquent Collection instance.
     */
    public function newCollection(array $models = []): UserCollection
    {
        return new UserCollection($models);
    }
    
    /**
     * Create a new Eloquent Builder instance.
     */
    public function newEloquentBuilder($query): UserBuilder
    {
        return new UserBuilder($query);
    }
}
```

При обращении к методу `User::query()` наш `IDE` будет подсказывать методы,
которые объявлены в классе `UserBuilder`. 
Который, в свою очередь, содержит методы получения экземпляров `User` 
и коллекций `UserCollection`.

Наш `IDE` всё видит, всё понимает, всё подсказывает:

```php
$user = User::query()
    ->find(1); // IDE понимает, что $user — это экземпляр User

echo $user->email; // IDE подскажет свойства и методы

$users = User::query()
    ->customMethod()
    ->get(); // IDE понимает, что $users — это экземпляр UserCollection

$users // IDE подскажет методы
    ->customMethod()
    ->each(function (User $user) {
        ...
    });
```

## Relations

С учетом вышесказанного аналогичным образом 
мы будем размечать и отношения между моделями.

```php
namespace App\Models;

use App\Builders\PostBuilder;
use App\Collections\PostCollection;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read PostCollection|Post[] $posts User's posts.
 */
class User extends Model
{
    /**
     * User writes some posts.
     */
    public function posts(): HasMany|PostBuilder
    {
        return $this->hasMany(Post::class);
    }
}
```

Теперь при обращении к методу `$user->posts()` наш `IDE` будет подсказывать методы, 
которые объявлены и в классе `HasMany`, и в классе `PostBuilder`. 
А при обращении к свойству `$user->posts` — методы, объявленные в классе `PostCollection`.

## Паттерн Repository

Паттерн Repository используется как прослойка между системой хранения и системой доступа.

Он находится между контроллером и моделью.

По многим признакам `Custom Builder` похож на данный паттерн — по крайней мере, в части чтения данных.

Вот пример:

```php
namespace App\Builders;

class UserBuilder extends Builder {
    
    /**
     * Возвращает список пользователей для отображения в админке.
     */
    public function listForDashboard(): static 
    {
        return $this
            ->with('posts')
            ->withCount('posts')
            ->orderBy('name');
    } 
}
```

```php
namespace App\Http\Controllers;

class UserController extends Controller
{    
    public function index(IndexRequest $request) 
    {
        return User::query()
            ->listForDashboard()
            ->paginate();
    }
}
```

Смысл в том, чтобы в контроллере мы не писали никакой бизнес-логики, 
не упоминали имен атрибутов и связей — 
всё это должно быть в репозитории, то есть в нашем `Custom Builder`.

## В заключение

Я строго рекомендую всегда объявлять и использовать Custom Collections и Builders — 
даже если вы не планируете добавление собственных методов. 
Достаточно уже того, что `IDE` сразу будет понимать какую именно модель он получает в ответ.

А если у вас будут собственные методы (а они будут), то удобство становится еще более явным. 
И ошибку написать будет сложнее — `IDE` сразу покажет, если идет обращение к «невидимому» методу.

И последнее, но важное — такой `Custom Builder` может вполне полноценно играть роль репозитория.