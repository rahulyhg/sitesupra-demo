//Invoke strict mode
"use strict";

YUI().add('website.sitemap-settings', function (Y) {
	
	//Shortcut
	var Manager = SU.Manager,
		Action = Manager.Action;
	
	
	
	/**
	 * Page settings form
	 */
	function Plugin () {
		Plugin.superclass.constructor.apply(this, arguments);
	};
	
	Plugin.NAME = 'PluginSitemapSettings';
	
	Y.extend(Plugin, Action.PluginBase, {
		
		/**
		 * Plugin state
		 * @type {Boolean}
		 * @private
		 */
		initialized: false,
		
		/**
		 * Delete button, Supra.Button instance
		 * @type {Object}
		 * @private
		 */
		button_delete: null,
		
		/**
		 * Duplicate button, Supra.Button instance
		 * @type {Object}
		 * @private
		 */
		button_duplicate: null,
		
		/**
		 * Show hidden pages element, Y.Node instance
		 * @type {Object}
		 * @private
		 */
		node_hidden_pages: null,
		
		/**
		 * Selected page data
		 * @type {Object}
		 * @private
		 */
		data: null,
		
		
		/**
		 * @constructor
		 */
		initialize: function () {},
		
		initializeWidgets: function () {
			if (this.initialized) return;
			this.initialized = true;
			
			this.panel = new Supra.Panel({
				'srcNode': this.host.one('.sitemap-settings').removeClass('sitemap-settings'),
				'arrowPosition': ['L', 'C'],
				'arrowVisible': true,
				'constrain': SU.Manager.SiteMap.one(),
				'zIndex': 2
			});
			this.panel.get('boundingBox').addClass('sitemap-settings');
			this.panel.render(document.body);
			
			//On language change hide panel
			this.host.languagebar.on('localeChange', this.panel.hide, this.panel);
			
			//On document click hide panel
			var evt = null;
			var fn = function (event) {
				var target = event.target.closest('div.sitemap-settings');
				if (!target) this.hide();
			};
			
			//When panel is hidden remove 'click' event listener from document
			this.panel.on('visibleChange', function (event) {
				if (event.newVal) {
					if (evt) evt.detach();
					evt = Y.one(document).on('click', fn, this.panel);
				} else if (evt) {
					evt.detach();
				}
			}, this);
			
			this.addWidget(this.panel);
			
			//When host action is hidden also hide panel
			this.host.on('visibleChange', function (event) {
				if (!event.newVal && event.newVal != event.prevVal) {
					if (this.panel.get('visible')) {
						this.panel.hide();
					}
				}
			}, this);
			
			//On tree node toggle hide panel
			this.host.flowmap.on('toggle', this.panel.hide, this.panel);
			
			//Duplicate and delete buttons
			var contbox = this.panel.get('boundingBox'),
				buttons = contbox.all('button'),
				
				btn = this.button_duplicate = new Supra.Button({'srcNode': buttons.item(0), 'style': 'mid'});
				btn.render();
				btn.on('click', this.duplicatePage, this);
			
			if (!Supra.Authorization.isAllowed(['page', 'duplicate'], true)) {
				btn.hide();
			}
			
			var btn = this.button_delete = new Supra.Button({'srcNode': buttons.item(1), 'style': 'mid-red'});
				btn.render();
				btn.on('click', this.deletePage, this);
			
			if (!Supra.Authorization.isAllowed(['page', 'delete'], true)) {
				btn.hide();
			}
			
			//Hidden pages link
			this.node_hidden_pages = contbox.one('p.hidden-pages');
			this.node_hidden_pages.one('a').on('click', this.onShowHiddenPages, this);
			
		},
		
		/**
		 * Handle "Show hidden pages" link click
		 * 
		 * @param {Event} e Event
		 * @private
		 */
		onShowHiddenPages: function (e) {
			this.host.showHiddenPages(this.data.id);
			this.panel.hide();
		},
		
		/**
		 * Open property panel
		 * 
		 * @param {Object} data Page data
		 * @private
		 */
		showPropertyPanel: function (target, data, newpage) {
			this.initializeWidgets();
			this.data = data;
			
			//Position panel
			this.panel.set('align', {'node': target, 'points': [Y.WidgetPositionAlign.LC, Y.WidgetPositionAlign.RC]});
			
			//Position arrow
			if (target) {
				var type = this.host.getType(),
					node = this.host.flowmap.getNodeById(data.id),
					is_group = (data.type == 'group'),
					is_root = !!(node && node.isRoot());
				
				if (type != 'templates' && is_root) {
					//Root page
					this.button_delete.set('disabled', true);
					this.button_duplicate.set('disabled', true);
				} else {
					//Template or not a root page
					this.button_delete.set('disabled', false);
					this.button_duplicate.set('disabled', false);
				}
				
				if (type == 'templates') {
					this.button_delete.set('label', Supra.Intl.get(['sitemap', 'delete_template']));
					this.button_duplicate.set('label', Supra.Intl.get(['sitemap', 'duplicate_template']));
				} else {
					this.button_delete.set('label', Supra.Intl.get(['sitemap', 'delete_page']));
					this.button_duplicate.set('label', Supra.Intl.get(['sitemap', 'duplicate_page']));
				}
				
				if (data.has_hidden_pages) {
					this.node_hidden_pages.removeClass('hidden');
				} else {
					this.node_hidden_pages.addClass('hidden');
				}
				
				this.panel.show();
				this.panel.set('arrowAlign', target);
			}
		},
		
		/**
		 * Duplicate selected page or template
		 * 
		 * @private
		 */
		duplicatePage: function () {
			if (!this.data) return;
			
			//Send request to server
			var page_id = this.data.id,
				locale = this.host.languagebar.get('locale'),
				type = this.host.getType(),
				target = null,
				target_fn = null;
			
			if (type == 'templates') {
				target = Manager.getAction('Template');
				target_fn = 'duplicateTemplate';
			} else {
				target = Manager.getAction('Page');
				target_fn = 'duplicatePage';
			}
			
			target[target_fn](page_id, locale, function () {
				//Hide properties
				this.panel.hide();
				this.data = null;
				
				//Reload tree
				this.host.flowmap.reload();
				this.host.setLoading(true);
			}, this);
		},
		
		/**
		 * Delete selected page
		 * 
		 * @private
		 */
		deletePage: function () {
			if (!this.data) return;
			
			if (this.host.getType() == 'templates') {
				var message_id = 'message_delete_template';
			} else {
				var message_id = 'message_delete_page';
			}
			
			Manager.executeAction('Confirmation', {
				'message': Supra.Intl.get(['sitemap', message_id]),
				'useMask': true,
				'buttons': [
					{'id': 'delete', 'label': 'Yes', 'click': this.deletePageConfirm, 'context': this},
					{'id': 'no', 'label': 'No'}
				]
			});
		},
		
		/**
		 * After user confirmed page deletion collect page data and
		 * delete it
		 * 
		 * @private
		 */
		deletePageConfirm: function () {
			//Send request to server
			var page_id = this.data.id,
				locale = this.host.languagebar.get('locale'),
				type = this.host.getType(),
				target = null,
				target_fn = null;
			
			if (type == 'templates') {
				target = Manager.getAction('Template');
				target_fn = 'deleteTemplate';
			} else {
				target = Manager.getAction('Page');
				target_fn = 'deletePage';
			}
			
			target[target_fn](page_id, locale, function () {
				//Hide properties
				this.panel.hide();
				this.data = null;
				
				//Reload tree
				this.host.flowmap.reload();
				this.host.setLoading(true);
				
				//Reload recycle bin
				var recycle = Manager.getAction('SiteMapRecycle');
				if (recycle.get('visible')) {
					recycle.load();
				}
			}, this);
		}
		
	});
	
	Action.PluginSitemapSettings = Plugin;
	

	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.panel', 'supra.input', 'website.input-template']});
