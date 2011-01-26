$(function(){
	$('.social_icon').each(function(){
		var self = $(this);
		self.hover(function(){
			self.animate({
				top: '0',
				opacity: 1
			}, 200);
		}, function(){
			self.animate({
				top: '-12px',
				opacity: 0.5
			}, 200);
		})
	});
});