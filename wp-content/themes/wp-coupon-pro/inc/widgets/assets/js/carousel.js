jQuery(document).ready(function() {
    jQuery(".st-carousel").each(  function( i ) {
        //return false ;
        var s = jQuery( this );
        if ( s.hasClass('slider-added' ) ) {
           return false ;
        }

        s.addClass( 'slider-added' );
        var settings = s.attr('data-settings');
        try {
            settings = JSON.parse( settings );
        }catch (e) {

        }

        var args =  {
            dots: true,
            nav : settings.nav, // Show next and prev buttons
            navText : settings.navText,
            pagination : true,
            //paginationNumbers: true,
            smartSpeed : settings.smartSpeed,
            navSpeed : settings.navSpeed,
    
            //singleItem: false,
            autoplay: settings.autoplay,
            autoplayHoverPause: settings.autoplayHoverPause,
            // "singleItem:true" is a shortcut for:
            items : settings.items,
            rtl: settings.rtl,
            
            responsive:{
                0:{
                    items: settings.itemsMobile
                },
                768:{
                    items: settings.itemsDesktopSmall
                },
                1199:{
                    items: settings.items
                }
            },
           
            afterInit: function(){
                setTimeout(  function(){
                    s.closest( '.widget').fadeIn(500);
                }, 100 );
            }
        };

        s.owlCarousel( args );

    } ) ;

});