(function(){

	Event.clear('test');

	test=infrajs.test;
	var counter='';
	test.tasks.push([
		'Очередь событий',
		function(){
			Event.handler('test.opa',function(){
				counter+='2';
			},'two:one');
			Event.handler('test.opa',function(){
				counter+='1';
			},'one');
			test.check();
		},
		function() {
			Event.fire('test.opa');
			if (counter === '12') return test.ok();
			if (counter === '21') return test.err('Порядок выполнения нарушен '+counter);
			return test.err('Всё плохо '+counter);
		}
	]);

	test.exec();
})();