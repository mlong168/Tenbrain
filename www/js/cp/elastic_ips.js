var Elastic_IPs = function(){
	var menu = new Ext.menu.Menu({
		id: 'elastic_ips_menu',
		items: [{
			id: 'associate',
			text: 'Associate',
			handler: function(item){
				var address = menu.selected_record.get('address'),
					modal_window = associator.up('window');
				menu.hide();
				associator.getForm().reset().setValues({
					address: address
				});
				modal_window.setTitle('Associate server with an IP address ' + address);
				modal_window.show().center();
			}
		}, {
			id: 'disassociate',
			text: 'Disassociate',
			handler: function(){
				var record = menu.selected_record,
					address = record.get('address'),
					instance = record.get('instance'),
					title = 'Disassociate an IP address',
					success = 'An elastic IP address has been successfully disassociated',
					error = 'A problem has occurred when disassociating an elastic IP address';
				
				menu.hide();				
				if(!instance)
				{
					Ext.Msg.alert(title, 'This IP is not currently associated with any server');
					return false;
				}
					
				Ext.Msg.confirm(title, 'Are you sure you want to disassociate the IP address ' + address + ' from the server ' + instance + '?', function(button){
					if(button !== 'yes') return false;
					
					Ext.Msg.wait('An IP adress is being disassociated', title);
					Ext.Ajax.request({
						url: 'amazon/disassociate_address',
						params: {address: address},
						success: function(response){
							response = Ext.decode(response.responseText);
							var s = response.success;
							Ext.Msg.alert(title, s ? success : response.error_message || error);
							ip_store.load();
						},
						failure: function(){
							Ext.Msg.alert(title, error);
						}
					});
				});
			}
		}, '-', {
			id: 'release',
			text: 'Release Address',
			handler: function(){
				var record = menu.selected_record,
					address = record.get('address'),
					instance = record.get('instance'),
					title = 'Release an IP address',
					success = 'An elastic IP address has been successfully released',
					error = 'A problem has occurred when releasing an elastic IP address';
					
				menu.hide();				
				if(instance)
				{
					Ext.Msg.alert(title, 'This IP is currently associated with the server ' + instance + ', therefore cannot be released now');
					return false;
				}
					
				Ext.Msg.confirm(title, 'Are you sure you want to release the IP address ' + address + '?', function(button){
					if(button !== 'yes') return false;
					
					Ext.Msg.wait('An IP adress is being released', title);
					Ext.Ajax.request({
						url: 'amazon/release_addresses',
						params: {addresses: Ext.encode(address)},
						success: function(response){
							response = Ext.decode(response.responseText);
							var s = response.success;
							Ext.Msg.alert(title, s ? success : response.error_message || error);
							ip_store.load();
						},
						failure: function(){
							Ext.Msg.alert(title, error);
						}
					});
				});
			}
		}],
		selected_record: null
	});
	
	Ext.define('Short_instance_list', {
		extend: 'Ext.data.Model',
		fields: [
			{name: 'instance_id',	type: 'string'},
			{name: 'instance_name',	type: 'string'}
		]
	});
	
	var associator = Ext.create('Ext.form.Panel', {
		url: '/amazon/associate_elastic_ip',
		frame: true,
		baseCls: 'x-plain',
		border: false,
		height: 60,
		minHeight: 60,
		pollForChanges: true,
		
		items: [{
			xtype: 'combo',
			anchor: '100%',
			labelWidth: 80,
			fieldLabel: 'Server',
			allowBlank: false,
			editable: false,
			store: Ext.create('Ext.data.Store', {
				model: 'Short_instance_list',
				proxy: {
					type: 'ajax',
					url: '/amazon/get_short_instances_list',
					reader: {
						type: 'json',
						root: 'instances'
					},
					
				},
			}),
			queryMode: 'remote',
			name: 'instance_name',
			displayField: 'instance_name',
			valueField: 'instance_id', // POST-var value
			emptyText: 'Select a server to associate',
			forceSelection: true,
			triggerAction: 'all'
		}, {
			xtype: 'hidden',
			name: 'address'
		}],

		buttons: [{
			text: 'Proceed',
			formBind: true,
			handler: function(){
				var title = 'Associate server to IP address',
					success = 'The IP address has been successfully associated to the server',
					error = 'A problem has occured while associating an IP address',
					name = associator.getForm().getFieldValues().name;

				this.up('window').hide();
				this.up('form').getForm().submit({
					waitTitle: title,
					waitMsg: 'The IP address is being associated',
					success: function(form, action){
						var s = action.result.success;
						Ext.Msg.alert(title, s ? success : error, function(){
							ip_store.load();
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
				this.up('window').hide();
			}
		}]
	});

	Ext.create('Ext.window.Window', {
		title: 'Associate an elastic IP',
		layout: 'fit',
		closeAction: 'hide',
		items: associator,
		width: 370,
		minWidth: 300,
		plain: 'true',
		bodyStyle: 'padding:5px;',
		modal : true
	});

	Ext.define('Elastic_ips', {
		extend: 'Ext.data.Model',
		fields: [
			{name: 'id',			type: 'int'},
			{name: 'address',		type: 'string'},
			{name: 'instance',		type: 'string'},
			{name: 'instance_dns',	type: 'string'}
		]
	});
	
	var ip_store = Ext.create('Ext.data.Store', {
		model: 'Elastic_ips',
		proxy: {
			type: 'ajax',
			url: '/amazon/elastic_ips',
			reader: {
				type: 'json',
				root: 'elastic_ips'
			}
		}
	});

	var checkbox_sm = Ext.create('Ext.selection.CheckboxModel');
	return {
		panels: {
			addresses: {
				xtype: 'grid',
				id: 'elastic_ips-panel',
				layout: 'fit',
				title: 'Elastic IPs',
				store: ip_store,
				selModel: checkbox_sm,
				columns: [
					{text: "Address", dataIndex: 'address', width: 150},
					{text: "Server", dataIndex: 'instance', width: 200, renderer: function(value){
						return value || '<i>not associated</i>';
					}},
					{text: 'Test address', dataIndex: 'address', width: 150, renderer: function(value){
						return '<a target="_blank" href="http://' + value + '/">' + value + '</a>';
					}},
					{text: "Link to server", dataIndex: 'instance_dns', flex: 1, renderer: function(value){
						return '<a target="_blank" href="http://' + value + '/">' + value + '</a>';
					}}
				],
				viewConfig: {
					emptyText: '<p style="text-align: center">No elastic IPs have been allocated</p>',
					loadingText: undefined
				},
				listeners: {
					itemcontextmenu: function (view, record, item, index, e) {
						var associated = !!record.get('instance');
						
						menu.selected_record = record;	
						menu.down('#disassociate').setDisabled(!associated);
						menu.down('#release').setDisabled(associated);
						
						e.preventDefault();				
						menu.showAt(e.getXY());
					},
					activate: Helpers.first_time_loader
				},
				dockedItems: [{
					xtype: 'toolbar',
					dock: 'top',
					items: [{
						xtype: 'button',
						text: 'Allocate',
						cls: 'x-btn-text-icon',
						iconCls: 'start',
						handler: function(){
							var title = 'Allocate an elastic IP address',
								success = 'An elastic IP address has been successfully allocated',
								error = 'A problem has occurred when allocating an elastic IP address';
								
							Ext.MessageBox.confirm(title, 'Are you sure you want to allocate an elastic IP address?', function(button){
								if(button !== 'yes') return false;
		
								Ext.Msg.wait('An IP adress is being allocated', title);
								Ext.Ajax.request({
									url: 'amazon/allocate_address',
									method: 'POST',
									success: function(response){
										response = Ext.decode(response.responseText);
										var s = response.success,
											modal_window = associator.up('window');
										Ext.Msg.alert(title, s ? success : response.error_message || error, function(){
											if(!s) return false;
											// view the association dialogue if things went fine
											var address = response.address;
											associator.getForm().reset().setValues({
												address: address
											});
											modal_window.setTitle('Associate a server with an IP address ' + address);
											modal_window.show().center();
										});
										ip_store.load();
									},
									failure: function(){
										Ext.Msg.alert(title, error);
									}
								});
							});
						}
					}, {
						xtype: 'button',
						text: 'Release',
						cls: 'x-btn-text-icon',
						iconCls: 'terminate',
						handler: function(){
							var selected = checkbox_sm.getSelection(), ips = [],
								title = 'Release elastic IP addresses',
								success = 'Selected elastic IPs address have been successfully released',
								error = 'A problem has occurred when releasing one or more of the selected elastic IP addresses';
		
							if(selected.length === 0)
							{
								Ext.Msg.alert('Warning', 'Please select some addresses to perform the action');
								return false;
							}
								
							Ext.MessageBox.confirm(title, 'Are you sure you want to release selected elastic IP addresses?', function(button){
								if(button !== 'yes') return false;
								for(var i = selected.length; i--;) ips.push(selected[i].data.address);

								Ext.Msg.wait('IP adresses are being released', title);
								Ext.Ajax.request({
									url: 'amazon/release_addresses',
									params: { addresses: Ext.encode(ips) },
									success: function(response){
										var r = Ext.decode(response.responseText), s = r.success;
										Ext.Msg.alert(title, s ? success : response.error_message || error);
										ip_store.load();
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
							ip_store.load();
						}
					}]
				}]
			}
		}
	}
}();