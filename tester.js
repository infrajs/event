(function () {

	Event.clear('test');
	test=Tester;
	var counter='';
	test.tasks.push([
		'Очередь событий',
		function () {
			Event.handler('test.opa',function () {
				counter+='2';
			},'two:one');
			Event.handler('test.opa',function () {
				counter+='1';
			},'one');
			test.check();
		},
		function () {
			Event.fire('test.opa');
			if (counter === '12') return test.ok();
			if (counter === '21') return test.err('Порядок выполнения нарушен '+counter);
			return test.err('Всё плохо '+counter);
		}
	]);



	test.tasks.push([
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

			test.check();
		},
		function () {
			Event.fire('test.one');
			if (counter === '136524') return test.ok();
			return test.err('Всё плохо '+counter);
		}
	]);
	test.tasks.push([
		'memories',
		function () {
			Event.handler('test.check',function () {
				return false
			});
			test.check();
		},
		function () {
			var r = Event.fire('test.check');
			if (r) return test.err("Event.fire('test.false') Должен был вернуть false. 1 раз.");
			var r = Event.fire('test.check');
			if (r) return test.err("Event.fire('test.false') Должен был вернуть false. 2 раз.");
			return test.ok();
		}
	]);
	test.tasks.push([
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
			test.check();
		},
		function () {
			var r = Event.fire('test.false');
			if (r) return test.err("Event.fire('test.false') Должен был вернуть false");
			var r = Event.fire('test.false');
			if (r) return test.err("Event.fire('test.false') Должен был вернуть false и во второй раз");
			if (counter === '12') return test.ok();
			return test.err('Всё плохо '+counter);
		}
	]);



	test.tasks.push([
		'Звонок в дверь',
		function () {
			Event.fire('Звонок в дверь');
			res = null;
			Event.handler('Звонок в дверь', function () {
				res = true;
				return false;
			}, 'Anton:Vika');
			Event.handler('Звонок в дверь', function () {
				res = false;
				return false;
			}, 'Vika');
			
			test.check();
		},
		function () {
			Event.clear('Звонок в дверь');
			if (!res) return test.err('Звонок в дверь не выполнен');
			return test.ok();
		}
	]);

	test.tasks.push([
		'Звонок по телефону',
		function () {
			res = null;
			Event.fire('Звонок по телефону');
			Event.handler('Звонок по телефону', function () {
				res = false;
				return false;
			}, 'Anton:Vika');
			Event.handler('Звонок по телефону', function () {
				res = true;
				return false;
			}, 'Vika');
			
			test.check();
		},
		function () {
			Event.clear('Звонок по телефону');
			if (!res) return test.err('Звонок по телефону не выполнен');
			return test.ok();
		}
	]);

//















	test.exec();
})();