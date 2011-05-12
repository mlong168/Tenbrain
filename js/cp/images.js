var Images = function(){
	
	var image_deploy_form_submitter = function(){
		var title = 'Server Deployment',
			success = 'Your Selected image has been successfully deployed',
			error = 'A problem occured while deploying your selected image';
			
		this.up('window').hide();
		this.up('form').getForm().submit({
			waitTitle: title,
			waitMsg: 'Deploying your image',
			success: function(form, action){
				Ext.Msg.alert(title, success);
				Instances.reload_until_stable('running');
			},
			failure: function(form, action){
				Ext.Msg.alert(title, action.result.error_message || error);
			}
		});
	};
	
	Ext.define('Amazon_instance_type', {
		extend: 'Ext.data.Model',
		fields: [
			{name: 'name',		type: 'string'},
			{name: 'available',	type: 'boolean'},
			{name: 'reason',	type: 'string'}
		]
	});
	
	var amazon_deployment_form = Ext.create('Ext.form.Panel', {
		id: 'amazon_image_deployment_form',
		url: '/amazon/launch_instance',
		frame: true,
		border: false,
		height: 80,
		minHeight: 80,
		baseCls: 'x-plain',
		pollForChanges: true,
		fieldDefaults: {
			labelWidth: 100,
			anchor: '100%'
		},
		
		items: [{
			xtype: 'textfield',
			fieldLabel: 'Server Name',
			name: 'instance_name',
			allowBlank: false,
			vtype: 'alphanum'
		}, {
			xtype: 'combo',
			fieldLabel: 'Server Type',
			allowBlank: false,
			editable: false,
			store: Ext.create('Ext.data.Store', {
				model: 'Amazon_instance_type',
				proxy: {
					type: 'ajax',
					url: '/amazon/get_available_instance_types',
					reader: {
						type: 'json',
						root: 'types'
					}
				}
			}),
			queryMode: 'remote',
			name: 'instance_type',
			displayField: 'name',
			valueField: 'name', // POST-var value
			emptyText: 'Select type',
			tpl: '<tpl for="."><div ext:qtip="{reason}" class="x-combo-list-item">{name}</div></tpl>',
			forceSelection: true,
			triggerAction: 'all',
			listeners: {
				select: function(combo, record){
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
			handler: image_deploy_form_submitter
		},{
			text: 'Cancel',
			handler: function(){
				this.up('window').hide();
			}
		}]
	});
	
	Ext.apply(Ext.form.VTypes, {
		IPAddress:  function(v) {
			return /^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/.test(v);
		},
		IPAddressText: 'Must be a numeric IP address',
		IPAddressMask: /[\d\.]/i
	});
	
	Ext.create('Ext.window.Window', {
		title: 'Deploy Amazon Image',
		layout: 'fit',
		width: 300,
		minWidth: 200,
		closeAction: 'hide',
		plain: 'true',
		modal : true,
		bodyStyle: 'padding:5px;',
		items: amazon_deployment_form
	});
	
	Ext.define('Gogrid_ip_address', {
		extend: 'Ext.data.Model',
		fields: [{name: 'address', type: 'string'}]
	});
	
	Ext.define('Gogrid_ram_size', {
		extend: 'Ext.data.Model',
		fields: [
			{name: 'size',			type: 'string'},
			// {name: 'available',	type: 'boolean'},
			{name: 'description',	type: 'string'}
		]
	});
	
	var gogrid_deployment_form = Ext.create('Ext.form.Panel', {
		id: 'gogrid_image_deployment_form',
		url: '/gogrid/launch_instance',
		frame: true,
		border: false,
		height: 110,
		minHeight: 110,
		baseCls: 'x-plain',
		pollForChanges: true,
		fieldDefaults: {
			labelWidth: 100,
			anchor: '100%'
		},
		
		items: [{
			xtype: 'textfield',
			fieldLabel: 'Server Name',
			name: 'name',
			allowBlank: false
		}, {
			xtype: 'combo',
			fieldLabel: 'IP address',
			name: 'address',
			allowBlank: false,
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
			displayField: 'address',
			valueField: 'address', // POST-var value
			autoSelect: true,
			forceSelection: true,
			triggerAction: 'all'
		}, {
			xtype: 'combo',
			fieldLabel: 'RAM size',
			name: 'ram',
			allowBlank: false,
			store: Ext.create('Ext.data.Store', {
				model: 'Gogrid_ram_size',
				proxy: {
					type: 'ajax',
					url: '/gogrid/get_available_ram_sizes',
					reader: {
						type: 'json',
						root: 'sizes'
					}
				}
			}),
			queryMode: 'remote',
			displayField: 'size',
			valueField: 'size', // POST-var value
			autoSelect: true,
			forceSelection: true,
			triggerAction: 'all',
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
			handler: image_deploy_form_submitter
		},{
			text: 'Cancel',
			handler: function(){
				this.up('window').hide();
			}
		}]
	});
	
	Ext.create('Ext.window.Window', {
		title: 'Deploy GoGrid Image',
		layout: 'fit',
		width: 450,
		minWidth: 400,
		closeAction: 'hide',
		plain: 'true',
		modal : true,
		bodyStyle: 'padding:5px;',
		items: gogrid_deployment_form
	});
	
	Ext.define('Rackspace_flavor', {
		extend: 'Ext.data.Model',
		fields: [
			{name: 'id',	type: 'string'},
			{name: 'name',	type: 'string'},
			{name: 'disc',	type: 'string'},
			{name: 'ram',	type: 'string'}
		]
	});
	
	var rackspace_deployment_form = Ext.create('Ext.form.Panel', {
		id: 'rackspace_image_deployment_form',
		url: '/rackspace/launch_instance',
		frame: true,
		border: false,
		height: 80,
		minHeight: 80,
		baseCls: 'x-plain',
		pollForChanges: true,
		fieldDefaults: {
			labelWidth: 100,
			anchor: '100%'
		},		
		items: [{
			xtype: 'textfield',
			fieldLabel: 'Server Name',
			name: 'name',
			allowBlank: false,
			maxLength: 20
		}, {
			xtype: 'combo',
			fieldLabel: 'Flavor',
			allowBlank: false,
			store: Ext.create('Ext.data.Store', {
				model: 'Rackspace_flavor',
				proxy: {
					type: 'ajax',
					url: '/rackspace/get_flavors',
					reader: {
						type: 'json',
						root: 'flavors'
					}
				}
			}),
			queryMode: 'remote',
			name: 'flavor_id',
			displayField: 'name',
			valueField: 'id', // POST-var value
			tpl: '<tpl for="."><div ext:qtip="{ram}MB RAM, {disk}GB storage" class="x-combo-list-item">{name}</div></tpl>',
			autoSelect: true,
			forceSelection: true,
			triggerAction: 'all',
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
			handler: image_deploy_form_submitter
		},{
			text: 'Cancel',
			handler: function(){
				this.up('window').hide();
			}
		}]
	});
	
	Ext.create('Ext.window.Window', {
		title: 'Deploy Rackspace Image',
		layout: 'fit',
		width: 450,
		minWidth: 400,
		closeAction: 'hide',
		plain: 'true',
		modal : true,
		bodyStyle: 'padding:5px;',
		items: rackspace_deployment_form
	});

	var images_menu = Ext.create('Ext.menu.Menu', {
		items: [{
			text: 'Deploy Image',
			iconCls: 'buy-button',
			handler: function(){
				images_menu.hide();
				var image = images_menu.selected_image,
					provider = image.get('provider'),
					form_height = provider === 'Amazon' ? 128 : 152,
					form = Ext.getCmp(provider.toLowerCase() + '_image_deployment_form');

				form.getForm().reset().setValues({
					image_id: image.get('image_id')
				});
				
				form.up('window').show().center();
				return false;
			}
		}],
		selected_image: null
	});
	
	Ext.define('Images', {
		extend: 'Ext.data.Model',
		fields: [
			{name: 'id',			type: 'int'},
			{name: 'name',			type: 'string'},
			{name: 'image_id',		type: 'string'},
			{name: 'state',			type: 'string'},
			{name: 'description',	type: 'string'},
			{name: 'location',		type: 'string'},
			{name: 'provider',		type: 'string'}
		]
	});
	var images = Ext.create('Ext.data.Store', {
		model: 'Images',
		groupField: 'provider',
		proxy: {
			type: 'ajax',
			url: '/common/available_images',
			reader: {
				type: 'json',
				root: 'images'
			}
		}
	});
	
	var images_grid = new Ext.grid.GridPanel({
		id: 'available_images-panel',
		title: 'Images available for deployment',
		store: images,
		loadMask: true,
		forceFit: true,
		border: false,
		emptyText: '<p style="text-align: center">No images are available for deployment</p>',
		features: [Ext.create('Ext.grid.feature.Grouping', {
			groupHeaderTpl: 'Provider: {name} ({rows.length} {[values.rows.length > 1 ? "Images" : "Image"]})'
		})],
		listeners: {
			itemcontextmenu: function (view, record, item, index, e) {
				e.preventDefault();
				images_menu.selected_image = record;
				images_menu.showAt(e.getXY());
			},
			activate: function(p){
				var store = p.getStore();
				if(store.last() === undefined) store.load();
			}
		},
		columns: [
			{header: "Name", dataIndex: 'name', width: 100, flex: 1},
			{header: "Provider", dataIndex: 'provider', hidden: true},
			{header: "State", dataIndex: 'state', width: 70, flex: 1},
			{header: "Description", dataIndex: 'description', width: 170, flex: 1},
			{header: "Location", dataIndex: 'location', width: 120, flex: 1}
		],
		bbar: {
			xtype: 'toolbar',
			items: ['->', {
				xtype: 'button',
				text: 'Refresh List',
				cls: 'x-btn-text-icon',
				iconCls: 'restart',
				handler: function(){
					images.load();
				}
			}]
		}
	});
	
	return {
		get_grid: function(){
			return images_grid;
		}
	};
}();