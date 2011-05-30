$(function(){

	minBaks = 10;
	baksPerDay = 1;
	
	$('input#time_amount').bind('keyup', function(){
		days = Number($(this).val());
		if (!isNaN(days))
		{
			baks = makeDaysToMoney(days);
			if(baks < minBaks)
			{
				minDays = makeMoneyToDays(minBaks);
				$('input#money_amount').val(minBaks);
			}
			else
			{
				$('input#money_amount').val(baks);
			}
		}
		else
		{
			$(this).val(minBaks);
		}
	});
	
	$('input#money_amount').bind('keyup', function(){
		baks = Number($(this).val());
		if (!isNaN(baks))
		{
			days = makeMoneyToDays(baks);
			if(baks < minBaks)
			{
				minDays = makeMoneyToDays(minBaks);
				$('input#time_amount').val(minDays);
			}
			else
			{
				$('input#time_amount').val(days);
			}
		}
		else
		{
			$(this).val(minBaks);
		}
	});
	
	$('input#money_amount').bind('change', function(){
		baks = Number($(this).val());
		if (!isNaN(baks))
		{
			days = makeMoneyToDays(baks);
			if(baks < minBaks)
			{
				minDays = makeMoneyToDays(minBaks);
				$('input#time_amount').val(minDays);
				$('input#money_amount').val(minBaks);
			}
			else
			{
				$('input#time_amount').val(days);
			}
		}
		else
		{
			$(this).val(minBaks);
		}
	});
	

	function makeDaysToMoney(days)
	{
		return days * baksPerDay;
	}

	function makeMoneyToDays(money)
	{
		return money / baksPerDay;
	}
	
});