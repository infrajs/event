( function () {
	window.Event = {
		list:{},
		classes:{
			'alien': function (obj) {
				return '';
			}
		},
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
			return fire;
		},
		createHandler: function (name, callback, key, obj) 
		{
			var handler=this.createContext(name, obj, key);
			handler['callback'] = callback;
			
			handler.list['list'].push(handler);
			if (!handler.list['keys'][handler['key']]) handler.list['keys'][handler['key']] = 1;
			else handler.list['keys'][handler['key']]++; //Сосчитали все ключи

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
			if (cls) { //одноимённый с классом ключ есть всегда. Вроде того что у класса есть свои собственные обработчики котоорые должны сработать первыми.
				keys.push(cls);
			}
			var classes = this.classes;
			
			var objid = obj ? classes[cls](obj) : '';
			
			var context= {
				'key' : key,
				'executed' : {},
				'name': name,
				'class': cls,
				'keys': keys,
				'obj': obj,
				'objid': objid,
				'list':this.getList(name)
			};
			return context;
		},
		is: function(r){
			return typeof(r) == 'undefined' || r;
		},
		//wait when listen
		one: function(name, callback, key, obj)
		{
			var ready=false;
			return this.handler(name, function(){
				if (ready) return;
				ready=true;
				return callback();
			}, key, obj);
		}
		handler: function (name, callback, key, obj)
		{

			var handler = this.createHandler(name, callback, key, obj);
			
			/**
			 * TODO исключить что событие выполняется сейчас
			 **/
			if (obj) { //Подписка на совершённое событие
				if (handler.list['result'][handler['objid']]) {
					callback(obj); //Подписка на конкретный объект
				}
			} else { //Подписка на все объекты
				for (var i in handler.list['result']) {
					callback(list['result'][i]['obj']);
				}
			}
		},
		tik: function (name)
		{
			/**
			 * Режим повторения, сбросить что есть и начать заного.
			 * Передаётся класс или имя
			 **/
			if(!name) {
				for (name in this.list) {
					this.tik(name);
				}
				return;
			}
			var fire=this.createContext(name);
			var list = this.getList(fire['name']);
			for (var i=0, l=list['list'].length; i < l; i++) {
				var handler=list['list'][i];
				handler['executed']={};
			}
			list['result']={}; //Выполнено событие или нет
			list['readyobj']={};//Массив с временными отметками по объектам что выполнено. При равенстве количество с keys. Ключ попадает в массив ready
			list['readykeys']={}; //Выполненные ключи
		},
		fire: function (name, obj)
		{
			/**
			 * Уникальность очереди событий определяется именем события содержащей имя класса события.
			 * Все подписки хранятся в классе и объект не меняется
			 **/


			var fire = this.createFire(name, obj);
			var list=fire.list;

			/**
			 * TODO: Реализация is isshow... нужно сбрасывать события
			 **/
			if (list['result'][fire['objid']]) return list['result'][fire['objid']];

			if (!list['readykeys'][fire['objid']]) list['readykeys'][fire['objid']]=[];
			if (list['readyobj'][fire['objid']]) list['readyobj'][fire['objid']]={};
			
			
			// TODO: проверить обработку несуществующих ключей
			/*for (var i = 0, l = list['list'].length;  i < l; i++) { //Подписка на ходу
				handler = list['list'][i];
				handler['keys']=handler['keys'].filter(function(n) { //Из условия подписчика убраны несуществующие ключи
					return !!list['keys'][n];
				});
			}*/

			var r = this.execute(fire, list);
			if (this.is(r))) r = true;
			else r = false;

			list['result'][fire['objid']] = r;
			return list['result'][fire['objid']];
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

				var iskeys = handler['keys'].filter( function (n) { //Массив невыполненных ключей
					return list['readykeys'][fire['objid']].indexOf(n) === -1; //Проверили выполнены ли все существующие ключи
				});
				if (iskeys.length && (iskeys.length!=1 || !handler['key'] || handler['keys'].indexOf(handler['key']) === -1 )) { // Подписка key:key сработает так как сама она удовлетворяет ключ key
					omit = {
						'keys': iskeys,
						'handler': handler
					};
					continue; //Найден неудовлетворённый ключ.. может быть выход из цикла и на ислкючение
				}

				if (handler['key']) {//Если есть ключ
					if (list['readykeys'][fire['objid']].indexOf(handler['key']) === -1) { //И этого ключа ещё не было

						if (!list['readyobj'][fire['objid']])list['readyobj'][fire['objid']]={};
						if (!list['readyobj'][fire['objid']][handler['key']]) list['readyobj'][fire['objid']][handler['key']] = 0;
						list['readyobj'][fire['objid']][handler['key']]++;
						//Если выполнено по объекту столько же сколько всего обработчиков
						if (list['readyobj'][fire['objid']][handler['key']] === list['keys'][handler['key']]) {
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
				throw 'Рекурсивная зависимость подписчиков. '+omit['keys'].join(',');
			}
		}
	}
})();