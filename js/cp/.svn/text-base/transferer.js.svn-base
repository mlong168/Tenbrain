var Transferer = function(){
	var transfer_form = new Ext.FormPanel({
		labelWidth: 70,
		frame: true,
		border: false,
		url: '/amazon/transfer_instances',		
		monitorValid: true,
		items: [{
			xtype: 'textfield',
			width: 200,
			fieldLabel: 'Key',
			name: 'key',
			value: 'AKIAIZPNXU6QOZESFUGQ',
			allowBlank: false
		}, {
			xtype: 'textfield',
			width: 350,
			fieldLabel: 'Secret Key',
			name: 'secret_key',
			value: 'Ss3kioejna+eIEeDkQkL9yWURZOo+H3EO2cUD7ea',
			allowBlank: false
		}],

		buttons: [{
			text: 'Proceed',
			formBind: true,
			handler: function(){
				var title = 'Transfer Instances',
					success = 'The account was transferred successfully',
					error = 'A problem has occured while transferring your instances';
				transfer_dialogue.hide();
				Ext.Msg.wait('Your instances are being transferred.<br />' +
					'The operation might take a couple of minutes depending on the number of instances you are running<br />' +
					'Please wait', title);
				Ext.Ajax.request({
					url: 'amazon/transfer_instances',
					params: transfer_form.getForm().getFieldValues(),
					timeout: 300*1000, // 5 minutes
					success: function(response){
						response = Ext.decode(response.responseText);
						var s = response.success;
						Ext.Msg.alert(title, s ? success : response.error_message || error, function(){
							document.location.reload();
						});
					},
					failure: function(){
						Ext.Msg.alert(title, error);
					}
				});
			}
		}, {
			text: 'Cancel',
			handler: function(){
				transfer_dialogue.hide();
			}
		}]
	});
	
	var transfer_dialogue = new Ext.Window({
		title: 'Upgrade to TenBrain premium!',
		height: 128,
		width: 450,
		closeAction: 'hide',
		items: transfer_form,
		border: false,
		modal : true
	});
	return {
		show_dialogue: function(){
			transfer_dialogue.show();
		}
	}
}();

Ext.onReady(function(){
	Ext.get('upgrader').addListener('click', function(){
		Transferer.show_dialogue();
	});
});