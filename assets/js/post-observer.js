jQuery( document ).ready( function ( e ) {
    let slugChange = document.getElementById( 'edit-slug-box' ),
    options = {
        childList: true
    },
    observer = new MutationObserver( editSlugCallback );

    function editSlugCallback( mutations ) {
        for ( let mutation of mutations ) {
            if ( mutation.type === 'childList' ) {
                jQuery("#linkt-js-link-update").val( jQuery( 'span#sample-permalink a' ).text() );
                jQuery("#linkt-metabox-show").fadeIn( 'fast' );
                observer.disconnect();
            }
        }
    }

    observer.observe( slugChange, options );
});