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
	
	(function(){
		var helper = $('#helper'),
			message = helper.children('.help_message'),
			modulars = $('.overlay'),
			overlays = {
				back: modulars.filter('.back'),
				front: modulars.filter('.front')
			},
			initials = {
				width: helper.width(),
				height: helper.height()
			},
			start = {
				width: initials.width + 'px',
				height: initials.height + 'px',
				opacity: helper.css('opacity')
			},
			end = {
				width: (initials.width + 7) + 'px',
				height: initials.height + 'px',
				opacity: '1'
			};
			
		helper.hover(function(){
			helper.animate(end, 'fast');
		}, function(){
			helper.animate(start, 'fast');
		}).click(function(){
			overlays.back.fadeTo('fast', 0.5, function(){
				overlays.front.show();
			});
		});
		
		overlays.back.click(function(){
			modulars.hide();
		});
		
		$(document).keydown(function(event){
			if(event.which === 27)
			{
				modulars.hide();
			}
		});
	})();
	
	(function(){
		var signin_button = $('#signin_opener'),
			signin_form = $('#signin_controls'),
			mouse_inside = false;
		signin_button.add(signin_form).hover(function(){
			mouse_inside = true;
		}, function(){
			mouse_inside = false;
		});
		
		signin_button.click(function(){
			signin_form.toggle();
			signin_button.toggleClass('active');
		});
		
		$(document).click(function(){
			if(!mouse_inside)
			{
				signin_form.hide();
				signin_button.removeClass('active');
			}
		})
		
	})();
});