//Invoke strict mode
"use strict";

SU('supra.input', 'supra.slideshow', 'supra.tree', 'supra.medialibrary', function (Y) {
	
	//Shortcuts
	var Manager = SU.Manager,
		Action = Manager.Action,
		Loader = Manager.Loader;
	
	//Add as right bar child
	Manager.getAction('LayoutLeftContainer').addChildAction('LinkManager');
	
	//Create Action class
	new Action(Action.PluginContainer, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'LinkManager',
		
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
		 * Buttons
		 * @type {Object}
		 */
		button_cancel: null,
		button_back: null,
		button_remove: null,
		
		/**
		 * Link to file / link to page slideshow, Supra.Slideshow instance
		 * @type {Object}
		 */
		slideshow: null,
		
		/**
		 * Link slideshow, Supra.Slideshow instance
		 * @type {Object}
		 */
		link_slideshow: null,
		
		/**
		 * Media library list, Supra.MediaLibraryList instance
		 * @type {Object}
		 */
		medialist: null,
		
		/**
		 * Supra.Form instance
		 * @type {Object}
		 */
		form: null,
		
		/**
		 * New link or editing existing one
		 * @type {Boolean}
		 */
		is_new: false,
		
		/**
		 * Link data
		 * @type {Object}
		 */
		data: {},
		
		/**
		 * Original data with which link manager was opened
		 * @type {Object}
		 */
		original_data: null,
		
		/**
		 * Last known locale
		 * @type {String}
		 */
		locale: null,
		
		/**
		 * Link manager options
		 * @type {Object}
		 */
		options: null,
		
		
		
		/**
		 * Render widgets and add event listeners
		 */
		render: function () {
			//Toolbar buttons
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, []);
			
			//Slideshow widget
			this.slideshow = new Supra.Slideshow({
				'srcNode': this.one('div.slideshow')
			});
			
			this.slideshow.render();
			this.slideshow.after('slideChange', this.onSlideshowSlideChange, this);
			
			//
			var links = this.all('#linkToRoot a[data-slideshow]');
				links.on('click', this.onSlideshowLinkClick, this);
			
			
			//Back and Close buttons
			var buttons = this.all('button');
			
			this.button_back = new Supra.Button({'srcNode': buttons.filter('.button-back').item(0)});
			this.button_back.render();
			this.button_back.hide();
			this.button_back.on('click', this.scrollBack, this);
			
			this.button_close = new Supra.Button({'srcNode': buttons.filter('.button-close').item(0), 'style': 'mid-blue'});
			this.button_close.render();
			this.button_close.on('click', this.close, this);
			
			this.button_insert = new Supra.Button({'srcNode': buttons.filter('.button-insert').item(0), 'style': 'mid-green', 'visible': false});
			this.button_insert.render();
			this.button_insert.on('click', this.close, this);
			
			//Remove button
			var button = this.one('.yui3-sidebar-footer button');
			this.button_remove = new Supra.Button({'srcNode': button});
			this.button_remove.render();
			this.button_remove.on('click', this.removeLink, this);
			
			//When layout position/size changes update slide position
			Manager.LayoutLeftContainer.layout.on('sync', this.slideshow.syncUI, this.slideshow);
			
			//Create form
			this.form = new Supra.Form({
				'srcNode': this.one('form')
			});
			this.form.render();
		},
		
		/**
		 * On slideshow slide change update heading, button visibility
		 * and call appropriate callback function: onLinkToPage or onLinkToFile
		 */
		onSlideshowSlideChange: function (evt) {
			if (evt.newVal != evt.prevVal) {
				var heading = this.one('h2.yui3-sidebar-header span');
				
				if (this.slideshow.history.length <= 1) {
					this.button_back.hide();
					heading.hide();
				} else {
					this.button_back.show();
					heading.set('text', SU.Intl.get(['linkmanager', evt.newVal == 'linkToPage' ? 'title_page' : 'title_file']));
					heading.show();
				}
				
				var fn = 'on' + evt.newVal.substr(0,1).toUpperCase() + evt.newVal.substr(1);
				if (fn in this) {
					var node = this.slideshow.getSlide(evt.newVal);
					this[fn](node);
				}
			}
		},
		
		/**
		 * Hide action
		 */
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			
			//Show previous buttons
			Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
			
			//Hide action
			Manager.getAction('LayoutLeftContainer').unsetActiveAction(this.NAME);
		},
		
		/**
		 * Scroll back slideshow 
		 */
		scrollBack: function () {
			//Back button also controls medialist slideshow
			if (this.slideshow.get('slide') == 'linkToFile') {
				if (!this.medialist.slideshow.isRootSlide()) {
					this.medialist.slideshow.scrollBack();
					return;
				}
			}
			
			this.slideshow.scrollBack();
			this.updateButtonUI();
		},
		
		/**
		 * On slideshow link click navigate to slide
		 */
		onSlideshowLinkClick: function (e) {
			var target = e.target.closest('a'),
				id = target.getAttribute('data-slideshow');
			
			if (id) {
				this.slideshow.set('slide', id);
			}
		},
		
		/**
		 * On link slide create widgets, etc.
		 * 
		 * @param {Object} node
		 */
		onLinkToPage: function (node) {
			if (!this.link_slideshow) {
				//Internal / External
					//Create slideshow
					var slideshow = this.link_slideshow = (new Supra.Slideshow({
						'srcNode': node.one('div.slideshow')
					})).render();
					
					//On Internal / External switch show slide
					this.form.getInput('linkManagerType').on('change', function (evt) {
						var slide = 'linkManager' + evt.value.substr(0,1).toUpperCase() + evt.value.substr(1);
						slideshow.set('slide', slide);
					}, this);
					
					//On href change update button label
					this.form.getInput('href').on('change', this.updateButtonUI, this);
					
					//When layout position/size changes update slide position
					Manager.LayoutLeftContainer.layout.on('sync', this.link_slideshow.syncUI, this.link_slideshow);
					
					this.link_slideshow.on('slideChange', this.updateButtonUI, this);
				//Create tree
					//Use sitemap data
					this.locale = Supra.data.get('locale');
					var sitemap_data_path = SU.Manager.Loader.getActionInfo('Sitemap').path_data +
											'?locale=' + this.locale +
											'&published_only=1';
					
					//Create tree
					this.tree = new SU.Tree({
						srcNode: node.one('.tree'),
						requestUri: sitemap_data_path,
						groupNodesSelectable: this.options.groupsSelectable
					});
					this.tree.plug(SU.Tree.ExpandHistoryPlugin);
					this.tree.render();
					
					//On node change update button label
					this.tree.after('selectedNodeChange', this.updateButtonUI, this);
			}
			
			this.updateButtonUI();
		},
		
		/**
		 * On slide create widgets, etc.
		 * 
		 * @param {Object} node
		 */
		onLinkToFile: function (node) {
			if (!this.medialist) {
				//"Open App" button
					var btn = new Supra.Button({'srcNode': node.one('button'), 'style': 'mid'});
					btn.on('click', function () {
						Manager.executeAction('MediaLibrary');
						Manager.getAction('MediaLibrary').once('hide', function () {
							//Reload data
							this.medialist.reload();
						}, this);
					}, this);
					btn.render();
					
				//Create list widget
					var medialibrary = Manager.getAction('MediaLibrary');
					var list = this.medialist = (new Supra.MediaLibraryList({
						'srcNode': node.one('#linkToFileMediaList'),
						'foldersSelectable': false,
						'filesSelectable': false,
						'listURI': medialibrary.getDataPath('list'),
						'viewURI': medialibrary.getDataPath('view'),
						'displayType': Supra.MediaLibraryList.DISPLAY_ALL
					})).render();
					
					//On file select change button to "Insert"
					list.slideshow.after('slideChange', this.updateButtonUI, this);
			} else {
				this.medialist.reload();
			}
		},
		
		/**
		 * Restore state matching data
		 * 
		 * @param {Object} data
		 */
		setData: function (data) {
			this.original_data = data;
			this.is_new = !data;
			
			data = SU.mix({
				'type': '',
				'target': '',
				'title': '',
				'href': '',
				'page_id': null,
				'page_master_id': null,
				'file_id': null,
				'file_path': [],
				'file_title': '',
				'linktype': 'internal'
			}, data || {});
			
			//Show footer for existing link and hide for new link
			if (this.is_new) {
				this.one('.yui3-sidebar-footer').addClass('hidden');
				this.one('.yui3-sidebar-content').removeClass('has-footer');
			} else {
				this.one('.yui3-sidebar-footer').removeClass('hidden');
				this.one('.yui3-sidebar-content').addClass('has-footer');
			}
			
			//Since file title is different input 'title' is used to transfer data
			//reverse it
			if (data.title && !data.file_title) {
				data.file_title = data.title;
			}
			
			if (this.link_slideshow) {
				this.link_slideshow.set('noAnimations', true);
			}
			
			//If locale has changed since last time this action was opened then reload tree data
			var reloading_tree = false;
			if (this.locale && this.locale != Supra.data.get('locale')) {
				reloading_tree = true;
			}
			
			//If some option changed, then reload tree also
			if (this.tree && this.options.groupsSelectable != this.tree.get('groupNodesSelectable')) {
				this.tree.set('groupNodesSelectable', this.options.groupsSelectable);
				reloading_tree = true;
			}
			
			//Reload tree if needed
			if (reloading_tree) {
				this.locale = Supra.data.get('locale');
				var sitemap_data_path = SU.Manager.Loader.getActionInfo('SiteMap').path_data + '?locale=' + this.locale;
				
				this.tree.set('requestUri', sitemap_data_path);
				this.tree.reload();
			}
			
			//Reset tree selected node
			if (this.tree) {
				this.tree.set('selectedNode', null);
			}
			
			//Set values by input name
			this.form.setValues(data, 'name');
			
			this.data = data;
			this.slideshow.set('noAnimations', true);
			
			switch (data.resource) {
				case 'page':
					this.slideshow.set('slide', 'linkToPage');
					
					this.link_slideshow.set('noAnimations', true);
					this.form.getInput('linkManagerType').set('value', 'internal');
					this.link_slideshow.set('noAnimations', false);
					
					var key,
						value;

					// Supports selection by page ID or master ID
					if (data.page_id) {
						key = 'id';
						value = data.page_id;
					} else if (data.page_master_id) {
						key = 'master_id';
						value = data.page_master_id;
					}

					if (key) {
						var node = this.tree.getNodeBy(key, value);
						if (!node || reloading_tree) {
							this.tree.once('render:complete', function () {
								this.tree.set('selectedNode', null);

								var node = this.tree.getNodeBy(key, value);
								if (node) this.tree.set('selectedNode', node);
							}, this);
						} else {
							this.tree.set('selectedNode', node);
						}
					}
					
					break;
				case 'link':
					this.slideshow.set('slide', 'linkToPage');
					
					this.link_slideshow.set('noAnimations', true);
					this.form.getInput('linkManagerType').set('value', 'external');
					this.link_slideshow.set('noAnimations', false);
					
					break;
				case 'file':
					this.slideshow.set('slide', 'linkToFile');
					
					var path = [].concat(data.file_path, [data.file_id]);
					this.medialist.set('noAnimations', true);
					this.medialist.open(path);
					this.medialist.set('noAnimations', false);
					
					break;
				default:
					//Open root folder
					if (this.medialist) this.medialist.open(null);
					this.slideshow.set('slide', 'linkToRoot');
					
					break;
			}
			
			if (this.link_slideshow) {
				this.link_slideshow.set('noAnimations', false);
			}
			this.slideshow.set('noAnimations', false);
		},
		
		/**
		 * Returns link data
		 * 
		 * @return Link data
		 * @type {Object}
		 */
		getData: function () {
			var data = SU.mix(this.data || {}, this.form.getValues('name')),
				slide_id = this.slideshow.get('slide');
			
			if (slide_id == 'linkToPage') {
				if (data.linktype == 'internal') {
					//Link to page
					var tree_node = this.tree.get('selectedNode'),
						page_data = null,
						page_id = '',
						page_master_id = '',
						page_path = '';
					
					if (tree_node) {
						page_data = tree_node.get('data');
						if (page_data) {
							page_id = page_data.id;
							page_master_id = page_data.master_id;
							page_path = page_data.full_path || page_data.title;
						}
					}
					
					return {
						'resource': 'page',
						'page_id': page_id,
						'page_master_id': page_master_id,
						'href': page_path,
						'target': data.target,
						'title': data.title
					};
				} else {
					//Link to external resource
					return {
						'resource': 'link',
						'href': data.href,
						'target': data.target,
						'title': data.title
					};
				}
			} else if (slide_id == 'linkToFile') {
				//Link to file
				var item_data = this.medialist.getSelectedItem();
				if (!item_data) return;
				
				//File path for image is taken from original image
				var file_path = item_data.file_web_path;
				if (!file_path && item_data.type == Supra.MediaLibraryData.TYPE_IMAGE) {
					file_path = item_data.sizes.original.external_path;
				}
				
				return {
					'resource': 'file',
					'href': file_path,
					'target': '',
					'title': data.file_title || data.title,
					'file_id': item_data.id,
					'file_path': item_data.path
				};
			}
		},
		
		/**
		 * Returns tree page path
		 * 
		 * @param {Number} id
		 */
		getTreePagePath: function (id) {
			var data = this.tree.getIndexedData(),
				item = (id in data ? data[id] : null),
				list = [];
			 
			 while(item) {
			 	list.push(item.path);
				item = data[item.parent];
			 }
			 
			 return list.length > 1 ? list.reverse().join('/') + '/' : '/';
		},
		
		/**
		 * Set button label to "Insert" or "Close"
		 */
		updateButtonUI: function () {
			var show_insert = false;
			
			switch(this.slideshow.get('slide')) {
				case 'linkToPage':
					switch(this.link_slideshow.get('slide')) {
						case 'linkManagerInternal':
							//Tree tab
							if (this.tree.get('selectedNode')) {
								show_insert = true;
							}
							break;
						case 'linkManagerExternal':
							//External href input tab
							if (Y.Lang.trim(this.form.getInput('href').get('value'))) {
								show_insert = true;
							}
							break;
					}
					break;
				case 'linkToFile':
					//Media library tab
					var item = this.medialist.getSelectedItem();
					if (item && item.type != Supra.MediaLibraryData.TYPE_FOLDER) {
						show_insert = true;
					}
					break;
			}
			
			this.button_close.set('visible', !show_insert);
			this.button_insert.set('visible', show_insert);
		},
		
		/**
		 * Remove link and save data
		 */
		removeLink: function () {
			if (this.options.callback) {
				this.options.callback(null);
			}
			
			this.close(true);
		},
		
		/**
		 * Close and save data
		 */
		close: function (allow_remove) {
			if (this.options.callback) {
				var data = this.getData();
				
				if (allow_remove !== true) {
					//If not allowed to remove, then return original data
					data = data || this.original_data;
				}
				
				this.options.callback(data);
			}
			
			this.hide();
		},
		
		/**
		 * Execute action
		 */
		execute: function (data, options, callback) {
			if (SU.Y.Lang.isFunction(options)) {
				callback = options;
				options = null;
			}
			
			//Link manager options
			this.options = Supra.mix({
				'hideToolbar': false,
				'callback': callback,
				'groupsSelectable': false		//Virtual folders
			}, options || {});
			
			this.setData(data);
			
			if (this.options.hideToolbar) {
				Manager.getAction('PageToolbar').setActiveAction(this.NAME);
				Manager.getAction('PageButtons').setActiveAction(this.NAME);
			}
			
			Manager.getAction('LayoutLeftContainer').setActiveAction(this.NAME);
		}
	});
	
});