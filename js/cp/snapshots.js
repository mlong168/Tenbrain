var Snapshots = function(){
	var store = function(){
		var record = Ext.data.Record.create([
			'id',
			'snapshot_id',
			'name',
			'capacity',
			'description',
			'status',
			'progress',
			'started'
		]);
		return new Ext.data.Store({
			url: '/amazon/created_snapshots',
			reader: new Ext.data.JsonReader({
				root: 'snapshots'
			}, record),
			autoLoad: true
		});
	}();
	var reload_until_stable = function(){
		var starter = 8000, interval = starter, minimum_interval = 1000, jump = 2000, stable_state = 'completed';
		return function(state, step){
			state = state || stable_state;
			step = step || jump;
			
			// reset the initial timeout when called from outside itself
			if(this !== reload_until_stable)
			{
				interval = starter;
			}
			
			store.reload({
				callback: function(r){
					for(var i = r.length; i--;)
					{
						if(r[i].data.state !== state)
						{
							setTimeout(function(){
								/**
								 * here we simulate the call of function from within itself
								 * we do it by calling it from it's scope, so, later we can
								 * check smth like: this !== reload_until_stable
								 */
								reload_until_stable.call(reload_until_stable, state, step);
							}, interval);
							if(interval + step > minimum_interval) interval -= step;
							break;
						}
					}
				}
			});
			return false;
		};
	}();	

	var creator = new Ext.FormPanel({
		id: 'snapshot_creator',
		labelWidth: 70,
		url: '/amazon/create_snapshot',
		frame: true,
		floating: true,
		title: 'Create a Snapshot for the instance',
		width: 350,
		height: 120,
		// bodyStyle: 'padding: 5px 0 5px 5px',
		hidden: true,
		monitorValid: true,

		items: [{
			xtype: 'hidden',
			name: 'instance_id'	
		}, {
			xtype: 'textfield',
			width: 150,
			fieldLabel: 'Name',
			name: 'name',
			allowBlank: false,
			vtype: 'alphanum'
		}, {
			xtype: 'textfield',
			width: 250,
			fieldLabel: 'Description',
			name: 'description',
			allowBlank: false,
			vtype: 'alphanum'
		}],

		buttons: [{
			text: 'Proceed',
			formBind: true,
			handler: function(){
				creator.getForm().submit({
					success: function(form, action){
						creator.hide();
						Ext.Msg.alert(action.result.success
							? 'The snapshot has been created successfully'
							: 'A problem occured while creating your snapshot');
						Snapshots.reload_until_stable();
						console.log(this === Snapshots);
					},
					failure: function(form, action){
						Ext.Msg.alert('A problem occured while creating your snapshot');
					}
				});
			}
		},{
			text: 'Cancel',
			handler: function(){
				creator.hide();
			}
		}]
	});
	
	var instance_snapshots_grid = new Ext.grid.GridPanel({
		border: false,
		store: store,
		loadMask: true,
		cm: new Ext.grid.ColumnModel({
			defaultSortable: false,
			columns: [
				{header: "Name", dataIndex: 'name', width: 80},
				{header: "Description", dataIndex: 'description', id: 'description', width: 150},
				{header: "Capacity", dataIndex: 'capacity', width: 60},
				{header: "Status", dataIndex: 'status', width: 70},
				{header: "Start Time", dataIndex: 'started', width: 100},
				{header: "Progress", dataIndex: 'progress', width: 60}
			]
		}),
		view: new Ext.grid.GridView({
			// forceFit: true,
			emptyText: '<p style="text-align: center">No snapshots were created for this instance</p>'
		}),
		autoExpandColumn: 'description'
	});
	var instance_snapshots = new Ext.Window({
		title: 'Instance snapshots',
		height: 250,
		width: 700,
		layout: 'fit',
		closeAction: 'hide',
		items: instance_snapshots_grid
	});
	
	return {
		get_store: function(){ return store; },
		reload_store: function(){
			store.reload();
		},
		reload_until_stable: reload_until_stable,
		
		get_creator: function(){ return creator; },
		create: function(instance_id){
			creator.getForm().reset().setValues({instance_id: instance_id});			
			creator.setPosition(200, 100).show();
			return false;
		},
		
		get_instance_snapshots: function(){ return instance_snapshots; },
		show_instance_snapshots: function(instance_id){
			store.reload({
				params: {
					instance_id: instance_id
				}
			});
			instance_snapshots.show();
			return false;
		}		
	};
}();

var snapshots = new Ext.grid.GridPanel({
	id: 'snapshots-panel',
	title: 'Created snapshots',
	layout: 'fit',
	store: Snapshots.get_store(),
	bbar: {
		xtype: 'toolbar',
		items: ['->', {
			xtype: 'button',
			text: 'Refresh List',
			handler: function(){
				Snapshots.reload_store();
			}
		}]
	},
	view: new Ext.grid.GridView({
		forceFit: true,
		emptyText: '<p style="text-align: center">You have not created any snapshot so far</p>'
	}),
	cm: new Ext.grid.ColumnModel({
		defaultSortable: false,
		columns: [
			{width: 25, fixed: true, renderer: function(value, metadata, record){
				if(record.data.status !== 'completed') metadata.css = 'grid-loader';
			}},
			{header: "Name", dataIndex: 'name', width: 150},
			{header: "Description", dataIndex: 'description', width: 100},
			{header: "Capacity", dataIndex: 'capacity', width: 120},
			{header: "Status", dataIndex: 'status', width: 100},
			{header: "Start Time", dataIndex: 'started', width: 100},
			{header: "Progress", dataIndex: 'progress', width: 100}
		]
	})
});