//Invoke strict mode
"use strict";

SU('supra.form', function (Y) {
	
	var DEFAULT_CONFIG = {
		'message': '',
		'buttons': []
	};
	
	//Shortcut
	var Action = SU.Manager.Action;
	
	//Create Action class
	new Action(Action.PluginPanel, Action.PluginFooter, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'Confirmation',
		
		HAS_STYLESHEET: true,
		
		/**
		 * Render message
		 * 
		 * @param {Object} config
		 * @private
		 */
		renderMessage: function (config) {
			this.getContainer('p').set('innerHTML', Y.Lang.escapeHTML(config.message || ''));
		},
		
		/**
		 * Create buttons
		 * 
		 * @param {Object} config
		 * @private
		 */
		renderButtons: function (config) {
			var footer = this.getPluginWidgets('PluginFooter', true)[0],
				buttons = footer.getButtons(),
				button = null;
			
			//Remove old buttons
			for(var id in buttons) footer.removeButton(id);
			
			//Add new buttons
			buttons = config.buttons;
			for(var i=0,ii=buttons.length; i<ii; i++) {
				footer.addButton(buttons[i]);
				button = footer.getButton(buttons[i].id);
				button.on('click', this.hide, this);
				
				if ('click' in buttons[i]) {
					button.on('click', buttons[i].click, buttons[i].context || null);
				}
			}
		},
		
		execute: function (config) {
			this.config = SU.mix({}, DEFAULT_CONFIG || {}, config);
			
			this.renderMessage(config);
			this.renderButtons(this.config);
			
			//Show in the middle of the screen
			this.panel.centered();
		}
	});
	
});