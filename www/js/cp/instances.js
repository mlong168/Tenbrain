// Ext.Compat.showErrors = true;
var Helpers = function(){
	return {
		link_wrapper: function(link){
			return '<a target="_blank" href="http://' + link + '/">' + link + '</a>';
		},
		first_time_loader: function(p){
			var store = p.getStore(),
				elem = p.getEl();
			if(store.last() === undefined)
			{
				elem.mask('Loading, please wait ...');
				store.load({
					callback: function(){
						elem.unmask();
					}
				});
			}
			return false;
		}
	}
}();

var Instances = function(){
	var states = ['running', 'terminated', 'stopped'];
	var store = function(){
		var stores = {};
		Ext.define('Server', {
			extend: 'Ext.data.Model',
			fields: [
				{name: 'id',			type: 'string'},
				{name: 'name',			type: 'string'},
				{name: 'dns_name',		type: 'string'},
				{name: 'ip_address',	type: 'string'},
				{name: 'image_id',		type: 'string'},
				{name: 'state',			type: 'string'},
				{name: 'type',			type: 'string'},
				{name: 'provider',		type: 'string'}
			]
		});
		for(var i = states.length; i--;)
		{
			stores[states[i]] = Ext.create('Ext.data.Store', {
				model: 'Server',
				proxy: {
					type: 'ajax',
					url: '/common/list_instances/state/' + states[i],
					reader: {
						type: 'json',
						root: 'instances'
					}
				}
			});
		}
		return stores;
	}();
	
	var reload_until_stable = function(){
		var init_timeout = 10000, interval = init_timeout, minimum_interval = 5000, step = 1000;
		return function(state, callback){
			state = state || 'running';
				
			if(this !== reload_until_stable)
			{
				interval = init_timeout;
			}
			
			store[state].load({
				callback: function(r){
					for(var i = r.length; i--;)
					{
						if(r[i].data.state !== state)
						{
							setTimeout(function(){
								reload_until_stable.call(reload_until_stable, state, callback);
							}, interval);
							if(interval > minimum_interval && interval - step > 0) interval -= step;
							return false;
						}
					}
					// being here means everything is pretty stable, so we can execute our callback
					if(typeof callback === 'function') callback();
				}
			});
			return false;
		};
	}();

	// menus:
	var instances_menu_handler = function(item){
		var id = item.id, action = id.substr(0, id.indexOf('_')),
			parent_menu = item.up('menu').parentMenu,
			record = parent_menu.selected_record,
			instance_id = record.get('id'),
			titles = {
				reboot: 'Reboot Server',
				stop: 'Stop Server',
				terminate: 'Terminate Server',
				start: 'Start Server'
			}, title = titles[action],
			success = 'Operation Successfull',
			error = 'A problem has occurred while processing your request';
			
		Ext.MessageBox.confirm(title, 'Are you sure you want to proceed?', function(button){
			if(button !== 'yes') return false;
			Ext.Msg.wait('Processing your request', title);
			Ext.Ajax.request({
				url: 'common/' + action + '_instances',
				params: { instances: Ext.encode([instance_id]) },
				success: function(response){
					response = Ext.decode(response.responseText);
					Ext.Msg.alert(title, response.success ? success : response.error_message || error);
					var stopped = parent_menu.id === 'stopped_instances_menu';
					reload_until_stable(stopped ? 'stopped' : 'running', function(){
						if(action === 'terminate') {
							store.terminated.load();
						} else if(action === 'stop') {
							store.stopped.load();
						} else if(action === 'start') {
							reload_until_stable('running');
							store.stopped.load();
						}
					});
				},
				failure: function(){
					Ext.Msg.alert(title, error);
				}
			});
		});
	};
	
	Ext.define('Server_types', {
		extend: 'Ext.data.Model',
		fields: [
			{name: 'value',		type: 'string'},
			{name: 'name',		type: 'string'},
			{name: 'available',	type: 'boolean'},
			{name: 'reason',	type: 'string'}
		]
	});
	
	var modify_form = Ext.create('Ext.form.Panel', {
		url: '/common/modify_server',
		baseCls: 'x-plain',
		height: 58,
		minHeight: 58,
		pollForChanges: true,

		items: [{
			xtype: 'hidden',
			name: 'server_id'
		}, {
			xtype: 'combo',
			anchor: '100%',
			fieldLabel: 'Server Type',
			labelWidth: 80,
			allowBlank: false,
			editable: false,
			store: new Ext.data.JsonStore({
				model: 'Server_types',
				proxy: {
					type: 'ajax',
					url: '/common/get_available_server_types',
					reader: {
						type: 'json',
						root: 'types'
					},
					extraParams: {provider: ''}
				}
			}),
			queryMode: 'remote',
			displayField: 'name',
			name: 'server_type', // POST-var name
			valueField: 'value', // POST-var value
			emptyText: 'Select type',
			tpl: '<tpl for="."><div ext:qtip="{reason}" class="x-combo-list-item">{name}</div></tpl>',
			forceSelection: true,
			triggerAction: 'all',
			listeners: {
				beforequery: function(qe){
					delete qe.combo.lastQuery;
				},
				beforeselect: function(combo, record){
					return record.data.available; // false if not selectable
				}
			}
		}],

		buttons: [{
			text: 'Proceed',
			formBind: true,
			handler: function(){
				var title = 'Modify server',
					success = 'Server has been modified successfully',
					error = 'A problem has occurred while modifying the server';
					
				this.up('window').hide();
				this.up('form').submit({
					waitTitle: title,
					waitMsg: 'Modifying server',
					success: function(form, action){
						Ext.Msg.alert(title, action.result.success ? success : response.error_message || error);
						Instances.reload_until_stable('stopped');
						Instances.reload_until_stable('running');
					},
					failure: function(form, action){
						Ext.Msg.alert(title, error);
					}
				});
			}
		}, {
			text: 'Cancel',
			handler: function(){
				this.up('window').hide();
			}
		}]
	});
	
	Ext.create('Ext.window.Window', {
		title: 'Modify server',
		layout: 'fit',
		width: 300,
		minWidth: 300,
		closeAction: 'hide',
		items: modify_form,
		modal : true,
		plain: 'true',
		bodyStyle: 'padding:5px;'
	});
	
	var web_console = Ext.create('Ext.window.Window', {
		title: 'Web SSH console',
		layout: 'fit',
		width: 687,
		height: 465,
		// resizable: false,
		closeAction: 'hide',
		modal : true,
		plain: 'true',
		bodyStyle: 'padding:5px;'
	});
	
	
	var instances_menu = new Ext.menu.Menu({
		id: 'running_instances_menu',
		items: [{
			text: 'Connect',
			menu: {
				items: [{
					text: 'Open web console',
					handler: function(){
						var record = instances_menu.selected_record,
							server_id = record.get('id');
						Ext.Msg.wait('Connecting to your server');
						Ext.Ajax.request({
							url: 'common/connection_params',
							params: {server_id: server_id},
							failure: function(){
								Ext.Msg.alert('An error has occurred');
							},
							success: function(response){
								var response = Ext.decode(response.responseText),
									params = response.connection_params,
									iframes = web_console.items,
									console_url = 'https://ec2-50-19-43-161.compute-1.amazonaws.com/ajaxterm/?';
								if(!response.success)
								{
									Ext.Msg.alert('An error has occurred');
									return false;
								}
								Ext.Msg.hide();
								console_url += 'hostname=' + params.hostname
									+ '&port=' + params.port
									+ '&provider=' + params.provider
									+ '&login_user=' + params.login_user
									+ '&key_name=' + params.username || 'default';
								if(iframes.getCount() !== 0)
								{
									iframes.get(0).destroy;
									iframes.removeAll();
								}
								iframes.add(Ext.create('Ext.Component', {
									autoEl: {
										tag: 'iframe',
										frameborder: 0,
										src: console_url
									}
								}))
								web_console.doLayout().show().center();
							}
						})
						
					}
				}, {
					text: 'View connection info',
					handler: function(){
						var record = instances_menu.selected_record,
							server_id = record.get('id'),
							title = 'Server connection information',
							error = 'An error has occurred';
						
						Ext.Msg.wait('Retrieving your server\'s connection info', title);
						Ext.Ajax.request({
							url: 'common/connection_info',
							params: {server_id: server_id},
							success: function(response){
								response = Ext.decode(response.responseText);
								var s = response.success;
								Ext.Msg.alert(title, s
									? response.connection_message
									: response.error_message || error);
							},
							failure: function(){
								Ext.Msg.alert(title, error);
							}
						});
					}
				}, {
					text: 'Download key file',
					handler: function(){
						if(this.up('menu').parentMenu.selected_record.get('provider') !== 'Amazon') return false;
						Ext.Msg.confirm('Download key pair', 'Do you want to download your key pair to connect to your server via SSH?', function(button){
							if(button === 'yes') document.location.assign('/amazon/download_private_key');
						});
					}
				}]
			}
		}, '-', {
			text: 'Actions',
			menu: {
				items: [{
					text: 'Reboot',
					id: 'reboot_instance',
					handler: instances_menu_handler
				}, {
					text: 'Stop',
					id: 'stop_instance',
					handler: instances_menu_handler
				}, {
					id: 'terminate_instance',
					text: 'Terminate',
					handler: instances_menu_handler
				}]
			}
		}, '-', {
			text: 'Management',
			menu: {
				items: [{
					text: 'Create Backup',
					handler: function(){
						var record = instances_menu.selected_record,
							id = record.get('id');
						instances_menu.hide();
						Snapshots.create(id);
					}
				}, {
					text: 'View Backups',
					handler: function(){
						var record = instances_menu.selected_record,
							id = record.get('id'),
							name = record.get('name');
						instances_menu.hide();
						Snapshots.show_instance_snapshots(id, name);
					}
				}, {
					text: 'Modify server',
					handler: function(){
						var record = instances_menu.selected_record,
							form = modify_form.getForm();
						instances_menu.hide();		
						if(record.get('provider') === 'Amazon')
						{
							Ext.Msg.show({
							   title: 'Error',
							   msg: 'For modifying Amazon servers they should be stopped. Please stop the server and try again',
							   buttons: Ext.Msg.OK,
							   icon: Ext.Msg.ERROR
							});
							return false;
						}
						form.reset().setValues({server_id: record.get('id')});
						form.findField('server_type').store.proxy.extraParams.provider = record.get('provider');
						modify_form.up('window').show().center();
					}
				}]
			}
		}],
		selected_record: null
	});

	// layouts:
	var grids = { }
	var sm_running = Ext.create('Ext.selection.CheckboxModel');
	grids.running = Ext.create('Ext.grid.Panel', {
		id: 'running_instances-panel',
		title: 'Your currently running servers',
		layout: 'fit',
		store: store.running,
		selModel: sm_running,
		forceFit: true,
		border: false,
		viewConfig: {
			emptyText: '<p style="text-align:center">You have not launched any server so far</p>',
			loadingText: 'Loading the list of your running servers'
		},
		columnLines: true,
		listeners: {
			itemcontextmenu: function (view, record, item, index, e) {
				var menu = instances_menu;
				e.preventDefault();
				menu.selected_record = record;
				menu.showAt(e.getXY());
			}
		},
		columns: [
			{text: "Name", dataIndex: 'name', width: 150, renderer: function(value, metadata, record){
				if(record.data.state !== 'running') metadata.css = 'grid-loader';
				return value;
			}},
			{text: "Link to server root", dataIndex: 'dns_name', width: 250, renderer: Helpers.link_wrapper},
			{text: "IP Address", dataIndex: 'ip_address', width: 120},
			{text: "State", dataIndex: 'state', width: 60},
			{text: "Type", dataIndex: 'type', width: 100}
		],
		tbar: Ext.create('Ext.toolbar.Toolbar', {
			items: [{
				xtype: 'button',
				text: 'Reboot',
				cls: 'x-btn-text-icon',
				iconCls: 'restart',
				handler: function(){
					var selected = sm_running.getSelection(),
						instances = [],
						title = 'Reboot Servers',
						success = 'Selected servers have been rebooted successfully',
						error = 'A problem has occurred while rebooting servers';
						
					if(selected.length === 0)
					{
						Ext.Msg.alert('Warning', 'Please select some servers to perform the action');
						return false;
					}
					
					for(var i = selected.length; i--;)
					{
						instances.push(selected[i].data.id);
					}
					
					Ext.MessageBox.confirm(title, 'Are you sure you want to reboot these servers?', function(button){
						if(button !== 'yes') return false;
					
						Ext.Msg.wait('Rebooting selected servers', title);
						Ext.Ajax.request({
							url: 'common/reboot_instances',
							params: {
								instances: Ext.encode(instances)
							},
							success: function(response){
								response = Ext.decode(response.responseText);
								var s = response.success;
								Ext.Msg.alert(title, s ? success : response.error_message || error);
								store.running.load();
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
				iconCls: 'stop',
				handler: function(){
					var selected = sm_running.getSelection(),
						instances = [],
						title = 'Stop servers',
						success = 'Selected servers have been stopped successfully',
						error = 'A problem has occurred while stopping the servers';
						
					if(selected.length === 0)
					{
						Ext.Msg.alert('Warning', 'Please select some servers to perform the action');
						return false;
					}
					
					for(var i = selected.length; i--;)
					{
						instances.push(selected[i].data.id);
					}
					
					Ext.MessageBox.confirm(title, 'Are you sure you want to stop these servers?', function(button){
						if(button !== 'yes') return false;
					
						Ext.Msg.wait('Stopping selected servers', title);
						Ext.Ajax.request({
							url: 'common/stop_instances',
							params: {
								instances: Ext.encode(instances)
							},
							success: function(response){
								response = Ext.decode(response.responseText);
								var s = response.success;
								Ext.Msg.alert(title, s ? success : response.error_message || error);
								reload_until_stable('running', function(){
									store.stopped.load();
								});
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
				iconCls: 'terminate',
				handler: function(){
					var selected = sm_running.getSelection(),
						instances = [],
						title = 'Terminate servers',
						success = 'Selected servers have been terminated successfully',
						error = 'A problem has occurred while terminating servers';
						
					if(selected.length === 0)
					{
						Ext.Msg.alert('Warning', 'Please select some servers to perform the action');
						return false;
					}
					
					for(var i = selected.length; i--;)
					{
						instances.push(selected[i].data.id);
					}
					
					Ext.MessageBox.confirm(title, 'Are you sure you want to terminate these servers?', function(button){
						if(button !== 'yes') return false;
						
						Ext.Msg.wait('Terminating selected servers', title);
						Ext.Ajax.request({
							url: 'common/terminate_instances',
							params: {
								instances: Ext.encode(instances)
							},
							success: function(response){
								response = Ext.decode(response.responseText);
								var s = response.success;
								Ext.Msg.alert(title, s ? success : response.error_message || error);
								reload_until_stable('running', function(){
									store.terminated.load();
								});
							},
							failure: function(){
								Ext.Msg.alert(title, error);
							}
						});
						return false;
					})
				}
			}]
		}),
		dockedItems: [{
			xtype: 'toolbar',
			dock: 'bottom',
			items: ['->', {
				xtype: 'button',
				text: 'Refresh List',
				cls: 'x-btn-text-icon',
				iconCls: 'restart',
				handler: function(){
					store.running.load();
				}
			}]
		}],
	});
	
	var stopped_menu = new Ext.menu.Menu({
		id: 'stopped_instances_menu',
		items: [{
			text: 'Management',
			menu: {
				items: [{
					text: 'Create backup',
					handler: function(){
						var id = stopped_menu.selected_record.get('id');
						stopped_menu.hide();
						Snapshots.create(id);
					}
				}, {
					text: 'View backups',
					handler: function(){
						var record = stopped_menu.selected_record,
							id = record.get('id'),
							name = record.get('name');
						stopped_menu.hide();
						Snapshots.show_instance_snapshots(id, name);
					}
				}]
			}
		}, '-', {
			text: 'Actions',
			menu: {
				items: [{
					text: 'Start server',
					id: 'start_instance',
					handler: instances_menu_handler
				}, {
					id: 'terminate_instance',
					text: 'Terminate',
					handler: instances_menu_handler
				}, {
					text: 'Modify server',
					handler: function(){
						var record = stopped_menu.selected_record,
							form = modify_form.getForm();

						stopped_menu.hide();
						form.reset().setValues({server_id: record.get('id')});
						form.findField('server_type').store.proxy.extraParams.provider = record.get('provider');
						modify_form.up('window').show().center();
					}
				}]
			}
		}],
		selected_record: null
	});
	
	var sm_stopped = Ext.create('Ext.selection.CheckboxModel');
	grids.stopped = Ext.create('Ext.grid.Panel', {
		id: 'stopped_instances-panel',
		title: 'Servers that have been stopped',
		layout: 'fit',
		forceFit: true,
		border: false,
		columnLines: true,
		
		store: store.stopped,
		selModel: sm_stopped,
		
		viewConfig: {
			emptyText: '<p style="text-align: center">You do not currently have any stopped server</p>'
		},
		columns: [
			{text: "Name", dataIndex: 'name', width: 150, renderer: function(value, metadata, record){
				if(record.data.state !== 'stopped') metadata.css = 'grid-loader';
				return value;
			}},
			{text: "IP Address", dataIndex: 'ip_address', width: 120},
			{text: "State", dataIndex: 'state', width: 100},
			{text: "Type", dataIndex: 'type', width: 100}
		],
		listeners: {
			itemcontextmenu: function (view, record, item, index, e) {
				var menu = stopped_menu;
				e.preventDefault();
				menu.selected_record = record;
				menu.showAt(e.getXY());
			},
			activate: Helpers.first_time_loader
		},
		tbar: {
			xtype: 'toolbar',
			items: [{
				xtype: 'button',
				text: 'Start',
				cls: 'x-btn-text-icon',
				iconCls: 'start',
				handler: function(){
					var selected = sm_stopped.getSelection(),
						instances = [],
						title = 'Start Servers',
						success = 'Selected servers have been started successfully',
						error = 'A problem has occurred while starting selected servers';
						
					if(selected.length === 0)
					{
						Ext.Msg.alert('Warning', 'Please select some servers to perform the action');
						return false;
					}
					
					for(var i = selected.length; i--;)
					{
						instances.push(selected[i].data.id);
					}
					
					Ext.MessageBox.confirm(title, 'Are you sure you want to start these servers?', function(button){
						if(button !== 'yes') return false;
					
						Ext.Msg.wait('Starting selected servers', title);
						Ext.Ajax.request({
							url: 'common/start_instances',
							params: {
								instances: Ext.encode(instances)
							},
							success: function(response){
								response = Ext.decode(response.responseText);
								var s = response.success;
								Ext.Msg.alert(title, s ? success : response.error_message || error);
								reload_until_stable('stopped', function(){
									store.running.load();
								});
							},
							failure: function(){
								Ext.Msg.alert(title, error);
							}
						});
					});
				}
			}]
		},
		bbar: {
			xtype: 'toolbar',
			items: ['->', {
				xtype: 'button',
				text: 'Refresh List',
				cls: 'x-btn-text-icon',
				iconCls: 'restart',
				handler: function(){
					store.stopped.load();
				}
			}]
		}
	});

	grids.terminated = Ext.create('Ext.grid.Panel', {
		id: 'terminated_instances-panel',
		title: 'Servers that have previously been terminated',
		layout: 'fit',
		border: false,
		forceFit: true,
		store: store.terminated,
		bbar: {
			xtype: 'toolbar',
			items: ['->', {
				xtype: 'button',
				text: 'Refresh List',
				cls: 'x-btn-text-icon',
				iconCls: 'restart',
				handler: function(){
					store.terminated.load();
				}
			}]
		},
		viewConfig: {
			emptyText: '<p style="text-align: center">You do not have any terminated server so far</p>'
		},
		columnLines: true,
		listeners: {
			activate: Helpers.first_time_loader
		},
		columns: [
			{text: "Name", dataIndex: 'name', width: 150},
			{text: "Provider", dataIndex: 'provider', width: 80},
			{text: "State", dataIndex: 'state', width: 100},
			{text: "Type", dataIndex: 'type', width: 100}
		]
	});
	
	return {
		get_panel: function(state){ return grids[state] },
		reload_instances: function(state){	// reload all if state is not specified
			if(typeof state === 'string' && states.indexOf(state) !== -1)
			{
				store[state].reload();
			}
			else
			{
				for(var i = store.length; i--;)
				{
					store[i].reload();
				}
			}
		},
		reload_until_stable: reload_until_stable
	};
}();

Instances.reload_until_stable('running');