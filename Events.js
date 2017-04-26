if(!window.infra) infra = {};
( function () {
	window.Events = {
		list:{},
		classes:{},
		explode: function (separator, str, limit)
		{
		    var arr = str.split(separator);
		    if (limit && arr.length>1) arr.push( arr.splice(limit-1).join(separator) );
		    return arr;
		},
		/**
		 * У одного имени есть два списка. глобальное событие и события alien других объектов 
		 * Если событию obj не передан и нет точки в имени события то это глобальное событие
		 **/
		getList: function (name) 
		{
			var list=this.list;
			if (!list[name]) {
				list[name] = {
					'data':{}, //Массив с данными по объектам
					'result':{}, //Выполнено событие или нет
					'list':[], //Очередь
					'keys':{}, //Ключи всех подписок с количеством
					'readyobj':{},//Массив с временными отметками по объектам что выполнено. При равенстве количество с keys. Ключ попадает в массив ready
					'readykeys':{} //Выполненные ключи
				};
			}
			return list[name];
		},
		createFire: function (name, obj) 
		{
			var fire=this.createContext(name, obj);
			fire.data=fire.list['data'][fire['objid']];
			fire.data.fire=fire; //У data fire Один
			return fire;
		},
		createHandler: function (name, callback, key, obj) 
		{
			var handler=this.createContext(name, obj, key);
			handler['callback'] = callback;
			
			handler.list['list'].push(handler);
			if (handler['key']) {
				if (!handler.list['keys'][handler['key']]) handler.list['keys'][handler['key']] = [handler];
				else handler.list['keys'][handler['key']].push(handler); //Сосчитали все ключи
			}

			return handler;
		},
		createContext: function (name, obj, key)
		{
			if (!key) key = '';
			var p = this.explode(':', key, 2);
			var key = p[0];

			if (p.length==2) {
				var keys = this.explode(',', p[1]);
			} else {
				var keys = [];
			}

			var p=this.explode('.', name);
			if (p.length>1) {
				var cls=p[0];
			} else {
				cls = '';
				if (obj) throw 'Для события с объектом класс обязателен';
			}
			if (cls) { 
				//одноимённый с классом ключ есть всегда. 
				//Вроде того что у класса есть свои собственные обработчики котоорые должны сработать первыми.
				//Если в качестве ключе указать название класса этот обработчик всегда будет первым
				keys.push(cls);
			}
			/*if (cls == 'Controller') {
				cls = 'Infrajs';
				p[0] = cls;
				name = p.join('.');
			} else if (cls == 'Layer') {
				cls = 'layer';
				p[0] = cls;
				name = p.join('.');
			}*/

			var classes = this.classes;
			var objid = obj ? classes[cls](obj) : '';
			var list=this.getList(name);
			list['class']=cls;
			var context= {
				'key' : key,
				'executed' : {},
				'name': name,
				'class': cls,
				'keys': keys,
				'obj': obj,
				'objid': objid,
				'list':list
			};
			if (!list['data'][context['objid']]) list['data'][context['objid']]={};
			return context;
		},
		is: function(r){
			return typeof(r) == 'undefined' || r;
		},
		//wait when listen
		one: function(name, callback, key, obj)
		{
			var ready=false;
			this.handler(name, function(){
				if (ready) return;
				ready=true;
				return callback();
			}, key, obj);
		},
		onext: function(name, callback, key, obj)
		{
			var ready=false;
			this.createHandler(name, function(){
				if (ready) return;
				ready=true;
				return callback();
			}, key, obj);
		},
		handler: function (name, callback, key, obj)
		{
			var handler = this.createHandler(name, callback, key, obj);
			Events.keystik(handler);

			if (obj) { 
				if (handler.list['result'][handler['objid']]) {
					//Метка result появляется когда очередь уже выполнена иначе событие выполнится в общем порядке
					//Подписка на совершённое событие 	
					var r = callback(obj); //Подписка на конкретный объект
					if (!this.is(r)) handler.list['result'][objid] = false;
				}
			} else { //Подписка на все объекты
				for (var objid in handler.list['result']) { //срабатывает для уже обработанных объектов
					if (!handler.list['result'][objid]) continue; //Для прерванных false результатов не запускаем
					var fire=handler.list['data'][objid]['fire'];
					var r = callback(fire['obj']);
					if (!this.is(r)) handler.list['result'][objid] = false;
				}
			}
		},
		clear: function (name) {
			this.tik(name, true);
		},
		tik: function (name, clear)
		{
			/**
			 * Режим повторения, сбросить что есть и начать заного.
			 * Передаётся класс или имя
			 * clear true - удалить и всех подписчиков
			 **/
			if (!name) {
				for (name in this.list) {
					this.tik(name, clear);
				}
				return;
			}
			if (this.list[name]) {
				var list = this.list[name];
				for (var i=0, l=list['list'].length; i < l; i++) {
					var handler=list['list'][i];
					handler['executed']={};
				}
				list['data']={};
				list['result']={}; //Выполнено событие или нет
				list['readyobj']={};//Массив с временными отметками по объектам что выполнено. При равенстве количество с keys. Ключ попадает в массив ready
				list['readykeys']={}; //Выполненные ключи
				if (clear) {
					list['keys'] = {};
					list['list'] = [];
				}
			} else {
				for(var i in this.list) {
					if (this.list[i]["class"] != name) continue;
					this.tik(i, clear);
				}
			}
		},
		fire: function (name, obj)
		{
			/**
			 * Уникальность очереди событий определяется именем события содержащей имя класса события.
			 * Все подписки хранятся в классе и объект не меняется
			 **/


			var fire = this.createFire(name, obj);
			var list=fire.list;
			var data=fire.data;
			/**
			 * TODO: Реализация is isshow... нужно сбрасывать события
			 **/
			if (typeof(list['result'][fire['objid']]) != 'undefined') return list['result'][fire['objid']];
			if (data.executed === false) return true; //Защита от рекурсий вложенный вызов вернёт true
			data.executed=false;
			
			

			if (!list['readykeys'][fire['objid']]) list['readykeys'][fire['objid']]=[];
			if (!list['readyobj'][fire['objid']]) list['readyobj'][fire['objid']]={};
			
			
			// TODO: проверить обработку несуществующих ключей
			for (var i = 0, l = list['list'].length;  i < l; i++) { //Подписка на ходу
				handler = list['list'][i];
				Events.keystik(handler);
			}

			var r = this.execute(fire, list);
			if (this.is(r)) r = true;
			else r = false;
			list['result'][fire['objid']] = r;
			data.executed=true;
			return list['result'][fire['objid']];
		},
		keystik: function(handler){
			handler['keystik']=handler['keys'].filter(function(n) { //Из условия подписчика убраны несуществующие ключи
				if (!handler.list['keys'][n]) return false;
				if (!handler.list['keys'][n].length) return false;
				return true;
			});
		},
		execute: function(fire, list)
		{
			var omit = false;

			for (var i = 0; i < list['list'].length; i++) { //Подписка на ходу возможна length изменится
				var handler=list['list'][i];
				
				
				//Для каждого объекта метка о выполнении текущего обработчика
				if (handler['executed'][fire['objid']]) continue; 
				
				if(handler['obj'] && handler['objid'] !== fire['objid'] ) {
					continue;
				} else {
					//Объект у подписки не указан
					//fire с объктом, подписка без объекта
					//подписка должна выполниться для всех объектов fire. Проходим дальше
				}

				var iskeys = handler['keystik'].filter( function (n) { //Массив невыполненных ключей
					return list['readykeys'][fire['objid']].indexOf(n) === -1; //Проверили выполнены ли все существующие ключи
				});
				if (iskeys.length && (iskeys.length!=1 || !handler['key'] || handler['keystik'].indexOf(handler['key']) === -1)) { // Подписка key:key сработает так как сама она удовлетворяет ключ key
					omit = {
						'keys': iskeys,
						'fire': fire,	
						'handler': handler
					};
					continue; //Найден неудовлетворённый ключ.. может быть выход из цикла и на ислкючение
				}
				
				if (!list['readyobj'][fire['objid']][handler['key']]) list['readyobj'][fire['objid']][handler['key']] = 0;
				list['readyobj'][fire['objid']][handler['key']]++;

				if (handler['key']) {//Если есть ключ
					if (list['readykeys'][fire['objid']].indexOf(handler['key']) === -1) { //И этого ключа ещё не было

						//Если выполнено по объекту столько же сколько всего обработчиков
						if (list['readyobj'][fire['objid']][handler['key']] === list['keys'][handler['key']].length) {
							list['readykeys'][fire['objid']].push(handler['key']);
						}
					}
				}
				

				handler['executed'][fire['objid']] = true;
				var moment = {fire:fire,handler:handler,parent:this.moment,list:list, i:i};
				this.moment=moment;
				var r = handler.callback(fire['obj']);
				this.moment=moment.parent;

				if (!this.is(r)) return r;

				r=this.execute(fire, list);
				return r;

			}
			if (omit) {
				window.omit=omit;
				console.error(omit);
				console.log('Событие', fire['name']);
				console.log('Идентификатор объекта', fire['objid'], fire['obj']);
				console.log('Подписчиков', fire.list['list'].length, fire.list['list']);
				console.log('Выполненное количество ключей у объекта. Почти выполненный ключи подписчиков', list['readyobj'][fire['objid']]);
				console.log('Нужное к выполнению количество ключей у объекта', list['keys']);

				console.log('Уже полностью выполненные ключи в нужном колчичестве', list['readykeys'][fire['objid']]);

				throw 'Events: рекурсивная зависимость подписчиков. '+omit['keys'].join(',')+' зависит от '+omit.handler['key']+ ' и наоборот. window.omit.handler.list.keys';
			}
		}
	}
	if(!window.Event) window.Event = {};
	for (var i in window.Events) {
		window.Event[i] = window.Events[i];
	}
})();