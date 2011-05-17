Ext.onReady(function(){
	Ext.QuickTips.init();
	
	// welcome panel
	var welcome = {
		id: 'welcome-panel',
		title: 'Welcome to TenBrain!',
		layout: 'fit',
		bodyStyle: 'padding:25px',
		contentEl: 'welcome-div'  // pull existing content from the page
	};
	
	var menu = function(){
		var items = [ welcome,
			// instances.js:
			// Instances.get_panel('running'), Instances.get_panel('terminated'), Instances.get_panel('stopped'),
			// images.js:
			// Images.get_grid(),
			// snapshots.js:
			// Snapshots.get_panel(),
			// profile.js:
			// account_profile, account_settings, account_password, account_linked
		];
		if(account_type === 'premium')
		{
			items.push(Load_balancers.get_grid());
			items.push(Elastic_IPs.get_grid());
		}
		return {
			get_items: function(){ return items },
			get_active: function(){
				for(var i = items.length; i--;)
				{
					if(items[i].id === active_menu_item + '-panel') return i;
				}
				return 1; // running instances - default
			}
		}
	}();
// 	
	// var tree_panel = Ext.create('Ext.tree.Panel', {
    	// id: 'tree-panel',
    	// title: 'Menu',
        // region: 'north',
        // split: true,
        // height: 300,
        // minSize: 150,
//         
        // // tree-specific configs:
        // rootVisible: false,
        // lines: false,
        // useArrows: true,
//         
        // store: Ext.create('Ext.data.TreeStore', {
	    	// proxy: {
	    		// type: 'ajax',
	    		// url: '/control_panel/menu'
	    	// }
	    // })
    // }),
	var help_panel = {
		id: 'details-panel',
        title: 'Details',
        region: 'center',
		autoScroll: true,
		html: '<h2>Welcome to TenBrain!</h2><p>When you select a menu item, additional details will display here.</p>'
    },
	helper;
    
    // tree_panel.on('selectionchange', function(m, selections){
    	// if(selections.length){
	    	// var node = selections[0],
	    		// id = node.internalId;
	    	// if(node.isLeaf()){  // ignore clicks on folders
	    		// Ext.getCmp('content-panel').layout.setActiveItem(id + '-panel');
	    		// if(!helper){
					// // create default empty div
	    			// helper = Ext.getCmp('details-panel').body.update('').setStyle('background', '#fff').createChild();
	    		// }
	    		// helper.hide().update(Ext.getDom(id + '-details').innerHTML).slideIn('l', {stopFx: true, duration: .2});
	    	// }
    	// }
    // });
	
    Ext.create('Ext.container.Viewport', {
		layout: 'border',
		renderTo: Ext.getBody(),
		items: [{
			xtype: 'box',
			region: 'north',
			contentEl: 'header'
		}, {
			layout: 'border',
			id: 'layout-browser',
			region: 'west',
			border: false,
			split: true,
			margins: '2 0 5 5',
			width: 275,
			minSize: 100,
			maxSize: 500,
			items: [help_panel]
		}, {
			xtype: 'box',
			region: 'south',
			height: 25,
			contentEl: 'footnote'
		}, {
			id: 'content-panel',
			region: 'center',
			layout: 'card',
			margins: '2 5 5 0',
			// items: menu.get_items(),
			// activeItem: menu.get_active(),
			defaults: {
				border: false
			}
		}]
    });
});
