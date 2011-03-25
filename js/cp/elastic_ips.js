var Elastic_IPs = function(){
	var menu = new Ext.menu.Menu({
		id: 'elastic_ips_menu',
		items: [{
			id: 'associate',
			text: 'Associate',
			handler: function(item){
				var address = menu.selected_record.get('address');
				menu.hide();
				associator.getForm().reset().setValues({
					address: address
				});
				modal_window
					.setTitle('Associate an instance with an IP address ' + address)
					.setSize(370, 102).show().center()
					.getLayout().setActiveItem('elastic_ip_associator');					
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
					Ext.Msg.alert(title, 'This IP is not currently associated with any instance');
					return false;
				}
					
				Ext.Msg.confirm(title, 'Are you sure you want to disassociate the IP address ' + address + ' from the instance ' + instance + '?', function(button){
					if(button !== 'yes') return false;
					
					Ext.Msg.wait('An IP adress is being disassociated', title);
					Ext.Ajax.request({
						url: 'amazon/disassociate_address',
						params: {address: address},
						success: function(response){
							response = Ext.decode(response.responseText);
							var s = response.success;
							Ext.Msg.alert(title, s ? success : response.error_message || error);
							ip_store.reload();
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
					Ext.Msg.alert(title, 'This IP is currently associated with the instance ' + instance + ', therefore cannot be released now');
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
							ip_store.reload();
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
	
	var associator = new Ext.FormPanel({
		id: 'elastic_ip_associator',
		labelWidth: 60,
		frame: true,
		url: '/amazon/associate_elastic_ip',
		monitorValid: true,
		items: [{
			xtype: 'combo',
			width: 250,
			fieldLabel: 'Instance',
			allowBlank: false,
			editable: false,
			store: new Ext.data.JsonStore({
				url: '/amazon/get_short_instances_list',
				autoLoad: true,
				successProperty: 'success',
				root: 'instances',
				fields: ['instance_id', 'instance_name']
			}),
			mode: 'local', // !!! load the store once and for all )
			name: 'instance_name',
			displayField: 'instance_name',
			hiddenName: 'instance_id', // POST-var name
			valueField: 'instance_id', // POST-var value
			emptyText: 'Select an instance to associate',
			forceSelection: true,
			typeAhead: true
		}, {
			xtype: 'hidden',
			name: 'address'
		}],

		buttons: [{
			text: 'Proceed',
			formBind: true,
			handler: function(){
				var title = 'Associate an instance to IP address',
					success = 'The IP address has been successfully associated to an instance',
					error = 'A problem has occured while associating an IP address',
					name = associator.getForm().getFieldValues().name;

				modal_window.hide();
				Ext.Msg.wait('The IP address is being associated', title);
				associator.getForm().submit({
					success: function(form, action){
						var s = action.result.success;
						Ext.Msg.alert(title, s ? success : error, function(){
							ip_store.reload();
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
		title: 'Associate an elastic IP',
		layout: 'card',
		closeAction: 'hide',
		items: associator,
		activeItem: 0,
		border: false,
		modal : true
	});
	
	var ip_store = function(){
		var record = Ext.data.Record.create([
			'id',
			'address',
			'instance',
			'instance_dns'
		]);
		return new Ext.data.Store({
			url: '/amazon/elastic_ips',
			reader: new Ext.data.JsonReader({
				root: 'elastic_ips',
				successProperty: 'success',
				idProperty: 'id'
			}, record),
			autoLoad: true
		});
	}();

	var xg = Ext.grid, checkbox_sm = new xg.CheckboxSelectionModel();
	var grid = new xg.GridPanel({
		id: 'elastic_ips-panel',
		layout: 'fit',
		title: 'Elastic IPs',
		store: ip_store,
		sm: checkbox_sm,
		colModel: new xg.ColumnModel({
			columns: [
				checkbox_sm,
				{header: "Address", dataIndex: 'address', width: 80},
				{header: "Instance", dataIndex: 'instance', width: 120, renderer: function(value){
					return value || '<i>not associated</i>';
				}},
				{header: 'Test address', dataIndex: 'address', width: 80, renderer: function(value){
					return '<a target="_blank" href="http://' + value + '/">' + value + '</a>';
				}},
				{header: "Link to instance", dataIndex: 'instance_dns', width: 300, renderer: function(value){
					return '<a target="_blank" href="http://' + value + '/">' + value + '</a>';
				}}
			]
		}),
		viewConfig: {
			forceFit: true,
			emptyText: '<p style="text-align: center">No elastic IPs have been allocated</p>'
		},
		listeners: {
			rowcontextmenu: function (grid, id, e) {
				var record = this.getStore().getAt(id),
					associated = !!record.get('instance');
				
				menu.selected_record = record;	
				menu.get('disassociate').setDisabled(!associated);
				menu.get('release').setDisabled(associated);
				
				e.preventDefault();				
				menu.showAt(e.getXY());
			}
		},
		tbar: {
			xtype: 'toolbar',
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
								var s = response.success;
								Ext.Msg.alert(title, s ? success : response.error_message || error, function(){
									if(!s) return false;
									// view the association dialogue if things went fine
									var address = response.address;
									associator.getForm().reset().setValues({
										address: address
									});
									modal_window
										.setTitle('Associate an instance with an IP address ' + address)
										.setSize(370, 102).show().center()
										.getLayout().setActiveItem('elastic_ip_associator');
								});
								ip_store.reload();
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
					var selected = checkbox_sm.getSelections(), ips = [],
						title = 'Release elastic IP addresses',
						success = 'Selected elastic IPs address have been successfully released',
						error = 'A problem has occurred when releasing one or more of the selected elastic IP addresses';

					if(!checkbox_sm.getCount())
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
								ip_store.reload();
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
					ip_store.reload();
				}
			}]
		}
	});
	return {
		get_grid: function(){
			return grid;
		}
	}
}();