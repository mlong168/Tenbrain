var Load_balancers = function(){
	
	var registered_instances_store = new Ext.data.JsonStore({
		url: 'common/get_load_balanced_instances',
		root: 'instances',
		fields: [{name: 'id', type: 'int'}, {name: 'name', type: 'string'},
				{name: 'ip_address', type: 'string'}, {name: 'healthy', type: 'string'}, {name: 'health_message', type: 'string'}],
		baseParams: {
			lb_id: 0
		}
	});
	
	var show_lb_instances = function(lb_id){
		var record = lb_menu.selected_record,
			name = record.get('name'),
			id = record.get('id');
		
		lb_menu.hide();
		registered_instances_window.show().center().setTitle('Instances registered within the load balancer "' + name + '"');
		registered_instances_store.baseParams.lb_id = id;
		registered_instances_store.reload();
	};
	
	var xg = Ext.grid, checkbox_sm = new xg.CheckboxSelectionModel()
	var registered_instances_grid = new xg.GridPanel({
		id: 'lb_registered_instances',
		layout: 'fit',
		loadMask: true,
		store: registered_instances_store,
		sm: checkbox_sm,
		view: new xg.GridView({
			forceFit: true,
			emptyText: '<p style="text-align: center">No instances have been registered with this load balancer</p>'
		}),
		listeners: {
			activate: function(p){
				var store = p.getStore();
				if(store.lastOptions === null) store.load();
			}
		},
		cm: new xg.ColumnModel({
			defaultSortable: false,
			columns: [
				checkbox_sm,
				{header: "Name", dataIndex: 'name', width: 150},
				{header: "Healthy?", dataIndex: 'healthy', width: 100, renderer: function(value, metadata, record){
					var tpl = new Ext.XTemplate('<tpl for=".">sick <span ext:qtip="{health_message}" style="color:blue; text-decoration:underline">(why?)</span></tpl>');
					return value
						? 'healthy'
						: tpl.applyTemplate(record.data);
				}},
				{header: "IP Address", dataIndex: 'ip_address', width: 120}
			]
		}),
		tbar: {
			xtype: 'toolbar',
			items: [{
				xtype: 'button',
				text: 'Remove from load balancer',
				cls: 'x-btn-text-icon',
				iconCls: 'terminate',
				handler: function(){
					var selected = checkbox_sm.getSelections(), instances = [],
						title = 'Deregister Instances with load balancer',
						success = 'Selected instances have been deregistered successfully',
						error = 'A problem has occurred while deregistering selected instances';

					if(!checkbox_sm.getCount())
					{
						Ext.Msg.alert('Warning', 'Please select some instances to perform the action');
						return false;
					}

					for(var i = selected.length; i--;)
					{
						instances.push(selected[i].data.instance_id);
					}

					Ext.MessageBox.confirm(title, 'Are you sure you want deregister selected instances from load balancer?', function(button){
						if(button !== 'yes') return false;

						for(var i = selected.length; i--;)
						{
							instances.push(selected[i].data.id);
						}
						Ext.Msg.wait('The instances are being deregistered from the load balancer', 'Deregistering instances');
						Ext.Ajax.request({
							url: 'common/deregister_instances_from_lb',
							params: {
								lb_id: lb_menu.selected_record.get('id'),
								instances: Ext.encode(instances)
							},
							success: function(response){
								response = Ext.decode(response.responseText);
								var s = response.success;
								Ext.Msg.alert(title, s ? success : response.error_message || error);
								registered_instances_store.reload();
							},
							failure: function(){
								Ext.Msg.alert(title, error);
							}
						});
					});
				}
			}, '->', {
				xtype: 'button',
				text: 'Refresh List',
				cls: 'x-btn-text-icon',
				iconCls: 'restart',
				handler: function(){
					registered_instances_store.reload();
				}
			}]
		},
		ref_lb_name: null
	});

	var registered_instances_window = new Ext.Window({
		layout: 'fit',
		closeAction: 'hide',
		border: false,
		width: 700,
		height: 300,
		minWidth: 400,
		minHeight: 200,
		plain: 'true',
		items: registered_instances_grid,
		bodyStyle: 'padding:5px;',
		modal : true
	});
	
	var instances_to_register_store = new Ext.data.JsonStore({
		url: 'common/instances_for_load_balancing',
		root: 'instances',
		fields: [{name: 'id', type: 'int'}, {name: 'name', type: 'string'}, {name: 'address', type: 'string'}],
		baseParams: {
			provider: 'Amazon'
		}
	});

	var deploy_form = new Ext.FormPanel({
		id: 'lb_deploy_form',
		url: '/common/create_load_balancer',
		labelWidth: 60,
		baseCls: 'x-plain',
		monitorValid: true,
		autoHeight: true,
		buttonAlign: 'center',
		defaults: {
			xtype: 'textfield'
		},
		items: [{
			fieldLabel: 'Name',
			anchor: '100%',
			name: 'name',
			vtype: 'alphanum',
			emptyText: 'Type a load balancer name here',
			allowBlank: false
		}, {
			xtype: 'combo',
			fieldLabel: 'Provider',
			anchor: '100%',
			name: 'provider',
			allowBlank: false,
			store: new Ext.data.ArrayStore({
				fields: ['name'],
				data: [['Amazon'], ['GoGrid'], ['Rackspace']]
			}),
			displayField: 'name',
			valueField: 'name',
			mode: 'local',
			listeners: {
				select: function(box, record){
					var provider = record.data.name,
						servers_selection = Ext.getCmp('instance_to_register_within_lb');
					servers_selection.enable();
					if(instances_to_register_store.baseParams.provider !== provider) {
						servers_selection.reset();
						instances_to_register_store.setBaseParam('provider', provider);
					}
					if(provider === 'GoGrid') {
						Ext.getCmp('gogrid_lb_address').enable().show();
					} else {
						Ext.getCmp('gogrid_lb_address').disable().hide();
					}
				}
			}
		},  {
			id: 'gogrid_lb_address',
			xtype: 'combo',
			anchor: '100%',
			disabled: true,
			hidden: true,
			fieldLabel: 'IP address',
			allowBlank: false,
			vtype: 'IPAddress',
			store: new Ext.data.JsonStore({
				url: '/gogrid/get_free_addresses',
				successProperty: 'success',
				root: 'addresses',
				fields: ['address']
			}),
			mode: 'remote',
			name: 'address',
			displayField: 'address',
			hiddenName: 'address', // POST-var name
			valueField: 'address', // POST-var value
			autoSelect: true,
			forceSelection: true,
			typeAhead: true,
			triggerAction: 'all',
		}, {
			id: 'instance_to_register_within_lb',
			xtype: 'superboxselect',
			disabled: true,
			editable: false,
			allowBlank: false,
			msgTarget: 'under',
			allowAddNewData: false,
			fieldLabel: 'Servers',
			anchor: '100%',
			blankText: 'Please select one or more servers',
			emptyText: 'Select one or more servers',
			listEmptyText: 'No servers are available to be registered',
			resizable: true,
			name: 'instances[]',
			store: instances_to_register_store,
			mode: 'remote',
			displayField: 'name',
			displayFieldTpl: '{name} ({address})',
			valueField: 'id',
			triggerAction: 'all',
			forceFormValue: false
			// extraItemCls: 'x-tag',
		}],
		buttons: [{
			text: 'Proceed',
			formBind: true,
			handler: function(){
				var title = 'Create load balancer',
					success = 'Load balancer was created successfully',
					error = 'A problem has occured while creating a load balancer';
				
				deploy_window.hide();
				Ext.Msg.wait('Your load balancer is being created', title);
				deploy_form.getForm().submit({
					success: function(form, action){
						var s = action.result.success;
						Ext.Msg.alert(title, s ? success : action.result.error_message || error);
					},
					failure: function(form, action){
						Ext.Msg.alert(title, action.result.error_message || error);
					}
				});
			}
		}, {
			text: 'Cancel',
			handler: function(){
				deploy_window.hide();
			}
		}]
	});

	var deploy_window = new Ext.Window({
		title: 'Create load balancer',
		layout: 'fit',
		closeAction: 'hide',
		width: 350,
		minWidth: 300,
		// minHeight: 200,
		plain: 'true',
		items: deploy_form,
		bodyStyle: 'padding:5px;',
		modal : true
	});

	var lb_menu = new Ext.menu.Menu({
		id: 'load_balancers_menu',
		items: [{
			text: 'Actions',
			menu: {
				items: [{
					text: 'Register instances with load balancer',
					handler: function(){
						var grid = lb_menu.ref_grid,
							record = lb_menu.selected_record;
						
						lb_menu.hide();						
						Ext.Msg.Alert('This is to be completed till evening no matter what');
					}
				}, {
					text: 'Deregister instances from load balancer',
					handler: show_lb_instances
				}]
			}
		}, {
			text: 'Management',
			menu: {
				items: [{
					text: 'View instances registered within the load balancer',
					handler: show_lb_instances
				}, {
					text: 'Delete load balancer',
					handler: function(){
						var grid = lb_menu.ref_grid,
							id = lb_menu.selected_record.get('id'),
							provider = lb_menu.selected_record.get('provider').toLowerCase(),
							title = 'Delete load balancer',
							success = 'Load balancer was deleted successfully',
							error = 'A problem has occurred when deleting the load balancer';

						lb_menu.hide();
						Ext.MessageBox.confirm(title, 'Are you sure you want delete this load balancer?', function(button){
							if(button !== 'yes') return false;

							Ext.Msg.wait('The load balancer is being deleted', title);
							Ext.Ajax.request({
								url: provider + '/delete_load_balancer',
								params: {
									id: id,
								},
								success: function(response){
									response = Ext.decode(response.responseText);
									var s = response.success;
									Ext.Msg.alert(title, s ? success : response.error_message || error, function(){
										store.reload();
									});
								},
								failure: function(){
									Ext.Msg.alert(title, error);
								}
							});
						});
					}
				}]
			}
		}],
		ref_grid: null,
		selected_record: null
	});
	
	var store = function(){
		var record = Ext.data.Record.create([
			'id',
			'name',
			'provider',
			'dns_name',
			'state'
		]);
		return new Ext.data.Store({
			url: '/common/list_load_balancers',
			reader: new Ext.data.JsonReader({
				root: 'load_balancers',
				successProperty: 'success',
				idProperty: 'id'
			}, record)
		});
	}();
	
	var reload_until_stable = function(){
		var init_timeout = 10000, interval = init_timeout, minimum_interval = 5000, step = 1000;
		return function(state, callback){
			state = state || 'On';
				
			if(this !== reload_until_stable)
			{
				interval = init_timeout;
			}
			
			store.reload({
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

	var grid = new xg.GridPanel({
		id: 'load_balancers-panel',
		layout: 'fit',
		title: 'Load Balancers',
		store: store,
		viewConfig: {
			forceFit: true,
			emptyText: '<p style="text-align: center">No load balancers have been created</p>'
		},
		listeners: {
			rowcontextmenu: function (grid, id, e) {
				e.preventDefault();
				lb_menu.ref_grid = this;
				lb_menu.selected_record = this.getStore().getAt(id);
				lb_menu.showAt(e.getXY());
			},
			activate: function(p){
				var store = p.getStore();
				if(store.lastOptions === null) store.load();
			}
		},
		tbar: {
			xtype: 'toolbar',
			items: [{
				xtype: 'button',
				text: 'Deploy a load balancer',
				cls: 'x-btn-text-icon',
				iconCls: 'start',
				handler: function(){
					var form = deploy_form.getForm().reset();					
					form.findField('gogrid_lb_address').disable().hide();
					form.findField('instance_to_register_within_lb').disable();
					deploy_window.show().center();
				}
			}, '->', {
				xtype: 'button',
				text: 'Refresh List',
				cls: 'x-btn-text-icon',
				iconCls: 'restart',
				handler: function(){
					store.reload();
				}
			}]
		},
		colModel: new xg.ColumnModel({
			columns: [
				{header: "Name", dataIndex: 'name', width: 130, renderer: function(value, metadata, record){
					if(record.data.state !== 'On') metadata.css = 'grid-loader';
					return value;
				}},
				{header: "Provider", dataIndex: 'provider', width: 100},
				{header: "State", dataIndex: 'state', width: 60},
				{header: "DNS Name", dataIndex: 'dns_name', width: 130, renderer: function(value){
					return '<a target="_blank" href="http://' + value + '/">' + value + '</a>';
				}}
			]
		})
	});
	
	return {
		get_grid: function(){
			return grid;
		}
	}
}();