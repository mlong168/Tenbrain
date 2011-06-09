var Account = function(){
	var create_page = function(config){
		return {
			xtype: 'panel',
			id: config.id + '-panel',
			title: config.title,
			layout: 'fit',
			items: {
				xtype: 'component',
				autoEl: {
					tag: 'iframe',
					frameborder: 0,
					src: config.src
				}
			}
		}
	};
	return {
		panels: {
			profile_info: create_page({
				id: 'account_profile',
				title: 'Your account info',
				src: '/profile'
			}),
			// profile_info: create_page({
				// id: 'account_linked',
				// title: 'Linked accounts',
				// src: '/account/linked'
			// }),
			passwords: create_page({
				id: 'account_password',
				title: 'Password management',
				src: '/account/password'
			})
		}
	}
}();