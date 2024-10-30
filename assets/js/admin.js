jQuery( document ).ready( function ( e ) {

	jQuery( '.linkt-dw-block-name' ).click( function(e) {
        jQuery( this ).parent().next().slideToggle( 'fast' );
        jQuery( this ).parent().toggleClass( 'linkt-open' );
    });
    jQuery( '.linkt-dw-switch' ).click( function(e) {
        jQuery( this ).parent().toggleClass( 'linkt-dw-url' );
        jQuery( this ).parent().toggleClass( 'linkt-dw-to' );
    });

    jQuery( '.linkt-redirect-url' ).click( function(e) {
        jQuery( this ).select();
        document.execCommand( "copy" );

        jQuery( this ).parent().addClass( 'show-tooltip' );
        setTimeout(function () {
            jQuery( '.tooltip' ).removeClass( 'show-tooltip' );
        }, 1000);
    });
    
});