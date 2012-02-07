//Invoke strict mode
"use strict";

/**
 * Page header action:
 * title, language bar, version
 */
Supra('supra.languagebar', function (Y) {

	//Shortcut
	var Manager = Supra.Manager,
		Action = Manager.Action,
		Root = Manager.getAction('Root');
	
	//Create Action class
	new Action(Action.PluginContainer, {
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'PageHeader',
		
		/**
		 * No stylesheet for this action
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * No template for this action
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		/**
		 * Content placeholder
		 * @type {Y.Node}
		 */
		PLACE_HOLDER: Y.one('#cmsHeader'),
		
		
		
		/**
		 * Language bar
		 * @type {Object}
		 * @private
		 */
		languagebar: null,
		
		/**
		 * Has anything changed
		 * @type {Boolean}
		 * @private
		 */
		has_changes: false,
		
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 * @private
		 */
		initialize: function () {
			//Create language bar
			this.languagebar = new SU.LanguageBar({
				'locale': SU.data.get('locale'),
				'contexts': SU.data.get('contexts')
			});
			
			//Set available localizations
			var page = Manager.Page.getPageData();
			if (page && page.localizations) {
				this.setAvailableLocalizations(page.localizations);
			}
			
			//On change reload page
			this.languagebar.on('localeChange', function (evt) {
				if (evt.newVal != evt.prevVal) {
					var currentLocale = Supra.data.get('locale');
					var page = Manager.Page.getPageData();
					
					// No page loaded
					if ( ! page) {
						return;
					}
					
					if (page.localizations && evt.newVal in page.localizations) {
						//Change global locale and reload page
						Supra.data.set('locale', evt.newVal);
						
						var pageId = page.localizations[evt.newVal].page_id;
						Root.save(Root.ROUTE_PAGE.replace(':page_id', pageId));
					} else {
						//Warning about not exising translation
						Manager.executeAction('Confirmation', {
							'message': '{#page.page_doesnt_exist_in_locale#}',
							'useMask': true,
							'buttons': [{
								'id': 'ok',
								'label': Supra.Intl.get(['buttons', 'error'])
							}]
						});
						
						//Prevent language change
						evt.halt();
					}
				}
			}, this);
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			this.languagebar.render(this.one('.languages'));
		},
		
		/**
		 * Set version title
		 * 
		 * @param {String} title Version title
		 * @private
		 */
		setVersionTitle: function (title) {
			if (title == 'autosaved') {
				
				//User modified content
				this.has_changes = true;
				
			} else if (title == 'draft') {
				
				//User saved without making any modifications
				//If page was previously published, then it stays published
				var page_data = Supra.Manager.Page.getPageData();
				if (!this.has_changes && page_data && page_data.published) {
					title = 'published';
				}
				
			} else if (title == 'published') {
				
				//User published page
				this.has_changes = false;
				
			}
			
			var version = Supra.Intl.get(['page', 'version_' + title]);
			this.one('.version').set('text', version);
		},
		
		/**
		 * Set available locales, only these will be shown in locale dropdown
		 * 
		 * @param {Object} locales
		 */
		setAvailableLocalizations: function (locales) {
			if (!this.languagebar) return;
			
			var contexts = SU.data.get('contexts'),
				context = null,
				item = null,
				filtered = [];
			
			if (locales) {
				for(var i=0,ii=contexts.length; i<ii; i++) {
					context = contexts[i];
					
					item = {
						'title': context.title,
						'languages': []
					};
					
					for(var k=0,kk=context.languages.length; k<kk; k++) {
						if (context.languages[k].id in locales) {
							item.languages.push(context.languages[k]);
						}
					}
					
					if (item.languages.length) {
						filtered.push(item);
					}
				}
			} else {
				filtered = contexts;
			}
			
			this.languagebar.set('contexts', filtered);
			this.languagebar.set('locale', this.languagebar.get('locale'));
		},
		
		/**
		 * Execute action, update data
		 * 
		 * @param {Boolean} ignore_locale_change Don't update locale
		 */
		execute: function (ignore_locale_change) {
			//If SiteMap is visible, then don't show header
			if (Supra.Manager.getAction('SiteMap').get('visible')) return;
			
			this.show();
			
			var page = Manager.Page.getPageData();
			this.one('.page-title').set('text', page ? page.title : '')
								   .setAttribute('title', page ? page.title : '');
			
			this.setVersionTitle(page && page.published ? 'published' : 'draft');
			this.has_changes = false;
			
			//Don't change locale if page is loading
			if (!ignore_locale_change) {
				var locale = Supra.data.get('locale');
				this.languagebar.set('locale', locale);
			}
		}
	});
	
});