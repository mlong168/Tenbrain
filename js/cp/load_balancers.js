var Load_balancers = function(){
	var store = function(){
		var record = Ext.data.Record.create([
			'id',
			'name',
			'dns_name'
		]);
		return new Ext.data.Store({
			url: '/amazon/created_load_balancers',
			reader: new Ext.data.JsonReader({
				root: 'load_balancers',
				successProperty: 'success',
				idProperty: 'id'
			}, record),
			autoLoad: true
		});
	}();

	var instance_store = function(){
		var record = Ext.data.Record.create([
			'id',
			'name',
			'instance_id',
			'ip_address'
		]);
		return new Ext.data.Store({
			url: '/amazon/show_lb_instances',
			reader: new Ext.data.JsonReader({
				root: 'instances',
				successProperty: 'success',
				idProperty: 'id'
			}, record),
			autoLoad: true
		});
	}();

	var xg = Ext.grid, checkbox_sm = new xg.CheckboxSelectionModel();
	var instances_grid = new xg.GridPanel({
		id: 'lb_multi_purpose_instances',
		layout: 'fit',
		loadMask: true,
		store: instance_store,
		sm: checkbox_sm,
		view: new xg.GridView({
			forceFit: true,
			emptyText: '<p style="text-align: center">No instances have been registered with this load balancer so far</p>'
		}),
		cm: new xg.ColumnModel({
			defaultSortable: false,
			columns: [
				checkbox_sm,
				{header: "Name", dataIndex: 'name', width: 150},
				{header: "IP Address", dataIndex: 'ip_address', width: 120},
				{header: "Instance ID", dataIndex: 'instance_id', width: 100}
			]
		}),
		tbar: {
			xtype: 'toolbar',
			items: [{
				xtype: 'button',
				id: 'register_instances-button',
				text: 'Register selected',
				cls: 'x-btn-text-icon',
				iconCls: 'start',
				handler: function(){
					var selected = checkbox_sm.getSelections(), instances = [],
						title = 'Register Instances with load balancer',
						success = 'Selected instances have been registered successfully',
						error = 'A problem has occurred while registering selected instances';

					if(!checkbox_sm.getCount())
					{
						Ext.Msg.alert('Warning', 'Please select some instances to perform the action');
						return false;
					}

					Ext.MessageBox.confirm(title, 'Are you sure you want load balance those selected instances?', function(button){
						if(button !== 'yes') return false;

						for(var i = selected.length; i--;)
						{
							instances.push(selected[i].data.instance_id);
						}
						Ext.Msg.wait('The instances are being registered with the load balancer', 'Registering instances');
						Ext.Ajax.request({
							url: 'amazon/register_instances_with_lb',
							params: {
								lb_name:
									instances_grid.ref_lb_name ||
									lb_menu.ref_grid.getStore().getAt(lb_menu.selected_record_id).get('name'),
								instances: Ext.encode(instances)
							},
							success: function(response){
								response = Ext.decode(response.responseText);
								var s = response.success;
								Ext.Msg.alert(title, s ? success : response.error_message || error);
								instances_grid.ref_lb_name = null; // reset lb name
								instance_store.reload();
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
					instance_store.reload();
				}
			}]
		},
		ref_lb_name: null
	});

	var registered_instances_store = function(){
		var record = Ext.data.Record.create([
			'id',
			'name',
			'instance_id',
			'ip_address',
			'healthy',
			'health_message'
		]);
		return new Ext.data.Store({
			url: '/amazon/get_load_balanced_instances',
			reader: new Ext.data.JsonReader({
				root: 'instances',
				successProperty: 'success',
				idProperty: 'id'
			}, record),
			autoLoad: true
		});
	}();
	
	var reg_checkbox_sm = new xg.CheckboxSelectionModel()
	var registered_instances_grid = new xg.GridPanel({
		id: 'lb_registered_instances',
		layout: 'fit',
		loadMask: true,
		store: registered_instances_store,
		sm: reg_checkbox_sm,
		view: new xg.GridView({
			forceFit: true,
			emptyText: '<p style="text-align: center">No instances have been registered with this load balancer so far</p>'
		}),
		cm: new xg.ColumnModel({
			defaultSortable: false,
			columns: [
				reg_checkbox_sm,
				{header: "Name", dataIndex: 'name', width: 150},
				{header: "Instance ID", dataIndex: 'instance_id', width: 100},
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
					var selected = reg_checkbox_sm.getSelections(), instances = [],
						title = 'Deregister Instances with load balancer',
						success = 'Selected instances have been deregistered successfully',
						error = 'A problem has occurred while deregistering selected instances';

					if(!reg_checkbox_sm.getCount())
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
							instances.push(selected[i].data.instance_id);
						}
						Ext.Msg.wait('The instances are being deregistered from the load balancer', 'Deregistering instances');
						Ext.Ajax.request({
							url: 'amazon/deregister_instances_from_lb',
							params: {
								lb_name: lb_menu.ref_grid.getStore().getAt(lb_menu.selected_record_id).get('name'),
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

	var deploy_form = new Ext.FormPanel({
		id: 'lb_deploy_form',
		labelWidth: 40,
		frame: true,
		url: '/amazon/create_load_balancer',
		monitorValid: true,
		items: [{
			xtype: 'textfield',
			width: 200,
			fieldLabel: 'Name',
			name: 'name',
			vtype: 'alphanum',
			emptyText: 'Type a load balancer name here',
			allowBlank: false
		}],

		buttons: [{
			text: 'Proceed',
			formBind: true,
			handler: function(){
				var title = 'Create load balancer',
					success = 'Load balancer was created successfully',
					error = 'A problem has occured while creating a load balancer',
					name = deploy_form.getForm().getFieldValues().name;

				modal_window.hide();
				Ext.Msg.wait('Your load balancer is being created', title);
				deploy_form.getForm().submit({
					success: function(form, action){
						var s = action.result.success;
						Ext.Msg.alert(title, s ? success : error, function(){
							store.reload();
							modal_window
								.setTitle('Register instances with load balancer "' + name + '"')
								.setSize(700, 250).show().center()
								.getLayout().setActiveItem('lb_multi_purpose_instances');
							instance_store.reload({
								params: {}
							});
							instances_grid.ref_lb_name = name;
						});
					},
					failure: function(form, action){
						Ext.Msg.alert(title, error);
					}
				});
			}
		}, {
			text: 'Cancel',
			handler: function(){
				modal_window.hide();
			}
		}]
	});

	var modal_window = new Ext.Window({
		title: 'Create load balancer',
		id: 'modal_window',
		layout: 'card',
		closeAction: 'hide',
		items: [deploy_form, instances_grid, registered_instances_grid],
		activeItem: 0,
		border: false,
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
							record = grid.getStore().getAt(lb_menu.selected_record_id),
							name = record.get('name');
						
						lb_menu.hide();						
						modal_window
							.setTitle('Instances available to register within the load balancer "' + name + '"')
							.setSize(700, 250).show().center()
							.getLayout().setActiveItem('lb_multi_purpose_instances');
						
						instance_store.reload({
							params: {
								lb_name: name
							}
						});
					}
				}, {
					text: 'Deregister instances from load balancer',
					handler: function(){
						var grid = lb_menu.ref_grid,
							record = grid.getStore().getAt(lb_menu.selected_record_id),
							name = record.get('name');
						
						lb_menu.hide();
						modal_window
							.setTitle('Instances registered within the load balancer "' + name + '"')
							.setSize(700, 250).show().center()
							.getLayout().setActiveItem('lb_registered_instances');
						registered_instances_store.reload({
							params: {
								lb_name: name
							}
						});
					}
				}]
			}
		}, {
			text: 'Management',
			menu: {
				items: [{
					text: 'View instances registered with load balancer',
					handler: function(){
						var grid = lb_menu.ref_grid,
							record = grid.getStore().getAt(lb_menu.selected_record_id),
							name = record.get('name');
						
						lb_menu.hide();
						modal_window
							.setTitle('Instances registered within the load balancer "' + name + '"')
							.setSize(700, 250).show().center()
							.getLayout().setActiveItem('lb_registered_instances');
						registered_instances_store.reload({
							params: {
								lb_name: name
							}
						});
					}
				}, {
					text: 'Delete load balancer',
					handler: function(){
						var grid = lb_menu.ref_grid,
							record = grid.getStore().getAt(lb_menu.selected_record_id),
							name = record.get('name'),
							title = 'Delete load balancer',
							success = 'Load balancer was deleted successfully',
							error = 'A problem has occurred when deleting the load balancer';

						lb_menu.hide();
						Ext.MessageBox.confirm(title, 'Are you sure you want delete this load balancer?', function(button){
							if(button !== 'yes') return false;

							Ext.Msg.wait('The load balancer is being deleted', title);
							Ext.Ajax.request({
								url: 'amazon/delete_load_balancer',
								params: {
									name: name,
								},
								success: function(response){
									response = Ext.decode(response.responseText);
									var s = response.success;
									Ext.Msg.alert(title, s ? success : response.error_message || error);
									store.reload();
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
		selected_record_id: null
	});

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
				lb_menu.selected_record_id = this.getStore().getAt(id).get('id');
				lb_menu.showAt(e.getXY());
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
					deploy_form.getForm().reset();
					modal_window
						.setTitle('Create Load Balancer')
						.setSize(270, 102).show().center()
						.getLayout().setActiveItem('lb_deploy_form');
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
				{header: "Name", dataIndex: 'name', width: 130},
				{header: "DNS Name", dataIndex: 'dns_name', width: 130, renderer: function(value){
					return '<a target="_blank" href="http://' + value + '/">' + value + '</a>';
				}}
			]
		})
	});
	return {
		deploy: function(){
			modal_window
				.setTitle('Create Load Balancer')
				.setSize(270, 102).show().center()
				.getLayout().setActiveItem('lb_deploy_form');
		},
		get_grid: function(){
			return grid;
		}
	}
}();