# События
Спеыиализированная реализация простой очереди событий. 

```php
Event::handler('onshow', function(){
	echo 'onshow';
});
Event::fire('onshow');

Event::handler('print:somekey', function(){
	echo 'World';
});
Event::handler('print', function(){
	echo 'Hello ';
},'somekey');
Event::fire('print');

```
Наличие события c Hello не обязательно для того чтобы сработала подписка с Wrold, но если такой handler есть, то World сработает вторым, после обработчика с клчём 'somekey' благодоря строчки :somekey.