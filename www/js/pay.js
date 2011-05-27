$(function(){
	
	$('input#time').bind('keyup ', function(){
		days = Number($(this).val());
		minBaks = 10;
		if (!isNaN(days))
		{
			baks = makeDaysToMoney(days);
			if(baks < minBaks)
			{
				$('input#money').val(minBaks);
				$('input#money').val(minBaks);
			}
			else
			{
				$('input#money').val(baks);
			}
		}
		else
		{
			$(this).val(minBaks);
		}
	});
	
	function makeDaysToMoney(days)
	{
		baksPerDay = 1;
		return days * baksPerDay;
	}
	
});