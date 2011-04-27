var Snapshots = function(){
	var store = function(){
		var record = Ext.data.Record.create([
			'id',
			'name',
			'description',
			'provider',
			'created_on',
			'status'
		]);
		return {
			common: new Ext.data.Store({
				url: '/common/list_backups',
				reader: new Ext.data.JsonReader({
					root: 'backups'
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
		url: '/common/create_backup',		
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
				var title = 'Create Backup',
					success = 'Backup has been created successfully',
					error = 'A problem occured while creating your backup';
				create_dialogue.hide();
				Ext.Msg.wait('Backup is being created', title);
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
		title: 'Create backup for the instance',
		height: 128,
		width: 350,
		closeAction: 'hide',
		items: creator,
		border: false,
		modal : true
	});
	
	var redeployment_form = new Ext.form.FormPanel({
		labelWidth: 100,
		url: '/common/restore_backup_to_new_instance',
		baseCls: 'x-plain',
		autoHeight: true,
		buttonAlign: 'center',
		defaults: {
			xtype: 'textfield'
		},
		monitorValid: true,

		items: [{
			xtype: 'hidden',
			name: 'backup_id'
		}, {
			anchor: '100%',
			fieldLabel: 'Name',
			name: 'name',
			allowBlank: false,
			vtype: 'alphanum'
		}, {
			xtype: 'combo',
			anchor: '100%',
			disabled: true,
			hidden: true,
			fieldLabel: 'IP address',
			allowBlank: false,
			vtype: 'IPAddress',
			store: new Ext.data.JsonStore({
				url: '/gogrid/get_free_addresses',
				root: 'addresses',
				fields: ['address']
			}),
			mode: 'remote',
			name: 'address',
			displayField: 'address',
			hiddenName: 'ip_address', // POST-var name
			valueField: 'address', // POST-var value
			autoSelect: true,
			forceSelection: true,
			typeAhead: true,
			triggerAction: 'all'
		}, {
			xtype: 'combo',
			anchor: '100%',
			fieldLabel: 'Server Type',
			allowBlank: false,
			editable: false,
			store: new Ext.data.JsonStore({
				url: '/common/get_available_server_types',
				root: 'types',
				fields: ['value', 'name', 'available', 'reason'],
				baseParams: {provider: ''}
			}),
			mode: 'remote',
			name: 'server_type',
			displayField: 'name',
			hiddenName: 'server_type', // POST-var name
			valueField: 'value', // POST-var value
			emptyText: 'Select type',
			tpl: '<tpl for="."><div ext:qtip="{reason}" class="x-combo-list-item">{name}</div></tpl>',
			forceSelection: true,
			typeAhead: true,
			triggerAction: 'all',
			listeners: {
				beforequery: function(q){
					delete q.combo.lastQuery;
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
				var title = 'Create new server from backup',
					success = 'A new server has been successfully created from backup',
					error = 'A problem has occurred while creating new server from backup';
				redeployment_dialogue.hide();
				Ext.Msg.wait('Creating the server', title);
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
		title: 'Create a new server from backup',
		width: 350,
		closeAction: 'hide',
		items: redeployment_form,
		modal : true,
		layout: 'fit',
		minWidth: 300,
		plain: 'true',
		bodyStyle: 'padding:5px;'
	});

	var instance_snapshots_grid = new Ext.grid.GridPanel({
		border: false,
		store: store.specific,
		loadMask: true,
		cm: new Ext.grid.ColumnModel({
			defaultSortable: false,
			columns: [
				{header: "Name", dataIndex: 'name', width: 80},
				{header: "Provider", dataIndex: 'provider', width: 60},
				{header: "Description", dataIndex: 'description', id: 'description', width: 150},
				{header: "Start Time", dataIndex: 'created_on', width: 100}
			]
		}),
		view: new Ext.grid.GridView({
			// forceFit: true,
			emptyText: '<p style="text-align: center">No backups were created for this instance</p>'
		}),
		autoExpandColumn: 'description'
	});
	
	var instance_snapshots = new Ext.Window({
		title: 'Server backups',
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
			url: '/amazon/backup_instance',
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
			emptyText: '<p style="text-align: center">The server backup has been created of has either been terminated or is currently not available</p>'
		}),
		autoExpandColumn: 'name'
	});
	
	var snapshot_instance = new Ext.Window({
		title: 'Server for backup',
		height: 250,
		width: 700,
		layout: 'fit',
		closeAction: 'hide',
		items: snapshot_instance_grid,
		modal : true
	});

	var snapshot_menu = new Ext.menu.Menu({
		items: [{
			text: 'Restore and terminate corresponding server',
			handler: function(){
				var snap_id = snapshot_menu.selected_record.get('id');
				snapshot_menu.hide();
				Ext.Msg.confirm(
					'Restore backup to corresponding server',
					"Are you sure you want to restore backup to it's server?",
					function(button){
						var error_message = 'A problem has occurred while restoring backup';
						if(button === 'yes')
						{
							Ext.Msg.wait('Restoring your backup', 'Backup Restore');
							Ext.Ajax.request({
								url: 'common/restore_backup_to_corresponding_instance',
								params: { backup_id: snap_id },
								success: function(response){
									response = Ext.decode(response.responseText);
									var s = response.success;
									Ext.Msg.alert(s ? 'Success' : 'Error', s
										? 'Backup has been restored successfully'
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
			text: 'Redeploy to new server',
			handler: function(){
				var record = snapshot_menu.selected_record,
					snap_id = record.get('id'),
					provider = record.get('provider'),
					is_gogrid = provider === 'GoGrid',
					form = redeployment_form.getForm(),
					types = form.findField('server_type');

				snapshot_menu.hide();
				
				form.reset().setValues({backup_id: snap_id});
				form.findField('ip_address').setDisabled(!is_gogrid).setVisible(is_gogrid);

				types.getStore().baseParams.provider = provider;
				redeployment_dialogue.show().center();
				return false;
			}
		}, {
			text: 'View corresponding server',
			handler: function(){
				var record = snapshot_menu.selected_record,
					backup_id = record.get('id'),
					name = record.get('name');
				
				snapshot_menu.hide();
				snapshot_instance_grid.getStore().reload({
					params: {
						backup_id: backup_id
					}
				});
				snapshot_instance.setTitle('Server for backup "' + name + '"');
				snapshot_instance.show();
			}
		}, {
			text: 'Delete backup',
			handler: function(){
				var backup_id = snapshot_menu.selected_record.get('id');
				snapshot_menu.hide();
				Ext.MessageBox.confirm('Backup Removal', 'Are you sure you want to delete this backup?', function(button){
					if(button === 'yes')
					{
						Ext.Ajax.request({
							url: 'common/delete_backup',
							params: {
								backup_id: backup_id
							},
							success: function(response){
								response = Ext.decode(response.responseText);
								Ext.Msg.alert('Delete backup', response.success
									? 'Backup has been deleted successfully'
									: 'A problem has occurred when deleting backup'
								);
								store.common.reload();
							},
							failure: function(){
								Ext.Msg.alert('Error', 'A problem has occurred when deleting backup');
							}
						});
					}
				});
				return false;
			}
		}],
		relative_grid: null,
		selected_record: null
	});

	var sm = new Ext.grid.CheckboxSelectionModel();
	var snapshots = new Ext.grid.GridPanel({
		id: 'snapshots-panel',
		title: 'Created backups',
		layout: 'fit',
		store: store.common,
		tbar: {
			xtype: 'toolbar',
			items: [{
				xtype: 'button',
				text: 'Delete Backups',
				cls: 'x-btn-text-icon',
				iconCls: 'terminate',
				handler: function(){
					var selected = sm.getSelections(), snaps = [],
						title = 'Backup removal',
						success = 'Backups have been deleted successfully',
						error = 'A problem has occurred while deleting backups';						
					if(!sm.getCount())
					{
						Ext.Msg.alert('Warning', 'Please select some backups to perform the action');
						return false;
					}
					
					Ext.MessageBox.confirm(title, 'Are you sure you want to delete these backups?', function(button){
						if(button !== 'yes') return false;
					
						for(var i = selected.length; i--;)
						{
							snaps.push(selected[i].data.backup_id);
						}
						Ext.Msg.wait('Backups are being deleted', 'Backup removal');
						Ext.Ajax.request({
							url: 'common/delete_backup',
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
			emptyText: '<p style="text-align: center">You have not created any backup so far</p>'
		}),
		cm: new Ext.grid.ColumnModel({
			defaultSortable: false,
			columns: [
				sm,
				{header: "Name", dataIndex: 'name', width: 150, renderer: function(value, metadata, record){
					if(record.data.status !== 'completed') metadata.css = 'grid-loader';
					return value;
				}},
				{header: "Status", dataIndex: 'status', width: 60},
				{header: "Provider", dataIndex: 'provider', width: 60},
				{header: "Description", dataIndex: 'description', id: 'description', width: 150},
				{header: "Start Time", dataIndex: 'created_on', width: 100}
			]
		}),
		sm: sm,
		listeners: {
			rowcontextmenu: function (grid, id, e) {
				e.preventDefault();
				snapshot_menu.relative_grid = this;
				snapshot_menu.selected_record = this.getStore().getAt(id);
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
				? 'Backups for server "' + instance_name + '"'
				: 'Server backups'
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