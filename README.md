# События
Специализированная реализация очереди событий. 

```php
Event::handler('onshow', function(){
	echo 'onshow';
});
Event::fire('onshow');
```

```php
Event::handler('print', function(){
	echo 'World';
}, ':somekey');
Event::handler('print', function(){
	echo 'Hello ';
},'somekey');
Event::fire('print');
```

```js
Event.handler('print', function(){
	console.log('World');
}, ':somekey');
Event.handler('print', function(){
	console.log('Hello ');
},'somekey');
Event.fire('print');
```
Наличие события c Hello не обязательно для того чтобы сработала подписка с World, но если такой handler есть, то World сработает вторым, после обработчика с клчём somekey.

Если все подписки имеют ключи и нет ниодного выполненного ключа, то будет выполнена первая подписка в очереди.

# Infrajs

При генерации событий Event все [конфиги](https://github.com/infrajs/config) установленных или необходимых модулей должны быть обработаны. Чтобы были собраны все подписки.
```php
	\infrajs\config\Config::get();
``` 