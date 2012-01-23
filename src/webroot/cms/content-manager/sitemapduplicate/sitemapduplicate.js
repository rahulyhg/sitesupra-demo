//Invoke strict mode
"use strict";

/**
 * Confirmation dialog
 * 
 * @example
 * 		Supra.Manager.executeAction('Confirmation', {
 * 			'message': 'Are you sure?',
 * 			'buttons': [
 * 				{'id': 'yes', 'click': function () { alert('Yes'); }, 'context': this},
 * 				{'id': 'no', 'label': 'No, some other time'},
 * 			]
 * 		});
 */
SU('supra.input', function (Y) {
	
	var DEFAULT_CONFIG = {
		'message': '',
		'escape': true,
		'useMask': true,
		'buttons': []
	};
	
	//Shortcut
	var Action = SU.Manager.Action;
	
	//Create Action class
	new Action(Action.PluginPanel, Action.PluginFooter, Action.PluginForm, {
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'SiteMapDuplicate',
		
		/**
		 * Load stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Load template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		/**
		 * Initialize
		 */
		initialize: function () {
			this.panel.set('zIndex', 105);
			this.panel.set('useMask', true);
		},
		
		/**
		 * On render bind listeners to prevent 'click' event propagation
		 */
		render: function () {
			
			var panel = this.panel;
			
			panel.get('boundingBox').on('click', this.stopPropagation);
			
			//When clicking on mask prevent propagation
			panel.once('maskNodeChange', function (event) {
				//Panel changed to use mask
				if (event.newVal) {
					event.newVal.on('click', this.stopPropagation);
				}
			}, this);
			
			//Callbacks
			this.footer.buttons.create.on('click', function () {
				if (this.config.on && this.config.on.create) {
					this.config.on.create.call(this.config.context, this.form.getInput('locale').get('value'));
				}
				
				this.panel.hide();
				this.config = null;
			}, this);
			this.footer.buttons.cancel.on('click', function () {
				if (this.config.on && this.config.on.cancel) {
					this.config.on.cancel.call(this.config.context);
				}
				
				this.panel.hide();
				this.config = null;
			}, this);
		},
		
		/**
		 * Stop event propagation
		 * 
		 * @param {Event} event Event object
		 * @private
		 */
		stopPropagation: function (event) {
			event.stopPropagation();
		},
		
		/**
		 * Set locales
		 * 
		 * @param {Array} locales Array of available locale ids
		 * @private
		 */
		setLocales: function (locales) {
			var values = [],
				contexts = Supra.data.get('contexts'),
				l = 0,
				ll = contexts.length,
				languages = null,
				k = 0,
				kk = 0;
			
			//Find titles for locales
			for(var i=0,ii=locales.length; i<ii; i++) {
				for(l=0; l<ll; l++) {
					languages = contexts[l].languages;
					for(k=0,kk=languages.length; k<kk; k++) {
						if (languages[k].id == locales[i]) {
							values.push({
								'id': languages[k].id,
								'title': languages[k].title
							});
						}
					}
				}
			}
			
			this.form.getInput('locale').set('values', values);
		},
		
		/**
		 * Execute action
		 */
		execute: function (config) {
			//Configuration
			this.config = SU.mix({
				'locales': [],
				'context': this,
				'on': {}
			}, DEFAULT_CONFIG, config || {});
			
			//Set locales
			this.setLocales(this.config.locales);
			
			//Show in the middle of the screen
			this.panel.centered();
		}
	});
	
});