var Transferer = function(){
	
	// function to be used as a submit button handler to credentials form:
	var submit_credentials = function(){
		var title = 'Registering your credentials',
			success = 'Your credentials have been registered successfully',
			error = 'A problem has occurred when registering your credentials. Please try again';

		transfer_dialogue.hide();
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
	
	var amazon_transfer_form = new Ext.FormPanel({
		title: 'Amazon',
		url: '/amazon/set_user_credentials',		
		buttonAlign: 'center',
		baseCls: 'x-plain',
		monitorValid: true,
		autoHeight: true,
		labelWidth: 70,
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
			handler: function(){ transfer_dialogue.hide(); }
		}]
	});
	
	var rackspace_transfer_form = new Ext.FormPanel({
		title: 'Rackspace',
		url: '/rackspace/set_user_credentials',		
		buttonAlign: 'center',
		baseCls: 'x-plain',
		monitorValid: true,
		autoHeight: true,
		labelWidth: 70,
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
			handler: function(){ transfer_dialogue.hide(); }
		}]
	});
	var gogrid_transfer_form = new Ext.FormPanel({
		title: 'GoGrid',
		url: '/gogrid/set_user_credentials',		
		buttonAlign: 'center',
		baseCls: 'x-plain',
		monitorValid: true,
		autoHeight: true,
		labelWidth: 70,
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
			handler: function(){ transfer_dialogue.hide(); }
		}]
	});
	
	var transfer_dialogue = new Ext.Window({
		title: 'Upgrade to TenBrain premium!',
		layout: 'fit',
		width: 450,
		minWidth: 400,
		//border: false,
		closeAction: 'hide',
		items: new Ext.TabPanel({
            activeTab: 0,
            border: false,
            defaults:{autoHeight: true, bodyStyle: 'padding: 10px'},
			items: [amazon_transfer_form, rackspace_transfer_form, gogrid_transfer_form]
		}),
		plain: 'true',
		//bodyStyle: 'padding:5px;',
		modal : true
	});
	
	return {
		show_dialogue: function(){
			transfer_dialogue.show();
		}
	}
}();

Ext.onReady(function(){
	Transferer.show_dialogue();
	Ext.get('upgrader').addListener('click', function(){
	});
});