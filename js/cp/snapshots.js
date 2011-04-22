var Snapshots = function(){
	var store = function(){
		var record = Ext.data.Record.create([
			'id',
			'snapshot_id',
			'name',
			'capacity',
			'description',
			'status',
			'progress',
			'started'
		]);
		return {
			common: new Ext.data.Store({
				url: '/common/available_images',
				reader: new Ext.data.JsonReader({
					root: 'snapshots'
				}, record),
				autoLoad: true
			}),
			specific: new Ext.data.Store({
				url: '/common/available_images',
				reader: new Ext.data.JsonReader({
					root: 'snapshots'
				}, record),
				autoLoad: true
			})
		};
	}();
	var reload_until_stable = function(){
		var starter = 8000, interval = starter, minimum_interval = 2000, jump = 2000, stable_state = 'completed';
		return function(state, step){
			state = state || stable_state;
			step = step || jump;
			
			// reset the initial timeout when called from outside itself
			if(this !== reload_until_stable)
			{
				interval = starter;
			}
			
			store.common.reload({
				callback: function(r){
					for(var i = r.length; i--;)
					{
						if(r[i].data.status !== state)
						{
							setTimeout(function(){
								/**
								 * here we simulate the call of function from within itself
								 * we do it by calling it from it's scope, so, later we can
								 * check smth like: this !== reload_until_stable
								 */
								reload_until_stable.call(reload_until_stable, state, step);
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

	var creator = new Ext.FormPanel({
		labelWidth: 70,
		frame: true,
		border: false,
		url: '/amazon/create_snapshot',		
		monitorValid: true,
		items: [{
			xtype: 'hidden',
			name: 'instance_id'	
		}, {
			xtype: 'textfield',
			width: 150,
			fieldLabel: 'Name',
			name: 'name',
			allowBlank: false,
			vtype: 'alphanum'
		}, {
			xtype: 'textfield',
			width: 250,
			fieldLabel: 'Description',
			name: 'description',
			allowBlank: false,
			vtype: 'alphanum'
		}],

		buttons: [{
			text: 'Proceed',
			formBind: true,
			handler: function(){
				var title = 'Create Snapshot',
					success = 'The snapshot has been created successfully',
					error = 'A problem occured while creating your snapshot';
				create_dialogue.hide();
				Ext.Msg.wait('The snapshot is being created', title);
				creator.getForm().submit({
					success: function(form, action){
						var s = action.result.success
						Ext.Msg.alert(title, s ? success : error);
						Snapshots.reload_until_stable();
					},
					failure: function(form, action){
						Ext.Msg.alert(title, error);
					}
				});
			}
		}, {
			text: 'Cancel',
			handler: function(){
				create_dialogue.hide();
			}
		}]
	});
	
	var create_dialogue = new Ext.Window({
		title: 'Create a Snapshot for the instance',
		height: 128,
		width: 350,
		closeAction: 'hide',
		items: creator,
		border: false,
		modal : true
	});
	
	var redeployment_form = new Ext.form.FormPanel({
		labelWidth: 100,
		url: '/amazon/restore_snapshot_to_new_instance',
		border: false,
		frame: true,
		monitorValid: true,

		items: [{
			xtype: 'hidden',
			name: 'snapshot_id'	
		}, {
			xtype: 'textfield',
			width: 170,
			fieldLabel: 'Name',
			name: 'name',
			allowBlank: false,
			vtype: 'alphanum'
		}, {
			xtype: 'combo',
			width: 150,
			fieldLabel: 'Instance Type',
			allowBlank: false,
			editable: false,
			store: new Ext.data.JsonStore({
				url: '/amazon/get_available_instance_types',
				autoLoad: true,
				successProperty: 'success',
				root: 'types',
				fields: ['name', 'available', 'reason']
			}),
			mode: 'local',
			name: 'instance_type',
			displayField: 'name',
			hiddenName: 'instance_type', // POST-var name
			valueField: 'name', // POST-var value
			emptyText: 'Select type',
			tpl: '<tpl for="."><div ext:qtip="{reason}" class="x-combo-list-item">{name}</div></tpl>',
			forceSelection: true,
			typeAhead: true,
			listeners: {
				beforeselect: function(combo, record){
					return record.data.available; // false if not selectable
				}
			}
		}],

		buttons: [{
			text: 'Proceed',
			formBind: true,
			handler: function(){
				var title = 'Create new instance from a snapshot',
					success = 'A new instance has been successfully created from a snapshot',
					error = 'A problem has occurred while creating a new instance from a snapshot';
				redeployment_dialogue.hide();
				Ext.Msg.wait('A new instance is being created', title);
				redeployment_form.getForm().submit({
					success: function(form, action){
						Ext.Msg.alert(title, action.result.success ? success : response.error_message || error);
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
				redeployment_dialogue.hide();
			}
		}]
	});
	
	var redeployment_dialogue = new Ext.Window({
		title: 'Create a new instance from snapshot',
		height: 128,
		width: 310,
		border: false,
		closeAction: 'hide',
		items: redeployment_form,
		modal : true
	});
	
	var instance_snapshots_grid = new Ext.grid.GridPanel({
		border: false,
		store: store.specific,
		loadMask: true,
		cm: new Ext.grid.ColumnModel({
			defaultSortable: false,
			columns: [
				{header: "Name", dataIndex: 'name', width: 80},
				{header: "Description", dataIndex: 'description', id: 'description', width: 150},
				{header: "Capacity", dataIndex: 'capacity', width: 60},
				{header: "Status", dataIndex: 'status', width: 70},
				{header: "Start Time", dataIndex: 'started', width: 100},
				{header: "Progress", dataIndex: 'progress', width: 60}
			]
		}),
		view: new Ext.grid.GridView({
			// forceFit: true,
			emptyText: '<p style="text-align: center">No snapshots were created for this instance</p>'
		}),
		autoExpandColumn: 'description'
	});
	
	var instance_snapshots = new Ext.Window({
		title: 'Instance snapshots',
		height: 250,
		width: 700,
		layout: 'fit',
		closeAction: 'hide',
		items: instance_snapshots_grid,
		modal : true
	});
	
	var snapshot_instance_grid = new Ext.grid.GridPanel({
		border: false,
		store: new Ext.data.Store({
			url: '/amazon/snapshot_instance',
			reader: new Ext.data.JsonReader({
				root: 'instances',
				successProperty: 'success',
				idProperty: 'id'
			}, Ext.data.Record.create([
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
			])),
			autoLoad: true
		}),
		loadMask: true,	
		cm: new Ext.grid.ColumnModel({
			defaultSortable: false,
			columns: [
				{header: "Name", dataIndex: 'name', width: 150, id: 'name'},
				{header: "Link to instance root", dataIndex: 'dns_name', width: 250, renderer: function(link){
					return '<a target="_blank" href="http://' + link + '/">' + link + '</a>';
				}},
				{header: "IP Address", dataIndex: 'ip_address', width: 120},
				{header: "State", dataIndex: 'state', width: 100}
			]
		}),
		view: new Ext.grid.GridView({
			// forceFit: true,
			emptyText: '<p style="text-align: center">The instance snapshot has been created of has either been terminated or is currently not available</p>'
		}),
		autoExpandColumn: 'name'
	});
	
	var snapshot_instance = new Ext.Window({
		title: 'Instance for snapshot',
		height: 250,
		width: 700,
		layout: 'fit',
		closeAction: 'hide',
		items: snapshot_instance_grid,
		modal : true
	});

	var snapshot_menu = new Ext.menu.Menu({
		items: [{
			text: 'Restore and terminate corresponding instance',
			handler: function(){
				var snap_id = snapshot_menu.relative_grid.getStore().getAt(snapshot_menu.selected_record_id).get('snapshot_id');
				snapshot_menu.hide();
				Ext.Msg.confirm(
					'Restore snapshot to corresponding instance',
					"Are you sure you want to restore snapshot to it's instance?",
					function(button){
						var error_message = 'A problem has occurred while restoring snapshot';
						if(button === 'yes')
						{
							Ext.Msg.wait('Restoring your snapshot', 'Snapshot Restore');
							Ext.Ajax.request({
								url: 'amazon/restore_snapshot_to_corresponding_instance',
								params: { snapshot_id: snap_id },
								success: function(response){
									response = Ext.decode(response.responseText);
									var s = response.success;
									Ext.Msg.alert(s ? 'Success' : 'Error', s
										? 'Snapshot has been restored successfully'
										: response.error_message || error_message
									);
									store.common.reload();
									Instances.reload_until_stable('running');
									Instances.reload_until_stable('stopped', function(){
										Instances.reload_instances('terminated');
									});
								},
								failure: function(){
									Ext.Msg.alert('Error', error_message);
								}
							});
						}
					});
				return false;				
			}
		}, {
			text: 'Redeploy to new instance',
			handler: function(){
				var snap_id = snapshot_menu.relative_grid.getStore().getAt(snapshot_menu.selected_record_id).get('snapshot_id');
				snapshot_menu.hide();
				redeployment_form.getForm().reset().setValues({snapshot_id: snap_id});
				redeployment_dialogue.show();
				return false;
			}
		}, {
			text: 'View corresponding instance',
			handler: function(){
				var record = snapshot_menu.relative_grid.getStore().getAt(snapshot_menu.selected_record_id),
					snap_id = record.get('snapshot_id'),
					name = record.get('name');
				
				snapshot_menu.hide();
				snapshot_instance_grid.getStore().reload({
					params: {
						snapshot_id: snap_id
					}
				});
				snapshot_instance.setTitle('Instance for snapshot "' + name + '"');
				snapshot_instance.show();
			}
		}, {
			text: 'Delete snapshot',
			handler: function(){
				var snap_id = snapshot_menu.relative_grid.getStore().getAt(snapshot_menu.selected_record_id).get('snapshot_id');
				snapshot_menu.hide();
				Ext.MessageBox.confirm('Snapshot Delete', 'Are you sure you want to delete this snapshot?', function(button){
					if(button === 'yes')
					{
						Ext.Ajax.request({
							url: 'amazon/delete_snapshot',
							params: {
								snapshot_id: snap_id
							},
							success: function(response){
								response = Ext.decode(response.responseText);
								Ext.Msg.alert('Delete snapshot', response.success
									? 'Snapshot has been deleted successfully'
									: 'A problem has occurred when deleting a snapshot'
								);
								store.common.reload();
							},
							failure: function(){
								Ext.Msg.alert('Error', 'A problem has occurred when deleting a snapshot');
							}
						});
					}
				});
				return false;
			}
		}],
		relative_grid: null,
		selected_record_id: null
	});

	var sm = new Ext.grid.CheckboxSelectionModel();
	var snapshots = new Ext.grid.GridPanel({
		id: 'snapshots-panel',
		title: 'Created snapshots',
		layout: 'fit',
		store: store.common,
		tbar: {
			xtype: 'toolbar',
			items: [{
				xtype: 'button',
				text: 'Delete Snapshots',
				cls: 'x-btn-text-icon',
				iconCls: 'terminate',
				handler: function(){
					var selected = sm.getSelections(), snaps = [],
						title = 'Delete Snapshots',
						success = 'Snapshots have been deleted successfully',
						error = 'A problem has occurred while deleting snapshots';						
					if(!sm.getCount())
					{
						Ext.Msg.alert('Warning', 'Please select some snapshots to perform the action');
						return false;
					}
					
					Ext.MessageBox.confirm(title, 'Are you sure you want to delete these snapshots?', function(button){
						if(button !== 'yes') return false;
					
						for(var i = selected.length; i--;)
						{
							snaps.push(selected[i].data.snapshot_id);
						}
						Ext.Msg.wait('The snapshots are being deleted', 'Deleting snapshots');
						Ext.Ajax.request({
							url: 'amazon/delete_snapshot',
							params: {
								snapshots: Ext.encode(snaps)
							},
							success: function(response){
								response = Ext.decode(response.responseText);
								var s = response.success;
								Ext.Msg.alert(title, s ? success : response.error_message || error);
								store.common.reload();
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
					store.common.reload();
				}
			}]
		},
		view: new Ext.grid.GridView({
			forceFit: true,
			emptyText: '<p style="text-align: center">You have not created any snapshot so far</p>'
		}),
		cm: new Ext.grid.ColumnModel({
			defaultSortable: false,
			columns: [
				sm,
				{header: "Name", dataIndex: 'name', width: 150, renderer: function(value, metadata, record){
					if(record.data.status !== 'completed') metadata.css = 'grid-loader';
					return value;
				}},
				{header: "Description", dataIndex: 'description', width: 100},
				{header: "Capacity", dataIndex: 'capacity', width: 120},
				{header: "Status", dataIndex: 'status', width: 100},
				{header: "Start Time", dataIndex: 'started', width: 100},
				{header: "Progress", dataIndex: 'progress', width: 100}
			]
		}),
		sm: sm,
		listeners: {
			rowcontextmenu: function (grid, id, e) {
				e.preventDefault();
				snapshot_menu.relative_grid = grid;
				snapshot_menu.selected_record_id = id;
				snapshot_menu.showAt(e.getXY());
			}
		}
	});
	
	return {
		get_panel: function(){ return snapshots; },
		reload_until_stable: reload_until_stable,
		
		create: function(instance_id){
			creator.getForm().reset().setValues({instance_id: instance_id});
			create_dialogue.show();
			return false;
		},
		
		show_instance_snapshots: function(instance_id, instance_name){
			instance_name = instance_name || '';
			instance_snapshots.setTitle(instance_name.length
				? 'Snapshots for instace "' + instance_name + '"'
				: 'Instance snapshots'
			);			
			store.specific.reload({
				params: {
					instance_id: instance_id
				}
			});		
			instance_snapshots.show();
			return false;
		}		
	};
}();