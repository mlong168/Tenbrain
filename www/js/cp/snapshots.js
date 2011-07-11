// custom Vtype for vtype:'Description'
Ext.apply(Ext.form.field.VTypes, {
    Description:  function(v) {
        return /^[A-Za-z0-9_]+[A-Za-z0-9_\-\. ]*$/.test(v);
    },
    DescriptionText: 'Not a valid description. Must be in format /^[A-Za-z0-9_]+[A-Za-z0-9_\-\. ]*$/',
    DescriptionMask: /[a-z\d\.\-_ ]/i
});

var Snapshots = function() {

	Ext.define('Backups', {
		extend: 'Ext.data.Model',
		fields: [{
			name: 'id',
			type: 'string'
		},{
			name: 'provider_backup_id',
			type: 'string'
		},{
			name: 'server_id',
			type: 'string'
		},{
			name: 'name',
			type: 'string'
		},{
			name: 'description',
			type: 'string'
		},{
			name: 'provider',
			type: 'string'
		},{
			name: 'status',
			type: 'string'
		},{
			name: 'created_on',
			type: 'string'
		}
		]
	});

	var store = {
		common: Ext.create('Ext.data.Store', {
			model: 'Backups',
			proxy: {
				type: 'ajax',
				url: '/common/list_backups',
				reader: {
					type: 'json',
					root: 'backups'
				}
			}
		}),
		specific: Ext.create('Ext.data.Store', {
			model: 'Backups',
			proxy: {
				type: 'ajax',
				url: '/common/view_backups',
				reader: {
					type: 'json',
					root: 'backups'
				}
			}
		})
	};

	var reload_until_stable = function() {
		var starter = 8000, interval = starter, minimum_interval = 2000, jump = 2000, stable_state = 'completed';
		return function(state, step) {
			state = state || stable_state;
			step = step || jump;

			// reset the initial timeout when called from outside itself
			if(this !== reload_until_stable) {
				interval = starter;
			}

			store.common.load({
				callback: function(r) {
					for(var i = r.length; i--;) {
						if(r[i].data.status !== state) {
							setTimeout( function() {
								/**
								 * here we simulate the call of function from within itself
								 * we do it by calling it from it's scope, so, later we can
								 * check smth like: this !== reload_until_stable
								 */
								reload_until_stable.call(reload_until_stable, state, step);
							}, interval);
							if(interval > minimum_interval && interval - step > 0)
								interval -= step;
							break;
						}
					}
				}
			});
			return false;
		};
	}();
	var creator = Ext.create('Ext.form.Panel', {
		url: '/common/create_backup',
		frame: true,
		border: false,
		height: 80,
		minHeight: 80,
		baseCls: 'x-plain',
		pollForChanges: true,
		defaults: {
			xtype: 'textfield',
			labelWidth: 70,
			anchor: '100%',
			allowBlank: false,
			vtype: 'alphanum'
		},
		items: [{
			xtype: 'hidden',
			name: 'instance_id'
		},{
			fieldLabel: 'Name',
			name: 'name'
		},{
			fieldLabel: 'Description',
			name: 'description',
            vtype: 'Description'
		}],

		buttons: [{
			text: 'Proceed',
			formBind: true,
			handler: function() {
				var title = 'Create Backup',
				success = 'Backup has been created successfully',
				error = 'A problem occured while creating your backup';

				this.up('window').hide();
				this.up('form').getForm().submit({
					waitTitle: title,
					waitMsg: 'Backup is being created',
					success: function(form, action) {
						var s = action.result.success
						Ext.Msg.alert(title, s ? success : error);
						Snapshots.reload_until_stable();
					},
					failure: function(form, action) {
						Ext.Msg.alert(title, error);
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
		title: 'Create backup for the instance',
		layout: 'fit',
		width: 350,
		minWidth: 200,
		closeAction: 'hide',
		plain: 'true',
		modal : true,
		bodyStyle: 'padding:5px;',
		items: creator
	});

	var redeployment_form = Ext.create('Ext.form.Panel', {
		url: '/common/restore_backup_to_new_instance',
		baseCls: 'x-plain',
		frame: true,
		border: false,
		height: 80,
		minHeight: 80,
		baseCls: 'x-plain',
		pollForChanges: true,
		fieldDefaults: {
			anchor: '100%',
			labelWidth: 100,
			allowBlank: false
		},

		items: [{
			xtype: 'hidden',
			name: 'backup_id'
		},{
			xtype: 'textfield',
			fieldLabel: 'Server Name',
			name: 'name'
		},{
			xtype: 'combo',
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
			name: 'ip_address',
			displayField: 'address',
			valueField: 'address', // POST-var value
			autoSelect: true,
			forceSelection: true,
			triggerAction: 'all'
		},{
			xtype: 'combo',
			fieldLabel: 'Server Type',
			editable: false,
			store: Ext.create('Ext.data.Store', {
				model: 'Server_types',
				proxy: {
					type: 'ajax',
					url: '/common/get_available_server_types',
					reader: {
						type: 'json',
						root: 'types'
					},
					extraParams: {
						provider: ''
					}
				}
			}),
			queryMode: 'remote',
			name: 'server_type',
			displayField: 'name',
			valueField: 'value', // POST-var value
			emptyText: 'Select type',
			tpl: '<tpl for="."><div ext:qtip="{reason}" class="x-combo-list-item">{name}</div></tpl>',
			forceSelection: true,
			triggerAction: 'all',
			listeners: {
				beforequery: function(q) {
					delete q.combo.lastQuery;
				},
				beforeselect: function(combo, record) {
					return record.data.available; // false if not selectable
				}
			}
		}],

		buttons: [{
			text: 'Proceed',
			formBind: true,
			handler: function() {
				var title = 'Create new server from backup',
				success = 'A new server has been successfully created from backup',
				error = 'A problem has occurred while creating new server from backup';

				this.up('window').hide();
				this.up('form').getForm().submit({
					waitTitle: title,
					waitMsg: 'Creating the server',
					success: function(form, action) {
						Ext.Msg.alert(title, action.result.success ? success : response.error_message || error);
						Instances.reload_until_stable('running');
					},
					failure: function(form, action) {
						Ext.Msg.alert(title, error);
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
		title: 'Create a new server from backup',
		layout: 'fit',
		width: 350,
		minWidth: 300,
		closeAction: 'hide',
		items: redeployment_form,
		modal : true,
		plain: 'true',
		bodyStyle: 'padding:5px;'
	});

	var instance_snapshots_grid = new Ext.grid.GridPanel({
		border: false,
		store: store.specific,
		loadMask: true,
		columns: [{
			text: "Name",
			dataIndex: 'name',
			width: 80
		},{
			text: "Provider",
			dataIndex: 'provider',
			width: 60
		},{
			text: "Description",
			dataIndex: 'description',
			id: 'description',
			width: 150
		},{
			text: "Start Time",
			dataIndex: 'created_on',
			width: 100
		}
		],
		forceFit: true,
		viewConfig: {
			emptyText: '<p style="text-align: center">No backups were created for this instance</p>'
		},
		columnLines: true,
		listeners: {
			activate: function(p) {
				var store = p.getStore();
				if(store.last() === undefined)
					store.load();
			}
		}
	});

	Ext.create('Ext.window.Window', {
		title: 'Server backups',
		height: 250,
		width: 700,
		layout: 'fit',
		closeAction: 'hide',
		items: instance_snapshots_grid,
		modal : true
	});

	var snapshot_instance_grid = Ext.create('Ext.grid.Panel', {
		border: false,
		store: Ext.create('Ext.data.Store', {
			model: 'Server',
			proxy: {
				type: 'ajax',
				url: '/common/backup_instance',
				reader: {
					type: 'json',
					root: 'instances'
				}
			}
		}),
		loadMask: true,
		columns: [{
			header: "Name",
			dataIndex: 'name',
			width: 150,
			id: 'name'
		},{
			header: "Link to instance root",
			dataIndex: 'dns_name',
			width: 250,
			renderer: function(link) {
				return '<a target="_blank" href="http://' + link + '/">' + link + '</a>';
			}
		},{
			header: "IP Address",
			dataIndex: 'ip',
			width: 120
		},{
			header: "State",
			dataIndex: 'state',
			width: 100
		}
		],
		forceFit: true,
		viewConfig: {
			emptyText: '<p style="text-align: center">The server backup has been created of has either been terminated or is currently not available</p>'
		},
		columnLines: true,
		listeners: {
			activate: function(p) {
				var store = p.getStore();
				if(store.last() === undefined)
					store.load();
			}
		}
	});

	var snapshot_instance = Ext.create('Ext.window.Window', {
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
			text: 'Restore to corresponding server',
			handler: function() {
				var snap_id = snapshot_menu.selected_record.get('id');
				snapshot_menu.hide();
				Ext.Msg.confirm(
				'Restore backup to corresponding server',
				"Are you sure you want to restore backup to it's server?", function(button) {
					var error_message = 'A problem has occurred while restoring backup';
					if(button === 'yes') {
						Ext.Msg.wait('Restoring your backup', 'Backup Restore');
						Ext.Ajax.request({
							url: 'common/restore_backup_to_corresponding_instance',
							params: {
								backup_id: snap_id
							},
							success: function(response) {
								response = Ext.decode(response.responseText);
								var s = response.success;
								Ext.Msg.alert(s ? 'Success' : 'Error', s
								? 'Backup has been restored successfully'
								: response.error_message || error_message
								);
								store.common.load();
								Instances.reload_until_stable('running');
								Instances.reload_until_stable('stopped', function() {
									Instances.reload_instances('terminated');
								});
							},
							failure: function() {
								Ext.Msg.alert('Error', error_message);
							}
						});
					}
				});
				return false;
			}
		},{
			text: 'Redeploy to new server',
			handler: function() {
				var record = snapshot_menu.selected_record,
				snap_id = record.get('id'),
				provider = record.get('provider'),
				is_gogrid = provider === 'GoGrid',
				form = redeployment_form.getForm(),
				types = form.findField('server_type');

				snapshot_menu.hide();

				form.reset().setValues({
					backup_id: snap_id
				});
				form.findField('ip_address').setDisabled(!is_gogrid).setVisible(is_gogrid);
				redeployment_form.setHeight(is_gogrid ? 112 : 80);

				types.store.proxy.extraParams.provider = provider;
				redeployment_form.up('window').show().center();
				return false;
			}
		},{
			text: 'View corresponding server',
			handler: function() {
				var record = snapshot_menu.selected_record,
				backup_id = record.get('id'),
				name = record.get('name');

				snapshot_menu.hide();
				snapshot_instance_grid.getStore().load({
					params: {
						backup_id: backup_id
					}
				});
				snapshot_instance.setTitle('Server for backup "' + name + '"');
				snapshot_instance.show();
			}
		},{
			text: 'Delete backup',
			handler: function() {
				var backup_id = snapshot_menu.selected_record.get('id');
				snapshot_menu.hide();
				Ext.MessageBox.confirm('Backup Removal', 'Are you sure you want to delete this backup?', function(button) {
					if(button === 'yes') {
						Ext.Ajax.request({
							url: 'common/delete_backup',
							params: {
								backup_id: backup_id
							},
							success: function(response) {
								response = Ext.decode(response.responseText);
								Ext.Msg.alert('Delete backup', response.success
								? 'Backup has been deleted successfully'
								: 'A problem has occurred when deleting backup'
								);
								store.common.load();
							},
							failure: function() {
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

	var sm = Ext.create('Ext.selection.CheckboxModel');

	return {
		panels: {
			backups: {
				xtype: 'grid',
				id: 'snapshots-panel',
				title: 'Created backups',
				layout: 'fit',
				store: store.common,
				viewConfig: {
					emptyText: '<p style="text-align: center">You have not created any backup so far</p>',
					loadingText: undefined
				},
				columns: [{
					text: "Name",
					dataIndex: 'name',
					width: 250,
					renderer: function(value, metadata, record) {
						if(record.data.status !== 'completed')
							metadata.css = 'grid-loader';
						return value;
					}
				},{
					text: "Status",
					dataIndex: 'status',
					width: 80
				},{
					text: "Provider",
					dataIndex: 'provider',
					width: 80
				},{
					text: "Description",
					dataIndex: 'description',
					id: 'description',
					flex: 1
				},{
					text: "Created on",
					dataIndex: 'created_on',
					width: 200
				}
				],
				selModel: sm,
				listeners: {
					itemcontextmenu: function (view, record, item, index, e) {
						e.preventDefault();
						snapshot_menu.selected_record = record;
						snapshot_menu.showAt(e.getXY());
					},
					activate: Helpers.first_time_loader
				},
				dockedItems: [{
					xtype: 'toolbar',
					dock: 'top',
					items: [{
						xtype: 'button',
						text: 'Delete Backups',
						cls: 'x-btn-text-icon',
						iconCls: 'terminate',
						handler: function() {
							var selected = sm.getSelection(), snaps = [],
							title = 'Backup removal',
							success = 'Backups have been deleted successfully',
							error = 'A problem has occurred while deleting backups';
							if(selected.length === 0) {
								Ext.Msg.alert('Warning', 'Please select some backups to perform the action');
								return false;
							}

							for(var i = selected.length; i--;) {
								snaps.push(selected[i].data.id);
							}

							Ext.Msg.confirm(title, 'Are you sure you want to delete these backups?', function(button) {
								if(button !== 'yes')
									return false;

								Ext.Msg.wait('Backups are being deleted', 'Backup removal');
								Ext.Ajax.request({
									url: 'common/delete_backups',
									params: {
										backup_ids: Ext.encode(snaps)
									},
									success: function(response) {
										response = Ext.decode(response.responseText);
										var s = response.success;
										Ext.Msg.alert(title, s ? success : response.error_message || error);
										store.common.load();
									},
									failure: function() {
										Ext.Msg.alert(title, error);
									}
								});
							});
						}
					}]
				},{
					xtype: 'toolbar',
					dock: 'bottom',
					items: ['->',{
						xtype: 'button',
						text: 'Refresh List',
						cls: 'x-btn-text-icon',
						iconCls: 'restart',
						handler: function() {
							store.common.load();
						}
					}]
				}]
			}
		},

		reload_until_stable: reload_until_stable,

		create: function(instance_id) {
			creator.getForm().reset().setValues({
				instance_id: instance_id
			});
			creator.up('window').show();
			return false;
		},
		show_instance_snapshots: function(instance_id, instance_name) {
			var instance_snapshots = snapshot_instance_grid.up('window');
			instance_name = instance_name || '';
			instance_snapshots.setTitle(instance_name.length
			? 'Backups for server "' + instance_name + '"'
			: 'Server backups'
			);
			store.specific.load({
				params: {
					server_id: instance_id
				}
			});
			instance_snapshots.show();
			return false;
		}
	};
}();