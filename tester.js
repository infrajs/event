(function () {
	var counter='';
	
	Tester.tasks.push([
		'Событие для объекта',
		function () {
			var obj1 = {id:1};
			var obj2 = {id:2};
			Event.classes['Класс'] = function (obj) {
				return obj.id;
			}
			counter = 0;
			Event.handler('Класс.событие', function () {
				counter++;
			}, '', obj1);

			Event.fire('Класс.событие', obj1);
			Event.fire('Класс.событие', obj2);
			
			Tester.check();
		},
		function () {
			Event.clear('Класс.событие');
			if (counter != 1) return Tester.err('Событие для объекта сработало раз '+counter);
			return Tester.ok();
		}
	]);

	Tester.tasks.push([
		'Очередь событий',
		function () {
			counter='';
			Event.clear('test');
			Event.handler('test.opa',function () {
				counter+='2';
			},'two:one');
			Event.handler('test.opa',function () {
				counter+='1';
			},'one');
			Tester.check();
		},
		function () {
			Event.fire('test.opa');
			if (counter === '12') return Tester.ok();
			if (counter === '21') return Tester.err('Порядок выполнения нарушен '+counter);
			return Tester.err('Всё плохо '+counter);
		}
	]);



	Tester.tasks.push([
		'Взаимозависимость',
		function () {
			counter='';
			Event.handler('test.one',function () {
				counter+='2';
			},'two:one');
			Event.handler('test.one',function () {
				counter+='1';
			},'one');
			Event.handler('test.one',function () {
				counter+='3';
			},'one');
			Event.handler('test.one',function () {
				counter+='4';
			},'two:one');
			Event.handler('test.one',function () {
				counter+='5';
			},'one:three');
			Event.handler('test.one',function () {
				counter+='6';
			},'three');

			Tester.check();
		},
		function () {
			Event.fire('test.one');
			if (counter === '136524') return Tester.ok();
			return Tester.err('Всё плохо '+counter);
		}
	]);

	Tester.tasks.push([
		'memories',
		function () {
			Event.handler('Tester.check',function () {
				return false
			});
			Tester.check();
		},
		function () {
			var r = Event.fire('Tester.check');
			if (r) return Tester.err("Event.fire('test.false') Должен был вернуть false. 1 раз.");
			var r = Event.fire('Tester.check');
			if (r) return Tester.err("Event.fire('test.false') Должен был вернуть false. 2 раз.");
			return Tester.ok();
		}
	]);
	
	Tester.tasks.push([
		'return false',
		function () {
			counter='';
			Event.handler('test.false',function () {
				counter+='1';
			});
			Event.handler('test.false',function () {
				counter+='2';
				return false;
			});
			Event.handler('test.false',function () {
				counter+='3';
			});
			Tester.check();
		},
		function () {
			var r = Event.fire('test.false');
			if (r) return Tester.err("Event.fire('test.false') Должен был вернуть false");
			var r = Event.fire('test.false');
			if (r) return Tester.err("Event.fire('test.false') Должен был вернуть false и во второй раз");
			if (counter === '12') return Tester.ok();
			return Tester.err('Всё плохо '+counter);
		}
	]);



	Tester.tasks.push([
		'Звонок в дверь',
		function () {
			Event.fire('Звонок в дверь');
			counter = null;
			Event.handler('Звонок в дверь', function () {
				counter = true; //Выполняется в последнюю очередь
				return false;
			}, 'Anton:Vika');
			Event.handler('Звонок в дверь', function () {
				counter = false;
				return false;
			}, 'Vika');
			
			Tester.check();
		},
		function () {
			Event.clear('Звонок в дверь');
			if (!counter) return Tester.err('Звонок в дверь не выполнен');
			return Tester.ok();
		}
	]);

	Tester.tasks.push([
		'Звонок по телефону',
		function () {
			counter = null;
			Event.handler('Звонок по телефону', function () {
				counter = false;
				return false;
			}, 'Anton:Vika');
			Event.handler('Звонок по телефону', function () {
				counter = true; //Больше ничего не выполняется
				return false;
			}, 'Vika');
			Event.fire('Звонок по телефону');
			Tester.check();
		},
		function () {
			Event.clear('Звонок по телефону');
			if (!counter) return Tester.err('Звонок по телефону не выполнен');
			return Tester.ok();
		}
	]);

	Tester.exec();
})();