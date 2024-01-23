# Контракты

Контракты я люблю, иногда в ущерб здравому смыслу. 
Например, я могу объявить контракт без методов, 
только с объявленными свойствами `created_at` и `updated_at`,
и присвоить его всем моделям — только для того, 
чтобы можно было не объявлять эти свойства у каждой из моделей.
Архитектурной пользы от такого контракта конечно нет.

Но вообще, контракты это очень круто. Пусть, например, в нашем приложении
всё можно комментировать. Комментарии, соответственно, полиморф.

Объявим контракт:

```php
namespace App\Models\Contracts;

/**
 * Model has comments.
 * 
 * @property-read CommentCollection|Comment[] $comments 
 */
interface Commentable 
{
    public function comments(): MorphMany|CommentBuilder;
}
```

Применим этот контракт ко всем моделям, которые можно комментировать (можно и trait сделать).

Кстати, если нам вдруг понадобится какое-то общее поведение для всех `Builder`
моделей с комментариями, мы можем придумать контракт `CommentableBuilder`.

Ну, то есть: `PostBuilder implements CommentableBuilder`,
`ArticleBuilder implements CommentableBuilder` и так далее.
Но это, как правило, совершенно избыточно.

Комментарии, в свою очередь, будут выглядеть так:

```php
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $text
 * 
 * @property-read Model&Commentable $commentable 
 */
class Comment extends Model
{
    public function commentable(): MorphTo|CommentableBuilder
    {
        return $this->morphTo();
    }
}
```

Таким образом, в отношении `комментируемое-комментарий`, мы выделили главное:
владельцем комментария является `Commentable`, а у `Commentable` есть комментарии.
Иных знаний для работы с комментариями нам не надо.

Теперь мы можем написать репозиторий:

```php
namespace App\Actions;

class HandlesComments {
    
    public function index(Commentable $commentable): CommentBuilder 
    {
        return $commentable->comments()->latest();
    }
    
    public function update(Comment $comment, CommentData $dto): Comment 
    {
        $comment->text = $dto->getText();
        
        $comment->save();
        
        return $comment;
    }
    
    public function create(Commentable $commentable, CommentData $dto): Comment 
    {
        $comment = new Comment;
        
        $comment->commentable()->associate($commentable);
        $comment->author()->associate($dto->getAuthor());
    
        return $this->update($comment, $dto);
    }
}
```

Хорошо видно, что для работы с комментариями нам достаточно нашего контракта.
И, если в будущем в приложении появятся новые `Commentable` модели, 
нам не придется переделывать репозиторий.  

Ну и контроллер:

```php
namespace App\Http\Controllers;

abstract class CommentsController
{
    public function __construct(public HandlesComments $comments) 
    {
        //
    }
    
    abstract public function commentable(int $id): Commentable;
    
    public function index(int $id) 
    {
        return $this->comments
            ->index($this->commentable($id))
            ->paginate();
    }
    
    public function store(int $id, CommentRequest $request) 
    {
        return $this->comments
            ->create($this->commentable($id), $request);
    }
    
    public function update(Comment $comment, CommentRequest $request) 
    {
        return $this->comments
            ->update($comment, $request);
    }
}
```

И наследуем этот контроллер сколько нужно:

```php
namespace App\Http\Controllers;

class PostCommentsController extends CommentsController
{
    public function commentable(int $id): Commentable
    {
        return Post::query()->findOrFail($id);
    }
}
```

```php
namespace App\Http\Controllers;

class ArticleCommentsController extends CommentsController
{
    public function commentable(int $id): Commentable
    {
        return Article::query()->findOrFail($id);
    }
}
```

То же самое можно и с помощью `trait` сделать, это уже дело вкуса.

## В заключение

Контракты позволяют писать репозитории, билдеры, коллекции, контроллеры и др.,
которые содержат в себе только минимально необходимую ответственность.
Позволяют переиспользовать код, сохраняя строгую типизацию.

Такая реализация проста и понятна. Для сложных проектов самое то. 
Полный ништяк и каеф, пацаны, отвечаю.