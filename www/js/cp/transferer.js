var Transferer = function(){
	
	// function to be used as a submit button handler to credentials form:
	var submit_credentials = function(){
		var title = 'Registering your credentials',
			success = 'Your credentials have been registered successfully',
			error = 'A problem has occurred when registering your credentials. Please try again';

		this.up('window').hide();
		this.up('form').getForm().submit({
			waitTitle: title,
			waitMsg: 'Your credentials are being registered...',
			success: function(form, action){
				var response = action.result,
					s = response.success;
				Ext.Msg.alert(title, s ? success : response.error_message || error, function(){
					document.location.reload();
				});
			},
			failure: function(form, action){
				Ext.Msg.alert(title, action.result.error_message || error);
			}
		});
	};
	
	var add_existing_credentials = function(form, items){
		items.push({
			xtype: 'button',
			disabled: false,
			anchor: 'r',
			text: 'Delete this credentials and all its data',
			handler: function(){
				var fieldset = this.up('fieldset');
				fieldset.setLoading(true);
				setTimeout(function(){
					fieldset.setLoading(false);
					Ext.Msg.alert('API credentials removal', 'Your credentials have been removed successfully', function(){
						fieldset.collapse().disable();
					})
				}, 200);
			}
		});
		var fieldset = Ext.create('Ext.form.FieldSet', {
			checkboxToggle: true,
			title: 'Existing credentials',
			defaultType: 'textfield',
			collapsed: true,
			layout: 'anchor',
			defaults: {
				labelWidth: 70,
			    anchor: '100%',
			    disabled: true
			},
			items: items
		});
		var form_relayout = function(){
			form.doLayout()
		};
		fieldset.checkboxCmp.addListener('change', form_relayout);
		fieldset.addListener('destroy', form_relayout);
		form.items.insert(form.items.length - 1, fieldset);
		form_relayout();
	}
	
	var amazon_credentials_form = Ext.create('Ext.form.Panel', {
		id: 'amazon_credentials_form',
		title: 'Amazon',
		url: '/amazon/set_user_credentials',		
		buttonAlign: 'center',
		baseCls: 'x-plain',
		autoHeight: true,
		defaults: {
			xtype: 'textfield',
			allowBlank: false
		},
		items: [{
			xtype: 'fieldset',
			collapsible: true,
			title: 'Enter new API credentials',
			defaultType: 'textfield',
			layout: 'anchor',
			defaults: {
				labelWidth: 70,
			    anchor: '100%',
			    allowBlank: false
			},
			items: [{
				fieldLabel: 'Key',
				name: 'key'
			}, {
				fieldLabel: 'Secret Key',
				name: 'secret_key'
			}, {
				xtype: 'button',
				text: 'Submit',
				anchor: '15%',
				handler: function(){
					var form = this.up('form'), fieldset = this.up('fieldset');
					form.setLoading(true);
					Ext.Ajax.request({
						url: 'amazon/set_user_api_credentials',
						success: function(response){
							form.setLoading(false);
							response = Ext.decode(response.responseText);
							if(!response.success) return false;
							add_existing_credentials(form, [{
									fieldLabel: 'Key',
									name: 'key',
									// value: fieldset.items.get('key').value
									value: 'fieldset.items.get().value'
								}, {
									fieldLabel: 'Secret Key',
									name: 'secret_key',
									value: 'fkjlkdsfjlkds'
								}]);
			
						},
						failure: function(){
							form.setLoading(false);
						}
					})
				}
			}, {
				xtype: 'button',
				text: 'Reset',
				anchor: '15%'
			}]
		}]
	});
	
	amazon_credentials_form.addListener('activate', function(){
		var form = this;
		form.setLoading(true);
		Ext.Ajax.request({
			url: 'amazon/get_user_api_credentials',
			success: function(response){
				form.setLoading(false);
				response = Ext.decode(response);
				if(!response.success) return false;
				var credentials = response.credentials;
				if(typeof credentials !== undefined && credentials.length !== 0)
				{
					add_existing_credentials(form, [{
							fieldLabel: 'Key',
							name: 'key',
							value: credentials.key
						}, {
							fieldLabel: 'Secret Key',
							name: 'secret_key',
							value: credentials.secret_key
						}]);
				}

			},
			failure: function(){
				form.setLoading(false);
			}
		})
	})
	
	
	var rackspace_credentials_form = Ext.create('Ext.form.Panel', {
		title: 'Rackspace',
		url: '/rackspace/set_user_credentials',		
		buttonAlign: 'center',
		baseCls: 'x-plain',
		defaults: {
			xtype: 'textfield',
			anchor: '100%',
			labelWidth: 70,
			allowBlank: false
		},

		buttons: [{
			text: 'Proceed',
			formBind: true,
			handler: submit_credentials
		}, {
			text: 'Cancel',
			handler: function(){ this.up('window').hide(); }
		}]
	});
	
	
	// add_togglable_fieldSet(rackspace_credentials_form, 'Existing credentials', [{
			// fieldLabel: 'Username',
			// name: 'username',
			// disabled: true
		// }, {
			// fieldLabel: 'API Key',
			// name: 'key',
			// value: 'kfjlkgkjfskfsjglks',
			// disabled: true
		// }]);
// 	
	// add_togglable_fieldSet(rackspace_credentials_form, 'Enter new API credentials', [{
			// fieldLabel: 'Username',
			// name: 'username',
		// }, {
			// fieldLabel: 'API Key',
			// name: 'key',
		// }], false);
	
	var gogrid_credentials_form = Ext.create('Ext.form.Panel', {
		title: 'GoGrid',
		url: '/gogrid/set_user_credentials',		
		baseCls: 'x-plain',
		pollForChanges: true,
		defaults: {
			xtype: 'textfield',
			labelWidth: 70,
			allowBlank: false
		},
		items: [{
			width: 200,
			fieldLabel: 'API Key',
			name: 'key'
		}, {
			width: 300,
			fieldLabel: 'Secret Key',
			name: 'secret_key',
		}],

		buttons: [{
			text: 'Proceed',
			formBind: true,
			handler: submit_credentials
		}, {
			text: 'Cancel',
			handler: function(){ credentials_dialogue.hide(); }
		}]
	});
	
	var credentials_dialogue = Ext.create('Ext.window.Window', {
		title: 'Your personal cloud account manager',
		layout: 'fit',
		width: 450,
		minWidth: 400,
		closeAction: 'hide',
		plain: 'true',
		modal : true,
		items: new Ext.TabPanel({
            activeTab: 0,
            border: false,
            defaults:{autoHeight: true, bodyStyle: 'padding: 10px'},
			items: [amazon_credentials_form, rackspace_credentials_form, gogrid_credentials_form]
		})
	});
	
// Ext.onReady(function(){
	// credentials_dialogue.show();
// })
	
	Ext.define('Account_types', {
		extend: 'Ext.data.Model',
		fields: [
			{name: 'name',			type: 'string'},
			{name: 'description',	type: 'string'}
		]
	});
	
	var type_switcher = Ext.create('Ext.form.Panel', {
		url: '/control_panel/change_user_account_type',
		baseCls: 'x-plain',
		height: 60,
		pollForChanges: true,

		items: [{
			xtype: 'combo',
			name: 'account_type', // POST-var name
			anchor: '100%',
			fieldLabel: 'Account Type',
			labelWidth: 90,
			allowBlank: false,
			editable: false,
			store: Ext.create('Ext.data.Store', {
				model: 'Account_types',
				proxy: {
					type: 'ajax',
					url: '/control_panel/get_available_account_types',
					reader: {
						type: 'json',
						root: 'accounts'
					}
				}
			}),
			queryMode: 'remote',
			displayField: 'name',
			valueField: 'name', // POST-var value
			emptyText: 'Select type',
			tpl: '<tpl for="."><div ext:qtip="{description}" class="x-combo-list-item">{name}</div></tpl>',
			forceSelection: true,
			triggerAction: 'all'
		}],

		buttons: [{
			text: 'Proceed',
			formBind: true,
			handler: function(){
				var title = 'Account type change',
					success = 'The type of your account has been successfully changed',
					error = 'A problem has occurred while changing the type of your account';
					
				this.up('window').hide();
				this.up('form').getForm().submit({
					waitTitle: title,
					waitMsg: 'Changing your account type...',
					success: function(form, action){
						Ext.Msg.alert(title, action.result.success ? success : response.error_message || error);
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
		title: 'Change your account type',
		layout: 'fit',
		width: 300,
		minWidth: 300,
		closeAction: 'hide',
		items: type_switcher,
		bodyStyle: 'padding:5px;',
		plain: 'true',
		modal : true
	});
	
	return {
		manage_credentials: function(){
			credentials_dialogue.show();
		},
		manage_account_type: function(){
			type_switcher.up('window').show();
		}
	}
}();

Ext.onReady(function(){
	Ext.get('cloud_account_manager').addListener('click', function(){
		Transferer.manage_credentials();
	});
	
	Ext.get('account_type_changer').addListener('click', function(){
		Transferer.manage_account_type();
	});
});