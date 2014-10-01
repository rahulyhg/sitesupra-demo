//Invoke strict mode
"use strict";

/**
 * Form action
 */
Supra(function (Y) {

	//Shortcut
	var Manager = Supra.Manager,
		Action = Manager.Action,
		CRUD = Supra.CRUD;
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'Form',
		
		/**
		 * Action doesn't have stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: false,
		
		/**
		 * Action doesn't have template, Supra.Slideshow and Supra.Form
		 * is used to generate markup
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: false,
		
		
		
		
		/**
		 * @constructor
		 */
		initialize: function () {
			//Toolbar buttons
			Manager.getAction('PageButtons').addActionButtons(this.NAME, [{
				'id': 'done',
				'context': this,
				'callback': this.save
			}]);
		
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);	
		},
		
		/**
		 * Save form
		 */
		save: function () {
			var form = this.getForm();
			var button = Supra.Manager.getAction('PageButtons').buttons[this.NAME][0];
			
			//Save
			form.save(function (data, status) {
				if (status) {
					//Reload data grid
					CRUD.Providers.getActiveProvider().getDataGrid().reset();
					CRUD.Providers.getActiveProvider().set('mode', 'list');
				}
				
				form.set('disabled', false);
				button.set('loading', false);
				
			}, this);
			
			//Disable form
			form.set('disabled', true);
			
			//Disable save button
			button.set('loading', true);
			
			// Hide media sidebar (if any)
			Manager.getAction('MediaSidebar').hide();
		},
		
		/**
		 * Returns form instance
		 * 
		 * @return Form instance
		 * @type {Supra.Form}
		 */
		getForm: function () {
			return CRUD.Providers.getActiveProvider().getForm();
		},
		
		/**
		 * On hide
		 */
		hide: function () {
			//Hide buttons
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
			Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
			
			//Change back to list mode
			CRUD.Providers.getActiveProvider().set('mode', 'list');
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			//Show buttons
			Manager.getAction('PageButtons').setActiveAction(this.NAME);
			Manager.getAction('PageToolbar').setActiveAction(this.NAME);
		}
	});
	
});