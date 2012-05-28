//Invoke strict mode
"use strict";

/**
 * Header action, app dock
 */
Supra('supra.header', function (Y) {

	//Shortcut
	var Action = Supra.Manager.Action;
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'Header',
		
		/**
		 * No stylesheet for this action
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: false,
		
		/**
		 * No template for this action
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: false,
		
		
		
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 * @private
		 */
		initialize: function () {
			//Change srcNode
			this.set('srcNode', Y.all('#cmsHeader'));
			
			//Create application dock bar
			this.app = new Supra.AppDock({
				'data': Supra.data.get('application')
			});
			
			Supra.Manager.executeAction('LayoutContainers');
			Supra.Manager.executeAction('PageToolbar');
			Supra.Manager.executeAction('PageButtons');
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			this.app.render(this.one());
		}
	});
	
});