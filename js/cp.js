Ext.onReady(function(){
	var Profile = function() {
		var container;
		return {
			show_page : function(btn){
				container = container || btn.findParentByType('panel').body;
				container.dom.src = '/account/' + btn.prolile_page;
				// container.load({
					// url: 'account/' + btn.prolile_page,
					// scope: this
				// })
			}
		};
	}();
	
	new Ext.Viewport({
		height: 600,
		layout: {
			type: 'border',
			align: 'stretch',
			padding: 5
		},
		items: [{
			region: 'north',
			html: '<div id="header">'
				+ '<a href="/" id="logo">Home</a>'
				+ '<div class="account_area"><div class="signed_in_controls">'
				+ '<span class="welcome_message">Welcome, slavko!</span><br>'
				+ '<a class="blue underlined_dash" href="/account/sign_out">Sign out</a>'
				+ '</div></div></div>',
			border: false,
			margins: '0 0 0 0'
		}, {
			region: 'west',
			collapsible: true,
			title: 'Navigation',
			width: 200
		}, {
			region: 'south',
			collapsible: true,
			html: '<div style="text-align:center">All rights reserved &copy; ' + new Date().getFullYear() + ', TenBrain</div>'
		}, {
			region: 'east',
			title: 'Title for the Grid Panel',
			collapsible: true,
			split: true,
			width: 200
		}, {
			region: 'center',
			xtype: 'panel',
			autoScroll: true,
			layout: 'fit',
			bodyCfg: {
				tag: 'iframe',
				src: '',
				frameborder: 0,
				scrolling: 'auto'
			},
			tbar: {
				xtype: 'toolbar',
				items: [{
					xtype: 'button',
					text: 'Cloud instances'
				}, '-', {
					xtype: 'button',
					text: 'Management console'
				}, '->', {
					xtype: 'button',
					text: 'Profile',
					menuAlign: 'tr-br?',
					menu: [{
						text: 'Profile Information',
						prolile_page: 'account_profile',
						handler: Profile.show_page
					},{
						text: 'Account Settings',
						prolile_page: 'account_settings',
						handler: Profile.show_page
					},{
						text: 'Password',
						prolile_page: 'account_password',
						handler: Profile.show_page
					},{
						text: 'Linked accounts',
						prolile_page: 'account_linked',
						handler: Profile.show_page
					}]
				}]
			}
		}],
		style: 'padding:10px'
	});
});