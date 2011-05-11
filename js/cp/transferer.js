var Transferer = function(){
	
	// function to be used as a submit button handler to credentials form:
	var submit_credentials = function(){
		var title = 'Registering your credentials',
			success = 'Your credentials have been registered successfully',
			error = 'A problem has occurred when registering your credentials. Please try again';

		credentials_dialogue.hide();
		this.findParentByType('form').getForm().submit({
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
	
	var amazon_credentials_form = new Ext.FormPanel({
		title: 'Amazon',
		url: '/amazon/set_user_credentials',		
		buttonAlign: 'center',
		baseCls: 'x-plain',
		autoHeight: true,
		pollForChanges: true,
		fieldDefaults: {
			labelWidth: 70
		},
		defaults: {
			xtype: 'textfield'
		},
		items: [{
			width: 200,
			fieldLabel: 'Key',
			name: 'key',
			allowBlank: false
		}, {
			width: 300,
			fieldLabel: 'Secret Key',
			name: 'secret_key',
			allowBlank: false
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
	
	var rackspace_credentials_form = new Ext.FormPanel({
		title: 'Rackspace',
		url: '/rackspace/set_user_credentials',		
		buttonAlign: 'center',
		baseCls: 'x-plain',
		pollForChanges: true,
		fieldDefaults: {
			labelWidth: 70
		},
		autoHeight: true,
		defaults: {
			xtype: 'textfield'
		},
		items: [{
			width: 200,
			fieldLabel: 'Username',
			name: 'username',
			allowBlank: false
		}, {
			width: 300,
			fieldLabel: 'API Key',
			name: 'key',
			allowBlank: false
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
	var gogrid_credentials_form = new Ext.FormPanel({
		title: 'GoGrid',
		url: '/gogrid/set_user_credentials',		
		buttonAlign: 'center',
		baseCls: 'x-plain',
		autoHeight: true,
		pollForChanges: true,
		fieldDefaults: {
			labelWidth: 70
		},
		defaults: {
			xtype: 'textfield'
		},
		items: [{
			width: 200,
			fieldLabel: 'API Key',
			name: 'key',
			allowBlank: false
		}, {
			width: 300,
			fieldLabel: 'Secret Key',
			name: 'secret_key',
			allowBlank: false
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
	
	var credentials_dialogue = new Ext.Window({
		title: 'Cloud account credentials manager',
		layout: 'fit',
		width: 450,
		minWidth: 400,
		closeAction: 'hide',
		items: new Ext.TabPanel({
            activeTab: 0,
            border: false,
            defaults:{autoHeight: true, bodyStyle: 'padding: 10px'},
			items: [amazon_credentials_form, rackspace_credentials_form, gogrid_credentials_form]
		}),
		plain: 'true',
		modal : true
	});
	
	Ext.define('Account_types', {
		extend: 'Ext.data.Model',
		fields: [
			{name: 'name',			type: 'string'},
			{name: 'description',	type: 'string'}
		]
	});
	
	var type_switcher = new Ext.FormPanel({
		url: '/control_panel/change_user_account_type',
		baseCls: 'x-plain',
		autoHeight: true,
		buttonAlign: 'center',
		pollForChanges: true,
		fieldDefaults: {
			labelWidth: 70
		},

		items: [{
			xtype: 'combo',
			anchor: '100%',
			fieldLabel: 'Account Type',
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
			hiddenName: 'account_type', // POST-var name
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
					
				account_type_dialogue.hide();
				type_switcher.getForm().submit({
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
				account_type_dialogue.hide();
			}
		}]
	});
	
	var account_type_dialogue = new Ext.Window({
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
			account_type_dialogue.show();
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