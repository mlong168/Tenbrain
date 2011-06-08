Ext.Loader.setConfig({enabled: true});

Ext.require([
    'Ext.tip.QuickTipManager',
    'Ext.container.Viewport',
    'Ext.layout.*',
    'Ext.form.Panel',
    'Ext.form.Label',
    'Ext.grid.*',
    'Ext.data.*',
    'Ext.tree.*',
    'Ext.selection.*',
    'Ext.tab.Panel'
]);

Ext.onReady(function(){
	
	Ext.tip.QuickTipManager.init();
	
	// welcome panel
	var welcome = {
		id: 'welcome-panel',
		title: 'Welcome to TenBrain!',
		layout: 'fit',
		bodyStyle: 'padding:25px',
		contentEl: 'welcome-div'  // pull existing content from the page
	};
	
	var pages = [],
		add_pages = function(Containers){	// Containers must be an array
			for(var i = Containers.length; i--;)
			{
				Ext.Object.each(Containers[i].panels, function(name, example) {
					pages.push(example);
				});
			}
		};
	pages.push(welcome);
	
	add_pages([Instances, Images, Snapshots, Account]);
	if(account_type === 'premium')
	{
		add_pages([Load_balancers, Elastic_IPs]);
	}
	
	var active_menu = function(){
		for(var i = pages.length; i--;)
		{
			if(pages[i].id === active_menu_item + '-panel') return i;
		}
		return 0; // welcome - default
	}();

	var tree_panel = Ext.create('Ext.tree.Panel', {
    	id: 'tree-panel',
    	title: 'Menu',
        region: 'north',
        split: true,
        height: 330,
        minSize: 150,
        rootVisible: false,
        autoScroll: true,
        
        store: Ext.create('Ext.data.TreeStore', {
			root: {
				expanded: true
			},
	    	proxy: {
	    		type: 'ajax',
	    		url: '/console/menu'
	    	}
	    })
   });
   
	var help_panel = {
		id: 'details-panel',
        title: 'Details',
        region: 'center',
		autoScroll: true,
		html: '<h2>Welcome to TenBrain!</h2><p>When you select a menu item, additional details will display here.</p>'
    };
    
    var detail_el;
	tree_panel.getSelectionModel().on('select', function(selModel, record) {
		var panel_id = record.getId() + '-panel';
		if(record.get('leaf'))
		{
			if(!Ext.getCmp(panel_id))
			{
				Ext.Msg.alert('Access error', 'This feature is only available for paid accounts, or is currently being implemented. Sorry for that');
				return false;
			}
			Ext.getCmp('content-panel').layout.setActiveItem(panel_id);
			if(!detail_el)
			{
				var bd = Ext.getCmp('details-panel').body;
				bd.update('').setStyle('background','#fff');
				detail_el = bd.createChild(); //create default empty div
			}
			detail_el.hide().update(Ext.getDom(record.getId() + '-details').innerHTML).slideIn('l', {stopAnimation:true,duration: 200});
		}
	});
	
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
			items: [tree_panel, help_panel]
		}, {
			id: 'content-panel',
			region: 'center',
			layout: 'card',
			margins: '2 5 5 0',
			items: pages,
			activeItem: active_menu,
			defaults: {
				border: false
			}
		}, {
			xtype: 'box',
			region: 'south',
			height: 20,
			contentEl: 'footnote'
		}]
    });
});
