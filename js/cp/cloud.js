// main operational object singleton:
var Cloud = function(){
	var images = function(){
		var record = Ext.data.Record.create([
			'id',
			'image_id',
			'name',
			'state',
			'description',
			'virtualization',
			'location'
		]);
		return new Ext.data.Store({
			url: '/amazon/available_images',
			reader: new Ext.data.JsonReader({
				root: 'images',
				successProperty: 'success',
				idProperty: 'id'
			}, record),
			autoLoad: true
		});
	}(),
	instances = function(){
		var record = Ext.data.Record.create([
			'id',
			'name',
			'dns_name',
			'ip_address',
			'instance_id',
			'image_id',
			'state',
			'virtualization',
			'type',
			'root_device'
		]),
		// all available states: ['pending', 'running', 'shutting-down', 'terminated', 'stopping', 'stopped']
		// 'running' state here also represents unstable states - pending, shutting-down, stopping
		states = ['running', 'terminated', 'stopped'],
		stores = {};
		for(var i = states.length; i--;)
		{
			stores[states[i]] = new Ext.data.Store({
			url: '/amazon/show_instances/' + states[i],
			reader: new Ext.data.JsonReader({
				root: 'instances',
				successProperty: 'success',
				idProperty: 'id'
			}, record),
			autoLoad: true
		})
		}
		return stores;
	}(),
	reload_instances_until_stable = function(){
		var init_timeout = 5000, interval = init_timeout, minimum_interval = 1000, jump = 1000;
		return function(state, step){
			state = state || 'running';
			step = step || jump;
				
			if(this !== reload_instances_until_stable)
			{
				interval = init_timeout;
			}
			
			instances[state].reload({
				callback: function(r){
					for(var i = r.length; i--;)
					{
						if(r[i].data.state !== state)
						{
							setTimeout(function(){
								reload_instances_until_stable.call(reload_instances_until_stable, state, step);
							}, interval);
							if(interval > minimum_interval && interval - step > 0) interval -= step;
							break;
						}
					}
				}
			});
			return false;
		};
	}();
	
	return {
		get_instances: function(state){
			return instances[state];
		},
		reload_instances: function(state){	// reload all if state is not specified
			if(typeof state === 'string' && $.inArray(state, states) !== -1)
			{
				instances[state].reload();
			}
			else
			{
				for(var i = instances.length; i--;)
				{
					instances[i].reload();
				}
			}
		},
		reload_instances_until_stable: reload_instances_until_stable,
		get_images: function(){
			return images;
		}
	};
}();

// layouts:
var welcome = {
	id: 'welcome-panel',
	title: 'Welcome to TenBrain!',
	layout: 'fit',
	bodyStyle: 'padding:25px',
	contentEl: 'welcome-div'  // pull existing content from the page
};

/**
 * instances stuff goes here
 */
// menus:
var instances_menu_handler = function(item){
	item.parentMenu.items.each(function(it){
		var id = it.id, action = id.substr(0, id.indexOf('_'));
		if($.inArray(action, it.enabled_after) !== -1)
		{
			it.enable();
		}
		else
		{
			// it.disable();
		}
	});
	var id = item.id, action = id.substr(0, id.indexOf('_')),
		parent_menu = item.parentMenu.findParentByType('menu');
		
	$.ajax({
		url: '/amazon/' + action + '_instance',
		type: 'POST',
		data: { instance_id: parent_menu.selected_instance_id },
		dataType: 'json',
		success:function(response){
			if(response.success === true)
			{
				Ext.Msg.alert('Operation Successful');
			}
			else
			{
				Ext.Msg.alert('A problem occured while processing your request. Please try again');
			}
			Cloud.reload_instances_until_stable();
		},
		error: function(){
			Ext.Msg.alert('A problem occured while processing your request. Please try again');
		}
	});
},
instances_menu = new Ext.menu.Menu({
	items: [{
		text: 'Management',
		menu: {
			items: [{
				text: 'Create Snapshot',
				handler: function(){
					instances_menu.hide();
					Snapshots.create(instances_menu.selected_instance_id);
				}
			}, {
				text: 'View Snapshots',
				handler: function(){
					instances_menu.hide();
					Snapshots.show_instance_snapshots(instances_menu.selected_instance_id);					
				}
			}]
		}
	}, '-', {
		text: 'Actions',
		menu: {
			items: [{
				id: 'terminate_instance',
				enabled_after: ['start'],
				text: 'Terminate',
				handler: instances_menu_handler
			}, {
				text: 'Start',
				id: 'start_instance',
				enabled_after: ['stop', 'terminate', 'reboot'],
				handler: instances_menu_handler
			}, {
				text: 'Stop',
				id: 'stop_instance',
				enabled_after: ['start', 'terminate'],
				handler: instances_menu_handler
			}, {
				text: 'Reboot',
				id: 'reboot_instance',
				enabled_after: ['start', 'stop', 'terminate', 'reboot'],
				handler: instances_menu_handler
			}]
		}
	}, '-', {
		text: 'Monitoring',
		menu: {
			items: [{
				text: '1'
			}, {
				text: '2'
			}, {
				text: '3'
			}]
		}
	}],
	ref_grid: null,
	selected_instance_id: null
});

// renderers:
var link_wrapper = function(link){
	return '<a target="_blank" href="http://' + link + '/">' + link + '</a>';
},
statesman = function(value, metadata, record, rowIndex, colIndex, store){
	if(record.data.state !== 'running')
	{
		metadata.css = 'grid-loader';
	}
	else
	{
		// return '<input type="checkbox">';
	}
	// return false;
};

// layouts:
var running_instances_grid = new Ext.grid.GridPanel({
	layout: 'fit',
	border: false,
	store: Cloud.get_instances('running'),
	bbar: {
		xtype: 'toolbar',
		items: ['->', {
			xtype: 'button',
			text: 'Refresh List',
			handler: function(){
				Cloud.get_instances('running').reload();
			}
		}]
	},
	view: new Ext.grid.GridView({
		forceFit: true,
		emptyText: '<p style="text-align: center">You have not launched any instance so far</p>'
	}),
	listeners: {
		rowcontextmenu: function (grid, id, e) {
			var menu = instances_menu;
			e.preventDefault();
			if(menu.ref_grid === null) menu.ref_grid = grid;
			menu.selected_instance_id = grid.getStore().getAt(id).get('instance_id');
			menu.showAt(e.getXY());
		}
	},	
	cm: new Ext.grid.ColumnModel({
		defaultSortable: false,
		columns: [
			{width: 25, fixed: true, renderer: statesman},
			{header: "Name", dataIndex: 'name', width: 150},
			{header: "Link to instance root", dataIndex: 'dns_name', width: 250, renderer: link_wrapper},
			{header: "IP Address", dataIndex: 'ip_address', width: 120},
			{header: "State", dataIndex: 'state', width: 100},
			{header: "Virtualization", dataIndex: 'virtualization', width: 100},
			{header: "Type", dataIndex: 'type', width: 100},
			{header: "Root Device", dataIndex: 'root_device', width: 100}
		]
	})
});

var running_instances = new Ext.Panel({
	id: 'running_instances-panel',
	title: 'Your currently running instances',
	layout: 'fit',
	items: [running_instances_grid, Snapshots.get_creator(), Snapshots.get_instance_snapshots()],
	activeItem: 1
})

var stopped_instances = new Ext.grid.GridPanel({
	id: 'stopped_instances-panel',
	title: 'Instances that have been stopped',
	layout: 'fit',
	store: Cloud.get_instances('stopped'),
	view: new Ext.grid.GridView({
		forceFit: true,
		emptyText: '<p style="text-align: center">You do not currently have any stopped instance</p>'
	}),
	bbar: {
		xtype: 'toolbar',
		items: ['->', {
			xtype: 'button',
			text: 'Refresh List',
			handler: function(){
				Cloud.get_instances('stopped').reload();
			}
		}]
	},	
	cm: new Ext.grid.ColumnModel({
		defaultSortable: false,
		columns: [
			{width: 25, fixed: true, renderer: statesman},
			{header: "Name", dataIndex: 'name', width: 150},
			{header: "Link to instance root", dataIndex: 'dns_name', width: 250, renderer: link_wrapper},
			{header: "IP Address", dataIndex: 'ip_address', width: 120},
			{header: "State", dataIndex: 'state', width: 100},
			{header: "Virtualization", dataIndex: 'virtualization', width: 100},
			{header: "Type", dataIndex: 'type', width: 100},
			{header: "Root Device", dataIndex: 'root_device', width: 100}
		]
	})
}),
terminated_instances = new Ext.grid.GridPanel({
	id: 'terminated_instances-panel',
	title: 'Instances that have previously been terminated',
	layout: 'fit',
	store: Cloud.get_instances('terminated'),
	bbar: {
		xtype: 'toolbar',
		items: ['->', {
			xtype: 'button',
			text: 'Refresh List',
			handler: function(){
				Cloud.get_instances('terminated').reload();
			}
		}]
	},
	view: new Ext.grid.GridView({
		forceFit: true,
		emptyText: '<p style="text-align: center">You do not have any terminated instance so far</p>'
	}),
	cm: new Ext.grid.ColumnModel({
		defaultSortable: false,
		columns: [
			{header: "Name", dataIndex: 'name', width: 150},
			{header: "Link to instance root", dataIndex: 'dns_name', width: 250, renderer: link_wrapper},
			{header: "IP Address", dataIndex: 'ip_address', width: 120},
			{header: "State", dataIndex: 'state', width: 100},
			{header: "Virtualization", dataIndex: 'virtualization', width: 100},
			{header: "Type", dataIndex: 'type', width: 100},
			{header: "Root Device", dataIndex: 'root_device', width: 100}
		]
	})
});

var instance_types = new Ext.data.ArrayStore({
	fields: ['type', 'banned', 'reason'],
	data: [
		['t1.micro', false, ''],
		['m1.small', true, 'Not available in a free version'],
		['m1.large', true, 'Not available in a free version'],
		['m1.xlarge', true, 'Not available in a free version'],
		['m2.xlarge', true, 'Not available in a free version'],
		['m2.2xlarge', true, 'Not available in a free version'],
		['m2.4xlarge', true, 'Not available in a free version'],
		['c1.medium', true, 'Not available in a free version'],
		['c1.xlarge', true, 'Not available in a free version'],
		['cc1.4xlarge', true, 'Not available in a free version'],
		['cg1.4xlarge', true, 'Not available in a free version']
	]
}),

deploy_configurator = new Ext.FormPanel({
	id: 'deploy_configurator',
	labelWidth: 125,
	url: '/amazon/launch_instance',
	frame: true,
	floating: true,
	title: 'Deployment options',
	width: 320,
	height: 60,
	bodyStyle: 'padding: 5px 0 5px 5px',
	hidden: true,
	monitorValid: true,

	items: [{
		xtype: 'textfield',
		fieldLabel: 'Instance Name',
		name: 'instance_name',
		allowBlank: false,
		vtype: 'alphanum'
	}, {
		xtype: 'combo',
		fieldLabel: 'Instance Type',
		hiddenName: 'genre',
		mode: 'local',
		name: 'instance_type',
		store: instance_types,
		displayField: 'type',
		valueField: 'type',
		allowBlank: false,
		width: 150,
		tpl: '<tpl for="."><div ext:qtip="{reason}" class="x-combo-list-item">{type}</div></tpl>',
		listeners: {
			beforeselect: function(combo, record){
				return !record.data.banned; // false if not selectable
			}
		}
	}, {
		xtype: 'hidden',
		name: 'image_id'
	}],

	buttons: [{
		text: 'Proceed',
		formBind: true,
		handler: function(){
			deploy_configurator.getForm().submit({
				success: function(form, action){
					deploy_configurator.hide();
					if(action.result.success === true)
					{
						Ext.Msg.alert('Your Selected image has been successfully deployed');
					}
					else
					{
						Ext.Msg.alert('A problem occured while deploying your selected image');
					}
					Cloud.reload_instances_until_stable('running');
				},
				failure: function(form, action){
					Ext.Msg.alert('A problem occured while deploying your selected image');
				}
			});
		}
	},{
		text: 'Cancel',
		handler: function(){
			deploy_configurator.hide();
		}
	}]
}),

images_menu = new Ext.menu.Menu({
	items: [{
		text: 'Actions',
		menu: {
			items: [{
				text: 'Deploy',
				handler: function(){
					images_menu.hide();					
					deploy_configurator.getForm().reset().setValues({image_id: images_menu.selected_image_id});
					
					deploy_configurator.setPosition(200, 100).show();
					return false;
				}
			}, {
				text: '2'
			}, {
				text: '3'
			}]			
		}
	}],
	selected_image_id: null
}),

images_grid = new Ext.grid.GridPanel({
	id: 'images_grid-panel',
	// title: 'Images available for deployment',
	layout: 'fit',
	store: Cloud.get_images(),
	border: false,
	viewConfig: {
		forceFit: true,
		emptyText: '<p style="text-align: center">No images are available for deployment</p>'
	},
	listeners: {
		rowcontextmenu: function (grid, id, e) {
			e.preventDefault();
			images_menu.selected_image_id = grid.getStore().getAt(id).get('image_id');
			images_menu.showAt(e.getXY());
		}
	},
	colModel: new Ext.grid.ColumnModel({
		columns: [
			{header: "Name", dataIndex: 'name', width: 130},
			{header: "State", dataIndex: 'state', width: 130},
			{header: "Description", dataIndex: 'description', width: 170},
			{header: "Virtualization", dataIndex: 'virtualization', width: 100},
			{header: "Location", dataIndex: 'location', width: 200}
		]
	})
}),

available_images = new Ext.Panel({
	id: 'available_images-panel',
	layout: 'fit',
	title: 'Images available for deployment',
	items: [images_grid, deploy_configurator],
	activeItem: 1
});

Cloud.reload_instances_until_stable('running');