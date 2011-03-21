var Images = function(){
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
	}();

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
	});

	var deployment_form = new Ext.FormPanel({
		url: '/amazon/launch_instance',
		frame: true,
		border: false,
		labelWidth: 125,
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
				var title = 'Instance Deployment',
					success = 'Your Selected image has been successfully deployed',
					error = 'A problem occured while deploying your selected image';
				deploy_configurator.hide();
				Ext.Msg.wait('Deploying your image', title);
				deployment_form.getForm().submit({
					success: function(form, action){
						var s = action.result.success;
						Ext.Msg.alert(title, s ? success : error);
						Instances.reload_until_stable('running');
					},
					failure: function(form, action){
						Ext.Msg.alert(title, error);
					}
				});
			}
		},{
			text: 'Cancel',
			handler: function(){
				deploy_configurator.hide();
			}
		}]
	});
	
	var deploy_configurator = new Ext.Window({
		title: 'Deployment options',
		width: 320,
		height: 128,
		closeAction: 'hide',
		items: deployment_form,
		border: false,
		modal : true
	});

	var images_menu = new Ext.menu.Menu({
		items: [{
			text: 'Actions',
			menu: {
				items: [{
					text: 'Deploy',
					handler: function(){
						images_menu.hide();					
						deployment_form.getForm().reset().setValues({
							image_id: images_menu.selected_image_id
						});
						deploy_configurator.show();
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
	});

	var images_grid = new Ext.grid.GridPanel({
		id: 'available_images-panel',
		layout: 'fit',
		title: 'Images available for deployment',
		store: images,
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
	});
	
	return {
		get_grid: function(){
			return images_grid;
		},
		reload_store: function(){
			images.reload();
		}
	};
}();