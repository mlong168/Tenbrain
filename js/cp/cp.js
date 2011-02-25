// console.log(Ext.BLANK_IMAGE_URL)
// if (Ext.BLANK_IMAGE_URL.substr(0, 5) != 'data:') {
	// Ext.BLANK_IMAGE_URL = '/css/ext_resources/images/default/s.gif';
// }
Ext.onReady(function(){
	Ext.QuickTips.init();
	var content_panel = {
		id: 'content-panel',
		region: 'center',
		layout: 'card',
		margins: '2 5 5 0',
		activeItem: active_menu_item,
		border: false,
		items: [
			// from cloud.js:
			welcome, running_instances, terminated_instances, stopped_instances, available_images, snapshots,
			// from profile.js:
			account_profile, account_settings, account_password, account_linked
		]
	},	
    tree_panel = new Ext.tree.TreePanel({
    	id: 'tree-panel',
    	title: 'Menu',
        region:'north',
        split: true,
        height: 300,
        minSize: 150,
        autoScroll: true,
        
        // tree-specific configs:
        rootVisible: false,
        lines: false,
        singleExpand: true,
        useArrows: true,
        
        loader: new Ext.tree.TreeLoader({
            dataUrl:'/control_panel/menu'
        }),
        
        root: new Ext.tree.AsyncTreeNode()
    }),
	help_panel = {
		id: 'details-panel',
        title: 'Details',
        region: 'center',
		autoScroll: true,
		html: '<h2>Welcome to TenBrain!</h2><p>When you select a menu item, additional details will display here.</p>'
    },
	helper;
    
    tree_panel.on('click', function(n){
    	var sn = this.selModel.selNode || {}; // selNode is null on initial selection
    	if(n.leaf && n.id != sn.id){  // ignore clicks on folders and currently selected node 
    		Ext.getCmp('content-panel').layout.setActiveItem(n.id + '-panel');
    		if(!helper){
				// create default empty div
    			helper = Ext.getCmp('details-panel').body.update('').setStyle('background', '#fff').createChild();
    		}
    		helper.hide().update(Ext.getDom(n.id + '-details').innerHTML).slideIn('l', {stopFx: true, duration: .2});
    	}
    });
	
    new Ext.Viewport({
		layout: 'border',
		title: 'TenBrain User Control Panel',
		items: [{
			xtype: 'box',
			region: 'north',
			applyTo: 'header'
		}, {
			layout: 'border',
	    	id: 'layout-browser',
	        region:'west',
	        border: false,
	        split: true,
			margins: '2 0 5 5',
	        width: 275,
	        minSize: 100,
	        maxSize: 500,
			items: [tree_panel, help_panel]
		}, content_panel, {
			xtype: 'box',
			region: 'south',
			applyTo: 'footnote'
		}],
        renderTo: Ext.getBody()
    });
});