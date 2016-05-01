(function($) {
$( document ).ready( function ( e ) {
	
	$('#sending').change(function(){
		$('#sending_verify').hide();
		$('#dkim_check').hide();
		$('#spf_check').hide();
	});

	$('#sending_verify').click(function(e){
			e.preventDefault();
			e.target.blur();
			var verifyDomain = {
		        domain: $('#sending').val()
		    };
        	var data = new WPED.DKIM();
        	$('#dkim_check').removeClass('dashicons-yes').addClass('spin').addClass('dashicons-update').css('color','gray');
        	data.fetch(
        		{
        			data: verifyDomain,
			        success: function (dkim) {
			        	$('#dkim_check').addClass('dashicons-yes').removeClass('spin').removeClass('dashicons-update');
			           if(dkim.get('success') == true){
			           		$('#dkim_check').css('color','green');
			           } else {
			           		$('#dkim_check').css('color','red');
			           }
			        },
			        type: 'POST'
			    }
        	);
			$('#spf_check').removeClass('dashicons-yes').addClass('spin').addClass('dashicons-update').css('color','gray');
        	var spfdata = new WPED.SPF();
        	spfdata.fetch(
        		{
        			data: verifyDomain,
			        success: function (spf) {
			        	$('#spf_check').addClass('dashicons-yes').removeClass('spin').removeClass('dashicons-update');
			           if(spf.get('success') == true){
			           		$('#spf_check').css('color','green');
			           } else {
			           		$('#spf_check').css('color','red');
			           }
			        },
			        type: 'POST'
			    }
        	);

        });
 
    var AppRouter = Backbone.Router.extend({
        routes: {
            "wped/:action/:id": "defaultRoute", // matches http://example.com/#anything-here
            "wped/status":"wped_info",
            "wped/verify": "wped_verify"
            
        }
    });
    var WPED = {
    	Views: {}
    };
	WPED.Model = Backbone.Model.extend({
	        url : ajaxurl+'?action=wped_verify_sender',
	    });

	WPED.DKIM = Backbone.Model.extend({
	        urlRoot : ajaxurl+'?action=wped_verify_dkim',
	         defaults: {
        	    domain: '',
            	email: ''
        		}	
	    });

		WPED.SPF = Backbone.Model.extend({
	        urlRoot : ajaxurl+'?action=wped_verify_spf',
	         defaults: {
        	    domain: '',
            	email: ''
        		}	
	    });

    var x =  wp.Backbone.View.extend({
        tagName: 'div',
        // Get the template from the DOM
        template : wp.template( 'my-awesome-template'),
 		events: {
			'click .media-modal-backdrop, .media-modal-close': 'escapeHandler',
			'keydown': 'keydown'
		},
	constructor: function( options ) {
		if ( options && options.controller ) {
			this.controller = options.controller;
		}
		wp.Backbone.View.apply( this, arguments );
	},
        // When a model is saved, return the button to the disabled state
        initialize:function () {
            var _this = this;
            
        }
        ,render: function() {
        	
        this.$el.html(this.template({name: 'world'}));
    	},
    	/**
	 * @returns {wp.media.view.Modal} Returns itself to allow chaining
	 */
	open: function() {
		var $el = this.$el,
			options = this.options,
			mceEditor;

		if ( $el.is(':visible') ) {
			return this;
		}

		if ( ! this.views.attached ) {
			this.attach();
		}

		// If the `freeze` option is set, record the window's scroll position.
		if ( options.freeze ) {
			this._freeze = {
				scrollTop: $( window ).scrollTop()
			};
		}

		// Disable page scrolling.
		$( 'body' ).addClass( 'modal-open' );

		$el.show();

		// Try to close the onscreen keyboard
		if ( 'ontouchend' in document ) {
			if ( ( mceEditor = window.tinymce && window.tinymce.activeEditor )  && ! mceEditor.isHidden() && mceEditor.iframeElement ) {
				mceEditor.iframeElement.focus();
				mceEditor.iframeElement.blur();

				setTimeout( function() {
					mceEditor.iframeElement.blur();
				}, 100 );
			}
		}

		this.$el.focus();

		return this.propagate('open');
	},

    	/**
	 * @param {Object} options
	 * @returns {wp.media.view.Modal} Returns itself to allow chaining
	 */
	close: function( options ) {
		var freeze = this._freeze;

		if ( ! this.views.attached || ! this.$el.is(':visible') ) {
			return this;
		}

		// Enable page scrolling.
		$( 'body' ).removeClass( 'modal-open' );

		// Hide modal and remove restricted media modal tab focus once it's closed
		this.$el.hide().undelegate( 'keydown' );

		// Put focus back in useful location once modal is closed
		$('#wpbody-content').focus();

		this.propagate('close');

		// If the `freeze` option is set, restore the container's scroll position.
		if ( freeze ) {
			$( window ).scrollTop( freeze.scrollTop );
		}

		if ( options && options.escape ) {
			this.propagate('escape');
		}
		app_router.navigate('');
		return this;
	},

	/**
	 * @returns {wp.media.view.Modal} Returns itself to allow chaining
	 */
	attach: function() {
		if ( this.views.attached ) {
			return this;
		}

		if ( ! this.views.rendered ) {
			this.render();
		}

		this.$el.appendTo( this.options.container );

		// Manually mark the view as attached and trigger ready.
		this.views.attached = true;
		this.views.ready();

		return this.propagate('attach');
	},

	/**
	 * @returns {wp.media.view.Modal} Returns itself to allow chaining
	 */
	detach: function() {
		if ( this.$el.is(':visible') ) {
			this.close();
		}

		this.$el.detach();
		this.views.attached = false;
		return this.propagate('detach');
	},
	/**
	 * @returns {wp.media.view.Modal} Returns itself to allow chaining
	 */
	escape: function() {
		return this.close({ escape: true });
	},
	/**
	 * @param {Object} event
	 */
	escapeHandler: function( event ) {
		event.preventDefault();
		this.escape();
	},
	/**
	 * Triggers a modal event and if the `propagate` option is set,
	 * forwards events to the modal's controller.
	 *
	 * @param {string} id
	 * @returns {wp.media.view.Modal} Returns itself to allow chaining
	 */
	propagate: function( id ) {
		this.trigger( id );

		if ( this.options.propagate ) {
			this.controller.trigger( id );
		}

		return this;
	},
	/**
	 * @param {Object} event
	 */
	keydown: function( event ) {
		// Close the modal when escape is pressed.
		if ( 27 === event.which && this.$el.is(':visible') ) {
			this.escape();
			event.stopImmediatePropagation();
		}
	}
 	});


    // Initiate the router
    var app_router = new AppRouter;

    app_router.on('route:defaultRoute', function(actions,id) {
        //alert(actions + ' ' + id);
        $(document).ready(function(){
        	var t = new x({});
        	t.open();
        	//$('body').append(t.$el);
        	//console.log(t.$el);
    	});
	});

	 app_router.on('route:wped_verify', function(actions,id) {
        $(document).ready(function(){
        	var t = new x({});
        	t.open();

        	var data = new WPED.Model();
        	data.fetch();

        	$('body').append(t.$el);
        	//console.log(t.$el);
    	});
	});


	 app_router.on('route:wped_info', function(domain) {
	 	$(document).ready(function(){
        	
        	//$('body').append(t.$el);
        	//console.log(t.$el);
    	});
	});

    // Start Backbone history a necessary step for bookmarkable URL's
    Backbone.history.start();





    /** Our code here **/

});
 
}(jQuery));