# Статическая типизация

Одним предложением — [статическая типизация](https://ru.wikipedia.org/wiki/Статическая_типизация) 
делает приложение сложнее в разработке, но надежнее в эксплуатации.

В `php` статической типизации нет, но у нас есть хорошие `IDE`, 
которые сразу подсказывают, если мы передаем в метод неправильный тип, 
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
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
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

— это экземпляр класса `User`. А в процессе набора кода — будет подсказывать
возможные методы. Шанс написать ошибку — снижается. 
Помнить наизусть названия методов — не требуется.
Можно делать безопасный рефакторинг; например, переименовать метод `unverified()`.
Можно легко перейти от объявления метода `factory()` к местам его вызова.
И так далее. 

## В заключение

Внедрение строгой типизации требует времени. 
Но в итоге, строгая типизация и высокая связность кода 
повышают качество приложения и скорость его разработки (как ни парадоксально).

Стоит привыкнуть, и получается гораздо быстрее один раз хорошенько описывать
сигнатуры, чем каждый день тратить время на полуслепую навигацию 
по плохо структурированному и описанному коду. 
