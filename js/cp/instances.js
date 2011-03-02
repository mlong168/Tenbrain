// main operational object singleton:
var Cloud = function(){
	var instances = function(){
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
		reload_instances_until_stable: reload_instances_until_stable
	};
}();

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
		parent_menu = item.parentMenu.findParentByType('menu'),
		instance_id = instances_menu.ref_grid.getStore().getAt(instances_menu.selected_record_id).get('instance_id');
		
	$.ajax({
		url: '/amazon/' + action + '_instance',
		type: 'POST',
		data: { instance_id: instance_id },
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
					var record = instances_menu.ref_grid.getStore().getAt(instances_menu.selected_record_id),
						id = record.get('instance_id');
					instances_menu.hide();
					Snapshots.create(id);
				}
			}, {
				text: 'View Snapshots',
				handler: function(){
					var record = instances_menu.ref_grid.getStore().getAt(instances_menu.selected_record_id),
						id = record.get('instance_id'),
						name = record.get('name');
					instances_menu.hide();
					Snapshots.show_instance_snapshots(id, name);
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
	selected_record_id: null
});

// renderers:
var link_wrapper = function(link){
	return '<a target="_blank" href="http://' + link + '/">' + link + '</a>';
},
statesman = function(value, metadata, record){
	if(record.data.state !== 'running')
	{
		metadata.css = 'grid-loader';
	}
	return value;
};

// layouts:
var sm = new Ext.grid.CheckboxSelectionModel();
var running_instances = new Ext.grid.GridPanel({
	id: 'running_instances-panel',
	title: 'Your currently running instances',
	layout: 'fit',
	border: false,
	store: Cloud.get_instances('running'),
	view: new Ext.grid.GridView({
		forceFit: true,
		emptyText: '<p style="text-align: center">You have not launched any instance so far</p>'
	}),
	listeners: {
		rowcontextmenu: function (grid, id, e) {
			var menu = instances_menu;
			e.preventDefault();
			if(menu.ref_grid === null) menu.ref_grid = grid;
			menu.selected_record_id = id;
			menu.showAt(e.getXY());
		}
	},
	sm: sm,
	cm: new Ext.grid.ColumnModel({
		defaultSortable: false,
		columns: [
			sm,
			{header: "Name", dataIndex: 'name', width: 150, renderer: statesman},
			{header: "Link to instance root", dataIndex: 'dns_name', width: 250, renderer: link_wrapper},
			{header: "IP Address", dataIndex: 'ip_address', width: 120},
			{header: "State", dataIndex: 'state', width: 100},
			{header: "Virtualization", dataIndex: 'virtualization', width: 100},
			{header: "Type", dataIndex: 'type', width: 100},
			{header: "Root Device", dataIndex: 'root_device', width: 100}
		]
	}),
	tbar: {
		xtype: 'toolbar',
		items: [{
			xtype: 'button',
			text: 'Reboot',
			cls: 'x-btn-text-icon',
			handler: function(){
				var selected = sm.getSelections(), instances = [],
					title = 'Reboot Instances',
					success = 'Selected instances have been rebooted successfully',
					error = 'A problem has occurred while rebooting the instances';
					
				if(!sm.getCount())
				{
					Ext.Msg.alert('Warning', 'Please select some instances to perform the action');
					return false;
				}
				
				for(var i = selected.length; i--;)
				{
					instances.push(selected[i].data.instance_id);
				}
				
				Ext.MessageBox.confirm(title, 'Are you sure you want to reboot these instances?', function(button){
					if(button !== 'yes') return false;
				
					Ext.Msg.wait('Rebooting selected instances', title);
					Ext.Ajax.request({
						url: 'amazon/reboot_instance',
						params: {
							instances: Ext.encode(instances)
						},
						success: function(response){
							response = Ext.decode(response.responseText);
							var s = response.success;
							Ext.Msg.alert(title, s ? success : response.error_message || error);
							Cloud.get_instances('running').reload();
						},
						failure: function(){
							Ext.Msg.alert(title, error);
						}
					});
				});
			}
		}, {
			xtype: 'button',
			text: 'Stop',
			cls: 'x-btn-text-icon',
			handler: function(){
				var selected = sm.getSelections(), instances = [],
					title = 'Stop Instances',
					success = 'Selected instances have been stopped successfully',
					error = 'A problem has occurred while stopping the instances';
					
				if(!sm.getCount())
				{
					Ext.Msg.alert('Warning', 'Please select some instances to perform the action');
					return false;
				}
				
				for(var i = selected.length; i--;)
				{
					instances.push(selected[i].data.instance_id);
				}
				
				Ext.MessageBox.confirm(title, 'Are you sure you want to stop these instances?', function(button){
					if(button !== 'yes') return false;
				
					Ext.Msg.wait('Stopping selected instances', title);
					Ext.Ajax.request({
						url: 'amazon/stop_instance',
						params: {
							instances: Ext.encode(instances)
						},
						success: function(response){
							response = Ext.decode(response.responseText);
							var s = response.success;
							Ext.Msg.alert(title, s ? success : response.error_message || error);
							Cloud.reload_instances_until_stable('running');
						},
						failure: function(){
							Ext.Msg.alert(title, error);
						}
					});
				});
			}
		}, {
			xtype: 'button',
			text: 'Terminate',
			cls: 'x-btn-text-icon',
			handler: function(){
				var selected = sm.getSelections(), instances = [],
					title = 'Terminate Instances',
					success = 'Selected instances have been terminated successfully',
					error = 'A problem has occurred while terminating the instances';
					
				if(!sm.getCount())
				{
					Ext.Msg.alert('Warning', 'Please select some instances to perform the action');
					return false;
				}
				
				for(var i = selected.length; i--;)
				{
					instances.push(selected[i].data.instance_id);
				}
				
				Ext.MessageBox.confirm(title, 'Are you sure you want to terminate these instances?', function(button){
					if(button !== 'yes') return false;
					
					Ext.Msg.wait('Terminating selected instances', title);
					Ext.Ajax.request({
						url: 'amazon/terminate_instance',
						params: {
							instances: Ext.encode(instances)
						},
						success: function(response){
							response = Ext.decode(response.responseText);
							var s = response.success;
							Ext.Msg.alert(title, s ? success : response.error_message || error);
							Cloud.reload_instances_until_stable('running');
						},
						failure: function(){
							Ext.Msg.alert(title, error);
						}
					});
					return false;
				})
			}
		}]
	},
	bbar: {
		xtype: 'toolbar',
		items: ['->', {
			xtype: 'button',
			text: 'Refresh List',
			handler: function(){
				Cloud.get_instances('running').reload();
			}
		}]
	}
});

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
});

var terminated_instances = new Ext.grid.GridPanel({
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

Cloud.reload_instances_until_stable('running');