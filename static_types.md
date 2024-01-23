# Статическая типизация

Одним предложением — [статическая типизация](https://ru.wikipedia.org/wiki/Статическая_типизация) 
делает приложение сложнее, но надежнее.

В `php` статической типизации нет, но у нас есть хорошие `IDE`, которые сразу подсказывают, если мы передаем в метод неправильный тип, 
или если неправильно применяем полученный из метода тип. 

Необходимо и достаточно, чтобы мы объявляли строго типизированные сигнатуры методов, 
тщательно объявляли все атрибуты и их типы.

```php
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property string $name
 * @property string $email
 * @property null|Carbon $email_verified_at 
 */
class User extends Model
{
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
```

Например — модель и её фабрика:

```php
namespace App\Models;
 
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static UserFactory factory($count = null, $state = [])
 */
class User extends Model
{
    use HasFactory;
}
```

```php
namespace Database\Factories;
 
use App\Models\User;

/**
 * @method User create($attributes = [], ?Model $parent = null)
 * @method User make($attributes = [], ?Model $parent = null)
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
        ];
    }
    
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
```

Благодаря такой разметке наш `IDE` сразу будет знать, что результат работы

```php    
User::factory()->unverified()->make();
```

— это экземпляр класса `User`.
