var Images = function(){
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
			mode: 'local', // !!! load the store once and for all )
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
						Ext.Msg.alert(title, success);
						Instances.reload_until_stable('running');
					},
					failure: function(form, action){
						Ext.Msg.alert(title, action.result.error_message || error);
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
	
	var images = function(){
		var record = Ext.data.Record.create([
			'id',
			'image_id',
			'name',
			'state',
			'description',
			'location',
			'provider'
		]);
		return new Ext.data.GroupingStore({
			url: '/amazon/available_images',
			reader: new Ext.data.JsonReader({
				root: 'images',
				successProperty: 'success',
				idProperty: 'id'
			}, record),
			groupField: 'provider',
			autoLoad: true
		});
	}();

	var images_grid = new Ext.grid.GridPanel({
		id: 'available_images-panel',
		title: 'Images available for deployment',
		store: images,
		loadMask: true,
		view: new Ext.grid.GroupingView({
			forceFit: true,
			emptyText: '<p style="text-align: center">No images are available for deployment</p>',
			groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})'
		}),
		listeners: {
			rowcontextmenu: function (grid, id, e) {
				e.preventDefault();
				images_menu.selected_image_id = grid.getStore().getAt(id).get('image_id');
				images_menu.showAt(e.getXY());
			}
		},
		colModel: new Ext.grid.ColumnModel({
			columns: [
				{header: "Name", dataIndex: 'name', width: 100},
				{header: "Provider", dataIndex: 'provider', hidden: true},
				{header: "State", dataIndex: 'state', width: 70},
				{header: "Description", dataIndex: 'description', width: 170},
				{header: "Location", dataIndex: 'location', width: 120}
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