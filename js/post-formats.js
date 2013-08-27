window.wp = window.wp || {};

(function($) {

var Format = Backbone.Model.extend({});

var TinyMCEView = Backbone.View.extend({
	mode: Format,

	el: '#postdivrich',

	render: function() {
		$('#post-format-ui').empty();
		this.showMetaBoxes();
		this.$el.show().find('.wp-editor-area').removeAttr('disabled');

		return this;
	},

	showMetaBoxes: function() {
		$('.postbox').each(function(){
			$(this).removeClass('cpffs-hidden');
		});

		$('.metabox-prefs label').each(function(){
			$(this).removeClass('cpffs-hidden');
		});
	}
});

var FormatView = Backbone.View.extend({
	model: Format,

	el: '#post-format-ui',

	initialize: function() {
		this.template = _.template( $('#format-type').html() );
	},

	render: function() {
		this.$el.html(this.template(this.model.toJSON()));
		this.hideMetaboxes();

		return this;
	},

	hideMetaboxes: function() {
		var t = this;

		$('.postbox').each(function(){
			if( $.inArray( $(this).attr('id'), t.model.get("hideBoxes") ) < 0 )
				$(this).addClass('cpffs-hidden');
		});

		$('.metabox-prefs label').each(function(){
			if( $.inArray( $(this).attr('for'), t.model.get("hideLabels") ) < 0 )
				$(this).addClass('cpffs-hidden');
		});

		$(function(){

			var mediaFrame;

			// Media selection
			$('.wp-format-media-select').click( function( event ) {
				event.preventDefault();
				var $el = $(this), mime = 'image',
					$holder = $el.closest('.wp-format-media-holder'),
					$field = $( '#wp_format_' + $holder.data('format') );

				mime = $holder.data('format');

				// If the media frame already exists, reopen it.
				if ( mediaFrame && lastMimeType === mime ) {
					mediaFrame.open();
					return;
				}

				lastMimeType = mime;

				mediaFrame = wp.media.frames.formatMedia = wp.media( {
					button: {
						text: $el.data('update')
					},
					states: [
						new wp.media.controller.Library({
							library: wp.media.query( { type: mime } ),
							title: $el.data('choose'),
							displaySettings: 'image' === mime
						})
					]
				} );

				mediaPreview = function(attachment) {
					var w, h, dimensions = '', url = attachment.url, mime = attachment.mime, format = attachment.type;

					if ( 'video' === format ) {
						if ( attachment.width ) {
							w = attachment.width;
							if ( w > 600 )
								w = 600;
							dimensions += ' width="' + w + '"';
						}

						if ( attachment.height ) {
							h = attachment.height;
							if ( attachment.width && w < attachment.width )
								h = Math.round( ( h * w ) / attachment.width );
							dimensions += ' height="' + h + '"';
						}
					}

					$('#' + format + '-preview').remove();
					$holder.parent().prepend( '<div id="' + format + '-preview" class="wp-format-media-preview">' +
						'<' + format + dimensions + ' class="wp-' + format + '-shortcode" controls="controls" preload="none">' +
							'<source type="' + mime + '" src="' + url + '" />' +
						'</' + format + '></div>' );
					$('.wp-' + format + '-shortcode').mediaelementplayer();
				};

				// When an image is selected, run a callback.
				mediaFrame.on( 'select', function() {
					// Grab the selected attachment.
					var w = 0, h = 0, html, attachment = mediaFrame.state().get('selection').first().toJSON();

					if ( 0 === attachment.mime.indexOf('audio') ) {
						$field.val(attachment.url);
						// show one preview at a time
						mediaPreview(attachment);
					} else if ( 0 === attachment.mime.indexOf('video') ) {
						attachment.src = attachment.url;
						$field.val(wp.shortcode.string({
							tag:     'video',
							attrs: _.pick( attachment, 'src', 'width', 'height' )
						}));
						// show one preview at a time
						mediaPreview(attachment);
					} else {
						html = wp.media.string.image({
							align : getUserSetting('align'),
							size : getUserSetting('imgsize'),
							link : getUserSetting('urlbutton')
						}, attachment);
						// set the hidden input's value
						$field.val(html);
						$('#image-preview').remove();
						if ( attachment.width )
							w = attachment.width > 600 ? 600 : attachment.width;
						if ( attachment.height )
							h = attachment.height;
						if ( w < attachment.width )
							h = Math.round( ( h * w ) / attachment.width );
						$holder.parent().prepend( ['<div id="image-preview" class="wp-format-media-preview">',
							'<img src="', attachment.url, '"',
							w ? ' width="' + w + '"' : '',
							h ? ' height="' + h + '"' : '',
							' />',
						'</div>'].join('') );
					}
				});

				mediaFrame.open();
			});
		});		

		// $('#postdivrich').hide().find('.wp-editor-area').attr('disabled', 'disabled');
	}
});

var postFormatInfoView = Backbone.View.extend({
	el: '#post-format-fs-info-container',

	model: Format,

	template: _.template($('#post-format-fs-info').html()),

	render: function() {
		this.$el.html(this.template(this.model.toJSON()));
	}
});

var descriptionView = Backbone.View.extend({
	el: '#change-post-format-ui',

	model: Format,

	template: _.template($('#change-post-format').html()),

	render: function() {
		this.$el.html(this.template(this.model.toJSON()));
		return this;
	}
});

var FormatViews = Backbone.View.extend({
	el: '.wrap',

	model: Format,

	events: {
		'click .post-format-options a' :  'changePostFormat',
		'click #change-post-format-ui'  :  'showPostFormat',
	},

	initialize: function() {
		var t = this;
		this.subviews = {};

		if( !cpffs.fs_info[0].fs_type ) {
			this.timeoutID = setTimeout(
				function(){
					t.changePostFormat();
				}, 10000
			);
		} else {
			this.changePostFormat(null, cpffs.fs_info[0].fs_type );
		}
	},

	render: function() {
		//Dump the post format ui (damn you tinymce!!!)
		$('#post-format-ui').empty();
		
		//Render subviews
		this.subviews.descriptionView.render();
		this.subviews.bodyView.render();
		this.subviews.infoView.render();

		return this;
	},

	showPostFormat: function() {
		$('.post-format-options').slideDown();
		$('.post-format-change').hide();
	},

	//We need to load the current post formats template
	changePostFormat: function(event, f) {
		var format, fomatAttrs;

		clearTimeout(this.timeoutID);

		$('.post-format-options').slideUp();

		if ( typeof f != 'undefined' ) {
			format = f;
		} else if ( typeof event != 'undefined' ) {
			event.preventDefault();
			format = $(event.currentTarget).attr('data-wp-format');
		} else {
			format = 'standard';
		}
		
		formatAttrs = {
			post_id: cpffs.post_id,
			hideBoxes: ['submitdiv', 'postimagediv', 'categorydiv', 'postcustom'],
			hideLabels: ['postimagediv-hide', 'categorydiv-hide', 'postcustom-hide'],
			inputs: [],
			formatType: format
		};

		switch( format ) {
			case 'image':
				formatAttrs.inputs = [
					{ el: 'textarea', label_name: 'Image embed code or URL', id: 'fs_url' },
					{   el: 'div', 
						format: format, 
						data_choose: 'Choose ' + format,
						data_update: 'Select ' + format,
						text: 'Select / Upload ' + format
					},
					{ el: 'input', type: 'text', label_name: 'Link URL', id: 'fs_url' }
				];
				formatAttrs.formatDescription = cpffs.all_post_formats[format].description;
			break;
			case 'link':
				formatAttrs.inputs = [
					{ el: 'input', type: 'text', label_name: 'Link URL:', id: 'fs_url' },
				];
				formatAttrs.formatDescription = cpffs.all_post_formats[format].description;				
			break;
			case 'quote':
				formatAttrs.inputs = [
					{ el: 'input', type: 'text', label_name: 'Quote Source', id: 'fs_byline' },
					{ el: 'input', type: 'text', label_name: 'Link URL:', id: 'fs_url' }
				];
				formatAttrs.formatDescription = cpffs.all_post_formats[format].description;
			break;
			case 'audio':
				formatAttrs.inputs = [
					{ el: 'textarea', label_name: 'Audio embed code or URL', id: 'fs_url' },
					{   el: 'div', 
						format: format, 
						data_choose: 'Choose ' + format,
						data_update: 'Select ' + format,
						text: 'Select ' + format + ' from media library'
					}
				];
				formatAttrs.formatDescription = cpffs.all_post_formats[format].description;
			break;			
			case 'video':
				formatAttrs.inputs = [
					{ el: 'textarea', label_name: 'Video embed code or URL', id: 'fs_url' },
					{   el: 'div', 
						format: format, 
						data_choose: 'Choose ' + format,
						data_update: 'Select ' + format,
						text: 'Select ' + format + ' from media library'
					}
				];
				formatAttrs.formatDescription = cpffs.all_post_formats[format].description;
			break;
			default:
				formatAttrs.formatDescription = cpffs.all_post_formats[format].description;

		}

		this.model = new Format(formatAttrs);

		if ( format === 'standard' || format === 'chat' ) {
			$( '#titlediv' ).show();
			this.subviews.bodyView = new TinyMCEView({});
		} else if ( format === 'status' || format === 'aside' ) {
			$( '#titlediv' ).hide();
			this.subviews.bodyView = new TinyMCEView({});
		} else if ( format === 'link' ) {
			$( '#titlediv' ).show();
			this.subviews.bodyView = new FormatView(
				{ model: this.model }
			);			
		} else {
			$( '#titlediv' ).show();
			this.subviews.bodyView = new FormatView({ model: this.model });
		}

		this.subviews.infoView = new postFormatInfoView({ model: this.model });
		this.subviews.descriptionView = new descriptionView({ model: this.model });

		this.render();
	}

});

new FormatViews();

} )( jQuery );