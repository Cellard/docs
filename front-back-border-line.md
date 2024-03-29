# Где проходит граница между back и front?

Back делает бизнес-логику, front делает красиво.

Задача back — принять, обработать и сохранить данные, которые ему присылает front, 
и, второе, отдать сохраненные данные, когда front попросит.

Для этого у back есть `Request`, `Controller` и `Resource`.

## Request

Это первая точка, где встречаются интересы front и back.

С одной стороны, `Request` описывает набор полей, который приходит от front, и правила их валидации.
Со второй стороны, `Request` соответствует `DTO`, используемому внутри приложения.

С точки зрения back значение тут имеет только соответствие контракту `DTO`. 
А как именно будут называться поля в запросе — остается на совести front.

Frontend разработчик имеет право изменить имена полей запроса, но не имеет права нарушать контракт `DTO`.

## Resource

Это вторая точка, где встречаются интересы front и back.

С одной стороны, `Resource` оперирует свойствами и отношениями модели.
Со второй стороны, `Resource` описывает набор полей, которые уходят на front.

С точки зрения back значение тут имеет только соответствие свойствам модели.
А как именно будут называться поля в ответе — остается на совести front.

Frontend разработчик имеет право изменить имена полей ответа, но не может выходить за пределы модели и её отношений.

## Route

Это третья точка, где встречаются интересы front и back.

С одной стороны, `Route` указывает, какой контроллер и метод будут обрабатывать этот запрос.
Со второй стороны, `Route` описывает http-адрес для этого метода.

С точки зрения back значение тут имеет только вызов метода контроллера.
А как именно будет выглядеть адрес запроса — остается на совести front.

Frontend разработчик имеет право изменить адрес запроса, но должен использовать контроллеры, предоставляемые back.

## В заключение

Такое разграничение — не более чем джентльменское соглашение. Но опыт использования показал удобство такого подхода.
