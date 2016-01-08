(function(){
	var depot=infra.fire.depot(infra,'test1');
	depot.listen=[];
	delete depot.evt;
	test=infra.test;
	var counter='';
	test.tasks.push([
		'Очередь событий',
		function(){
			infra.handle(infra,'test1',function(){
				counter+='2';
			},'two:one');
			infra.handle(infra,'test1',function(){
				counter+='1';
			},'one');
			test.check();
		},
		function() {
			infra.fire(infra,'test1');
			if (counter === '12') return test.ok();
			if (counter === '21') return test.err('Порядок выполнения нарушен '+counter);
			return test.err('Всё плохо '+counter);
		}
	]);

	test.exec();
})();