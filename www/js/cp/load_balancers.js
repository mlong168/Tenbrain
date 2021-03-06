var Load_balancers = function() {

	Ext.define('Load_balanced_instances', {
		extend: 'Ext.data.Model',
		fields: [{
			name: 'id',
			type: 'int'
		},{
			name: 'name',
			type: 'string'
		},{
			name: 'ip_address',
			type: 'string'
		},{
			name: 'healthy',
			type: 'boolean'
		},{
			name: 'health_message',
			type: 'string'
		}
		]
	});

	var registered_instances_store = Ext.create('Ext.data.Store', {
		model: 'Load_balanced_instances',
		proxy: {
			type: 'ajax',
			url: 'common/get_load_balanced_instances',
			reader: {
				type: 'json',
				root: 'instances'
			},
			extraParams: {
				lb_id: 0
			}
		}
	});

	var show_lb_instances = function(lb_id) {
		var record = lb_menu.selected_record,
		name = record.get('name'),
		id = record.get('id');

		lb_menu.hide();
		registered_instances_window.show().center().setTitle('Servers registered within the load balancer "' + name + '"');
		registered_instances_store.getProxy().baseParams.lb_id = id;
		registered_instances_store.load();
	};
	var checkbox_sm = Ext.create('Ext.selection.CheckboxModel');
	var registered_instances_grid = Ext.create('Ext.grid.Panel', {
		id: 'lb_registered_instances',
		layout: 'fit',
		loadMask: true,
		store: registered_instances_store,
		sm: checkbox_sm,
		forceFit: true,
		emptyText: '<p style="text-align: center">No servers have been registered with this load balancer</p>',
		listeners: {
			activate: function(p) {
				var store = p.getStore();
				if(store.lastOptions === null)
					store.load();
			}
		},
		columns: [{
			text: "Name",
			dataIndex: 'name',
			width: 150
		},{
			text: "Healthy?",
			dataIndex: 'healthy',
			width: 100,
			renderer: function(value, metadata, record) {
				console.log(healthy)
				var healthy = value.toString() !== 'false',
				tpl = new Ext.XTemplate('<tpl for=".">sick <span ext:qtip="{health_message}" style="color:blue; text-decoration:underline">(why?)</span></tpl>');
				record.data.healthy = healthy;
				return healthy ? 'healthy' : tpl.applyTemplate(record.data);
			}
		},{
			text: "IP Address",
			dataIndex: 'ip_address',
			width: 120
		}
		],
		tbar: {
			xtype: 'toolbar',
			items: [{
				xtype: 'button',
				text: 'Remove from load balancer',
				cls: 'x-btn-text-icon',
				iconCls: 'terminate',
				handler: function() {
					var selected = checkbox_sm.getSelections(), instances = [],
					title = 'Deregister servers with load balancer',
					success = 'Selected servers have been deregistered successfully',
					error = 'A problem has occurred while deregistering selected servers';

					if(!checkbox_sm.getCount()) {
						Ext.Msg.alert('Warning', 'Please select some servers to perform the action');
						return false;
					}

					for(var i = selected.length; i--;) {
						instances.push(selected[i].data.instance_id);
					}

					Ext.MessageBox.confirm(title, 'Are you sure you want deregister selected servers from load balancer?', function(button) {
						if(button !== 'yes')
							return false;

						for(var i = selected.length; i--;) {
							instances.push(selected[i].data.id);
						}
						Ext.Msg.wait('Servers are being deregistered from the load balancer', 'Deregistering servers');
						Ext.Ajax.request({
							url: 'common/deregister_instances_from_lb',
							params: {
								lb_id: lb_menu.selected_record.get('id'),
								instances: Ext.encode(instances)
							},
							success: function(response) {
								response = Ext.decode(response.responseText);
								var s = response.success;
								Ext.Msg.alert(title, s ? success : response.error_message || error);
								registered_instances_store.reload();
							},
							failure: function() {
								Ext.Msg.alert(title, error);
							}
						});
					});
				}
			}, '->',{
				xtype: 'button',
				text: 'Refresh List',
				cls: 'x-btn-text-icon',
				iconCls: 'restart',
				handler: function() {
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

	Ext.define('Servers_to_register', {
		extend: 'Ext.data.Model',
		fields: [{
			name: 'id',
			type: 'string'
		},{
			name: 'name',
			type: 'string'
		}	]
	});

	var instances_to_register_store = Ext.create('Ext.data.Store', {
		model: 'Servers_to_register',
		proxy: {
			type: 'ajax',
			url: 'common/get_servers_for_load_balancing',
			reader: {
				type: 'json',
				root: 'servers'
			},
			extraParams: {
				provider: 'Amazon'
			}
		}
	});

	// Ext.onReady(function(){
	// deploy_form.up('window').show();
	// })

	Ext.define('Provider', {
		extend: 'Ext.data.Model',
		fields: [{
			type: 'string',
			name: 'name'
		}]
	});

	var deploy_form = Ext.create('Ext.form.Panel', {
		id: 'lb_deploy_form',
		url: '/common/create_load_balancer',
		frame: true,
		border: false,
		height: 285,
		minHeight: 285,
		pollForChanges: true,
		baseCls: 'x-plain',
		defaults: {
			xtype: 'combo',
			anchor: '100%',
			labelWidth: 70,
			allowBlank: false
		},
		items: [{
			xtype: 'textfield',
			fieldLabel: 'Name',
			name: 'name',
			vtype: 'alphanum',
			emptyText: 'Type a load balancer name here'
		},{
			fieldLabel: 'Provider',
			name: 'provider',
			store: new Ext.create('Ext.data.Store', {
				model: 'Provider',
				data: [{
					name: 'Amazon'
				},{
					name: 'GoGrid'
				},{
					name: 'Rackspace'
				}]
			}),
			displayField: 'name',
			valueField: 'name',
			queryMode: 'local',
			listeners: {
				select: function(field, value) {
					var panel = this.up('form'),
					form = panel.getForm(),
					servers_selection = form.findField('instances[]'),
					gg_address = form.findField('address'),
					provider = field.lastValue,
					is_gogrid = provider === 'GoGrid';

					servers_selection.setDisabled(false);
					servers_selection.setLoading(true);
					if(instances_to_register_store.proxy.extraParams.provider !== provider) {
						instances_to_register_store.proxy.extraParams.provider = provider;
					}
					instances_to_register_store.load({
						callback: function() {
							servers_selection.setLoading(false);
							servers_selection.refresh()
						}
					})
					
					gg_address.setVisible(is_gogrid).setDisabled(!is_gogrid);
					panel.setHeight(is_gogrid ? 315 : 285);
				}
			}
		},{
			id: 'gogrid_lb_address',
			disabled: true,
			hidden: true,
			fieldLabel: 'IP address',
			vtype: 'IPAddress',
			store: Ext.create('Ext.data.Store', {
				model: 'Gogrid_ip_address',
				proxy: {
					type: 'ajax',
					url: '/gogrid/get_free_addresses',
					reader: {
						type: 'json',
						root: 'addresses'
					}
				}
			}),
			queryMode: 'remote',
			name: 'address',
			displayField: 'address',
			valueField: 'address', // POST-var value
			autoSelect: true,
			forceSelection: true,
			triggerAction: 'all',
		},{
			xtype: 'itemselector',
			name: 'instances[]',
			store: instances_to_register_store,
			displayField: 'name',
			valueField: 'id',
			value: [],
			buttons: ['add', 'remove'],
			buttonsText: {
				add: 'Add server to load balancer',
				remove: 'Remove from the list'
			}
			// used in al old superboxselect:
			// displayFieldTpl: '{name} ({address})'
		}
		],
		buttons: [{
			text: 'Proceed',
			formBind: true,
			handler: function() {
				var title = 'Create load balancer',
				success = 'Load balancer was created successfully',
				error = 'A problem has occured while creating a load balancer';

				this.up('window').hide();
				this.up('form').getForm().submit({
					waitTitle: title,
					waitMsg: 'Your load balancer is being created',
					success: function(form, action) {
						var s = action.result.success;
						Ext.Msg.alert(title, s ? success : action.result.error_message || error);
						reload_until_stable();
					},
					failure: function(form, action) {
						Ext.Msg.alert(title, action.result.error_message || error);
					}
				});
			}
		},{
			text: 'Cancel',
			handler: function() {
				this.up('window').hide();
			}
		}]
	});

	Ext.create('Ext.window.Window', {
		title: 'Create load balancer',
		layout: 'fit',
		closeAction: 'hide',
		width: 500,
		minWidth: 500,
		plain: 'true',
		items: deploy_form,
		bodyStyle: 'padding:5px;',
		modal : true,
		listeners: {
			show: function(){
				var s =  this.down('form').getForm().findField('instances[]');
				s.setDisabled(true);
				s.clearAll();
			}
		}
	});

	var instances_to_register_within_lb_store = Ext.create('Ext.data.Store', {
		model: 'Servers_to_register',
		proxy: {
			type: 'ajax',
			url: 'common/instances_for_registering_within_lb',
			reader: {
				type: 'json',
				root: 'instances'
			},
			extraParams: {
				lb_id: 0
			}
		}
	});

	var register_form = Ext.create('Ext.form.Panel', {
		url: '/common/register_instances_within_load_balancer',
		labelWidth: 50,
		baseCls: 'x-plain',
		items: [{
			xtype: 'hidden',
			name: 'lb_id'
		},{
			// xtype: 'superboxselect',
			xtype: 'combo',
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
			store: instances_to_register_within_lb_store,
			mode: 'remote',
			displayField: 'name',
			displayFieldTpl: '{name} ({address})',
			valueField: 'id',
			triggerAction: 'all',
			forceFormValue: false
			// extraItemCls: 'x-tag',
		}],
		buttons: [{
			text: 'Register',
			formBind: true,
			handler: function() {
				var title = 'Register servers within load balancer',
				success = 'Selected servers have been registered successfully',
				error = 'A problem has occured while registering your servers';

				this.up('window').hide();
				this.up('form').getForm().submit({
					waitTitle: title,
					waitMsg:'Servers are registering',
					success: function(form, action) {
						var s = action.result.success;
						Ext.Msg.alert(title, s ? success : action.result.error_message || error);
					},
					failure: function(form, action) {
						Ext.Msg.alert(title, action.result.error_message || error);
					}
				});
			}
		},{
			text: 'Cancel',
			handler: function() {
				this.up('window').hide();
			}
		}]
	});

	var register_window = Ext.create('Ext.window.Window', {
		title: 'Register instances within load balancer',
		layout: 'fit',
		closeAction: 'hide',
		width: 400,
		minWidth: 200,
		// minHeight: 200,
		plain: 'true',
		items: register_form,
		bodyStyle: 'padding:5px;',
		modal : true
	});

	var lb_menu = new Ext.menu.Menu({
		id: 'load_balancers_menu',
		items: [{
			text: 'Actions',
			menu: {
				items: [{
					text: 'Register servers with load balancer',
					handler: function() {
						var record = lb_menu.selected_record,
						name = record.get('name'),
						id = record.get('id'),
						form = register_form.getForm();

						lb_menu.hide();
						form.reset().setValues({
							lb_id: id
						});
						register_window.show().center().setTitle('Servers to register within the load balancer "' + name + '"');
						instances_to_register_within_lb_store.baseParams.lb_id = id;
						register_window.show().center();
					}
				},{
					text: 'Deregister servers from load balancer',
					handler: show_lb_instances
				}]
			}
		},{
			text: 'Management',
			menu: {
				items: [{
					text: 'View servers registered within the load balancer',
					handler: show_lb_instances
				},{
					text: 'Delete load balancer',
					handler: function() {
						var id = lb_menu.selected_record.get('id'),
							title = 'Delete load balancer',
							success = 'Load balancer was deleted successfully',
							error = 'A problem has occurred when deleting the load balancer';

						lb_menu.hide();
						Ext.MessageBox.confirm(title, 'Are you sure you want delete this load balancer?', function(button) {
							if(button !== 'yes')
								return false;

							Ext.Msg.wait('The load balancer is being deleted', title);
							Ext.Ajax.request({
								url: 'common/delete_load_balancer',
								params: {
									lb_id: id,
								},
								success: function(response) {
									response = Ext.decode(response.responseText);
									var s = response.success;
									Ext.Msg.alert(title, s ? success : response.error_message || error, function() {
										store.reload();
									});
								},
								failure: function() {
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

	Ext.define('Load_balancer', {
		extend: 'Ext.data.Model',
		fields: [{
			name: 'id',
			type: 'string'
		},{
			name: 'name',
			type: 'string'
		},{
			name: 'provider',
			type: 'string'
		},{
			name: 'dns_name',
			type: 'string'
		},{
			name: 'state',
			type: 'string'
		}
		]
	});

	var store = Ext.create('Ext.data.Store', {
		model: 'Load_balancer',
		proxy: {
			type: 'ajax',
			url: '/common/list_load_balancers',
			reader: {
				type: 'json',
				root: 'balancers'
			}
		}
	});

	var reload_until_stable = function() {
		var init_timeout = 10000, interval = init_timeout, minimum_interval = 5000, step = 1000;
		return function(state, callback) {
			state = state || 'On';

			if(this !== reload_until_stable) {
				interval = init_timeout;
			}

			store.load({
				callback: function(r) {
					for(var i = r.length; i--;) {
						if(r[i].data.state !== state) {
							setTimeout( function() {
								reload_until_stable.call(reload_until_stable, state, callback);
							}, interval);
							if(interval > minimum_interval && interval - step > 0)
								interval -= step;
							return false;
						}
					}
					// being here means everything is pretty stable, so we can execute our callback
					if(typeof callback === 'function')
						callback();
				}
			});
			return false;
		};
	}();
	return {
		panels: {
			balancers: {
				xtype: 'grid',
				id: 'load_balancers-panel',
				title: 'Load Balancers',
				store: store,
				viewConfig: {
					emptyText: '<p style="text-align: center">No load balancers have been created</p>',
					loadingText: undefined
				},
				columns: [{
					text: "Name",
					dataIndex: 'name',
					width: 200,
					renderer: function(value, metadata, record) {
						if(record.data.state !== 'On')
							metadata.css = 'grid-loader';
						return value;
					}
				},{
					text: "Provider",
					dataIndex: 'provider',
					width: 100
				},{
					text: "State",
					dataIndex: 'state',
					width: 80
				},{
					text: "DNS Name",
					dataIndex: 'dns_name',
					flex: 1,
					renderer: function(value) {
						return '<a target="_blank" href="http://' + value + '/">' + value + '</a>';
					}
				}
				],
				listeners: {
					itemcontextmenu: function (view, record, item, index, e) {
						e.preventDefault();
						lb_menu.ref_grid = this;
						lb_menu.selected_record = record;
						lb_menu.showAt(e.getXY());
					},
					activate: Helpers.first_time_loader
				},
				dockedItems: [{
					xtype: 'toolbar',
					dock: 'top',
					items: [{
						text: 'Deploy a load balancer',
						iconCls: 'start',
						handler: function() {
							var form = deploy_form.setHeight(110).getForm().reset();
							form.findField('address').disable().hide();
							deploy_form.up('window').show().center();
						}
					}, '->',{
						text: 'Refresh List',
						iconCls: 'restart',
						handler: function() {
							store.load();
						}
					}]
				}]
			}
		}
	}
}();