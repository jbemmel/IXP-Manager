/**
 * This file is a combination of other JavaScript files from the OSS-Framework.
 *
 * See: https://github.com/opensolutions/OSS-Framework/tree/master/data/js
 *
 * NOTICE: Do not edit this file as it will be overwritten the next time you run
 *         the by update-oss-js.sh script referenced above.
 *
 * Copyright (c) 2013 Open Source Solutions Limited, Dublin, Ireland
 * All rights Reserved.
 *
 * http://www.opensolutions.ie/
 *
 * Author: Open Source Solutions Limited <info _at_ opensolutions.ie>
 *
 */


//****************************************************************************
// Alert message functions
//****************************************************************************

$( 'document' ).ready( function(){
    // A good percentage of pages will have message boxes - this activates them all
    $(".alert-message").alert();
    $( ".alert" ).alert();
});

/**
 * This function adding oss messages.
 *
 * Function defines message box. And when check where the message should be shown.
 * First it is looking for modal dialog to display oss message in it.
 * If modal dialog was not found it looks for class breadcrumb, witch is page header,
 * and insert oss message after it. And finaly if no modal dialog or breadcrumb was found
 * it insert oss message at the top of main div.
 *
 * @param msg  This is main text of oss message.
 * @param type This is type of oss message(success, error, info, etc.).
 * @param handled This is means that it came from ossAjaxErrorHandler and message can be dispalyed on modal dialog
 */
function ossAddMessage( msg, type, handled )
{
    rand = Math.floor( Math.random() * 1000000 );

    msgbox = '<div id="oss-message-' + rand + '" class="alert alert-' + type + ' fade in">\
                                <a class="close" href="#" data-dismiss="alert">×</a>\
                                    '+ msg + '</div>';

    if( $('.modal-body:visible').length && handled )
    {
        $('.modal-body').prepend( msgbox );
    }
    else if( $('.page-header').length )
    {
        $('.page-header').after( msgbox );

    }
    else if( $('.page-content').length )
    {
        $('.page-header').after( msgbox );

    }
    else if( $( ".breadcrumb" ).length )
    {
        $('.breadcrumb').after( msgbox );
    }
    else if( $( ".container" ).length )
    {
        $('.container').before( msgbox );
    }
    else if( $('#main').length )
    {
        $('#main').prepend( msgbox );
    }

    $( "#oss-message-" + rand ).alert();
}

/**
 * This function hides displayed oss messages.
 *
 * Useful then you want to close all previous oss messages.
 */
function ossCloseOssMessages()
{
    $( "div[id|='oss-message']" ).hide();
}

//****************************************************************************
// Error Functions
//****************************************************************************

/**
 * This function is handling ajax errors.
 *
 * First function is checking if ajax was called on modal window, if so when
 * it checks if buttons are shown that mean that ajax crashed then modal dialog was
 * submitting and enabling modal dialog buttons. If buttons not visible that means
 * that ajax crashed then the content was loading so it close modal dialog.
 * After that it cheks if throbber (canvas) is showing and if so it closes that too.
 * And after that it calls ossAddMessage.
 *
 */
function ossAjaxErrorHandler( XMLHttpRequest, textStatus, errorThrown )
{
    if( $('#modal_dialog:visible').length )
    {
        if( $('#modal_dialog_save').length ){
            $('#modal_dialog_save').removeAttr( 'disabled' ).removeClass( 'disabled' );
            $('#modal_dialog_cancel').removeAttr( 'disabled' ).removeClass( 'disabled' );
        }
        else
        {
            if( dialog )
            {
                dialog.modal('hide');
            }
        }
    }

    $("#overlay").fadeOut( "slow", function(){ ;
        $("#overlay").remove();
    } );

    if( $('canvas').length ){
        $('canvas').remove();
    }
    ossAddMessage( 'An unexpected error occured.', 'error', true );
}

//****************************************************************************
// Modal dialog functions Functions
//****************************************************************************


$( 'document' ).ready( function(){
    // Activate the modal dialog pop up
    $( "a[id|='modal-dialog']" ).bind( 'click', ossOpenModalDialog );
});

/**
 * This function is opening modal dialog with contact us form.
 *
 * First it creats the throbber witch is shown while form is loading by ajax.
 * When fuction creats and opens modal dialog witch is showing throbber.
 * When form is load the throbber is replaced by it. If ajax gets en error the
 * ossAjaxErrorHandler is called.
 *
 * @param event event Its jQuery event, needed to prevent element from default actions.
 */
function ossOpenModalDialog(event) {

    event.preventDefault();

    ossCloseOssMessages();
    if( $( event.target ).is( "i" ) )
        element = $( event.target ).parent();
    else
        element = $( event.target );


    id = element.attr( 'id' ).substr( element.attr( 'id' ).lastIndexOf( '-' ) + 1 );

    if( id.substring( 0, 4 ) == "wide" )
        $( '#modal_dialog' ).addClass( 'modal-wide' );
    else
        $( '#modal_dialog' ).removeClass( 'modal-wide' );

    _createDialog( element.attr( 'href' ) );
};



/**
 * This function creates dialog from given link
 *
 * @param strin url Link to load dialog.
 */
function _createDialog( url )
{
    $('#modal_dialog').html( '<div id="throb" style="padding-left:230px; padding-top:175px; height:275px;"></div>' );

    var Throb = ossThrobber( 100, 20, 1.8 ).appendTo( $( '#throb' ).get(0) ).start();

    dialog = $( '#modal_dialog' ).modal( {
                backdrop: true,
                keyboard: true,
                show: true
    });

    dialog.off( 'hidden' );

    $.ajax({
        url: url,
        async: true,
        cache: false,
        type: 'POST',
        timeout: 10000,
        success:    function(data) {
                        $('#modal_dialog').html( data );
                        $( '#modal_dialog_cancel' ).bind( 'click', function(){
                            dialog.modal('hide');
                            dialog.on( 'hidden', function(){
                                $( '.modal-backdrop' ).remove();
                            });
                        });
                     },

        error:     ossAjaxErrorHandler,
        complete: function(){
            dialog.on( "shown", function(){
                $( '.modal-body' ).scrollTop( 0 );
            });
        }
    });
}

//****************************************************************************
// Toggle functions
//****************************************************************************

/**
 * This function is handling toggle elements.
 *
 * First function unbinds toggle element, removes label type and pointer.
 * Then creates throbber and add it to div trobber with id throb-{toggle element id}.
 * div for throbber should be created manualy. Function only assings throbber to it. After
 * that it calls AJAX for passed URL and data. If responce ok flag ok is set to true otherwise
 * error message is show. If we have AJAX error ten ossAjaxErrorHandler calls. After AJAX error
 * or success handlers function sets back label type and pointer by flags On and Ok , kills throbber
 * end bind same function again for toggle element.
 *
 * @param e Element witch will be edited
 * @param Url This is URL for AJAX.
 * @param data Data for AJAX to post.
 * @param delElement Element witch will be removed
 */
function ossToggle( e, Url, data, delElement )
{
    e.unbind();
    if( e.hasClass( 'disabled' ) )
        return;

    var on = true;
    if( e.hasClass( 'btn-danger' ) ) {
        e.removeClass( "btn-danger" ).attr( 'disabled', 'disabled' );
    } else {
        on = false;
        e.removeClass( "btn-success" ).attr( 'disabled', 'disabled' );
    }

    var Throb = ossThrobber( 18, 10, 1, 'images/throbber_16px.gif' ).appendTo( $( '#throb-' + e.attr( 'id' ) ).get(0) ).start();

    var ok = false;

    $.ajax({
        url: Url,
        data: data,
        async: true,
        cache: false,
        type: 'POST',
        timeout: 10000,
        success: function( data ){
            if( data == "ok" ) {
                ok = true;
            } else {
                ossAddMessage( data, 'error' );
            }
        },
        error: ossAjaxErrorHandler,
        complete: function(){

            if( !ok ) on = !on;

            if( on ) {
                e.html( "Yes" ).addClass( "btn-success" ).removeAttr( 'disabled' );
            } else {
                e.html( "No" ).addClass( "btn-danger" ).removeAttr( 'disabled' );
            }

            $( '#throb-' + e.attr( 'id' ) ).html( "" );

            e.click( function( event ){
                ossToggle( e, Url, data );
            });

            if( typeof( delElement ) != undefined ) {
                $( delElement ).hide( 'slow', function(){ $( delElement ).remove() } );;
            }

        }
    });

    return on;
}

//****************************************************************************
// Tooltip Functions
//****************************************************************************

$( 'document' ).ready( function(){
    $("[rel=tooltip]").tooltip();
    $( '.have-tooltip' ).tooltip( { html: true, delay: { show: 500, hide: 2 }, trigger: 'hover' } );
    $( '.have-tooltip-below' ).tooltip( { html: true, delay: { show: 500, hide: 2 }, trigger: 'hover', placement: 'bottom' } );
    $( '.have-tooltip-long' ).tooltip( { html: true, trigger: 'hover', placement: 'top' } );
});

//****************************************************************************
// Popover Functions
//****************************************************************************

$( 'document' ).ready( function(){

    // Activate Bootstrap pop ups
    $("[rel=popover]").popover(
        {
            offset: 10,
            html: true,
            trigger: "hover"
        }
    );

    $( "[data-oss-po-content]" ).each( ossPopover );
});


/**
 * This function is used on each method with selector [data-oss-po-content] which means
 * all DOM elements with attribute data-oss-po-content
 *
 * Attributes to configure popover:
 *    data-oss-po-content - mandatory and initial attribute. Sets popover text.
 *    data-oss-po-title - sets title for popover. Default is false.
 *    data-oss-po-placement - sets placement for popover. Default is top. Valid options: [top, bottom, left, right]
 *    data-oss-po-delay - sets delay in ms for popover show and hide. Default is 0.
 *    data-oss-po-trigger - sets trigger hook. Default is click. Valid options: [click, hover, focus, manual]
 *    data-oss-po-animation - turns animation on or off. Default is true. Valid options: [true, false]
 */
function ossPopover()
{
    var id = $( this ).attr( "id" );
    var prefix = "data-oss-po-";

    if( $( this ).attr( 'type' ) == "checkbox" )
    {
        $( this ).closest( 'label' ).append('<span id="' + id + '_pop_info" style="padding: 2px 0px 0px 5px;"><i class="icon-info-sign"></span>');
        $( '#' + id + '_pop_info' ).on( "click", function( event ){
            event.preventDefault();
        });
        $( '#' + id + '_pop_info' ).on( "mousedown", function( event ){
            event.preventDefault();
        });
    }
    else if( $( this ).parent().attr( "class" ).indexOf( "input-append" ) != -1 )
        $( this ).parent().after('<span id="' + id + '_pop_info" style="padding: 2px 0px 0px 5px;"><i class="icon-info-sign"></span>');
    else
        $( this ).closest( '.controls' ).append('<span id="' + id + '_pop_info" style="padding: 2px 0px 0px 5px;"><i class="icon-info-sign"></span>');

    $( "#" + id + "_pop_info" ).popover({
        content:   $( this ).attr( prefix + "content" ),
        html:      true,
        trigger:   $( this ).attr( prefix + "trigger" ) ? $( this ).attr( prefix + "trigger" ) :'click',
        title:     $( this ).attr( prefix + "title" ) ? $( this ).attr( prefix + "title" ) : false,
        delay:     $( this ).attr( prefix + "delay" ) ? parseInt( $( this ).attr( prefix + "delay" ) ) : 0,
        animation: $( this ).attr( prefix + "animation" ) ? $( this ).attr( prefix + "animation" ) : true,
        placement: $( this ).attr( prefix + "placement" ) ? $( this ).attr( prefix + "placement" ) : 'top'

    });
}

//****************************************************************************
// Chosen Functions
//****************************************************************************

$( 'document' ).ready( function(){

    $(".chzn-select-deselect").each( function( index ) {
        $( this ).select2( { allowClear: true } );
    });
});


// clear a select dropdown
function ossChosenClear( id ) {
    $( id ).html( "" ).val( "" );
    $( id ).trigger( "change" );
}

//clear a chosen dropdown with a placeholder
function ossChosenClear( id, ph ) {
    $( id ).html( ph ).val( "" );
    $( id ).trigger( "change" );
}


// set a chosen dropdown
function ossChosenSet( id, options, value ) {
    $( id ).html( options );

    if( value != undefined )
        $( id ).val( value );

    $( id ).trigger( "change" );
}

//****************************************************************************
// Throbber Functions
//****************************************************************************

/**
 * This function creates throbber with some default parameters and return the throbber object.
 *
 * @param size  This is size of throbber in pixels.
 * @param lines This is lines count, defines how many lines per throbber.
 * @param strokewidth This is the widh of line.
 * @param fallback This is path to alternative throbber image if browser not compatible with this one.
 * @return Throbber The throbber object
 */

function ossThrobber( size, lines, strokewidth, fallback )
{
    if( !fallback )
        fallback = 'images/throbber_32px.gif';

    return new Throbber({
        "color": 'black',
        "size": size,
        "fade": 750,
        "fallback": fallback,
        "rotationspeed": 0,
        "lines": lines,
        "strokewidth": strokewidth,
        "alpha": 1
    });
}

/**
 * This function creates throbber with overlay on element found by selector
 *
 * @param size  This is size of throbber in pixels.
 * @param lines This is lines count, defines how many lines per throbber.
 * @param strokewidth This is the widh of line.
 * @param fallback This is path to alternative throbber image if browser not compatible with this one.
 * @return Throbber The throbber object
 */

function ossThrobberWithOverlay( size, lines, strokewidth, selector, fallback )
{
    if( !fallback )
        fallback = '../images/throbber_32px.gif';

    var Throb = new Throbber({
        "color": 'white',
        "size": size,
        "fade": 500,
        "fallback": fallback,
        "rotationspeed": 0,
        "lines": lines,
        "strokewidth": strokewidth,
        "alpha": 1
    });

    $( selector ).prepend( '<div id="overlay" align="center" valign="middle" style="margin: -12px;"  class="oss-overlay hide"></div>' );

    var height = $( selector ).height();
    var padding = ( height - size ) / 2;

    $("#overlay").css( 'padding-top', padding );

    $("#overlay").height( $( selector ).height() + 23 - padding  ).width( $( selector ).width() + 23 );
    $("#overlay").fadeIn( "slow" );

    Throb.appendTo( $( '#overlay' ).get(0) ).start();

    return Throb;
}

//****************************************************************************
// Utility Functions
//****************************************************************************

/**
 * Sort function used in various places
 */
function ossSortByName(a, b)
{
    var aName = a.name.toLowerCase();
    var bName = b.name.toLowerCase();
    return ((aName < bName) ? -1 : ((aName > bName) ? 1 : 0));
}

/**
 * Set proper width and margins for bordered fieldset
 */
function ossFormatFieldset()
{
    $( ".legend-fieldset-bordered" ).css( "width", $( ".legend-fieldset-bordered > label" ).width() + 20 );

    $( ".fieldset-bordered-elements> .control-group > .control-label" ).each(function( index ) {
        $(this).width( "100" );
    });

    $( ".fieldset-bordered-elements > .control-group > .controls" ).each(function( index ) {
        $(this).css( "margin-left", "120px" );
    });
}

/**
 * This is simple ajax function where handling made in code.
 *
 * function calls AJAX for passed URL and data. If responce ok it return true
 * else it returns false.
 *
 * NOTE: asyn set to false
 *
 * @param string Url This is URL for AJAX.
 * @param Array data Data for AJAX to post.
 * @return bool
 */
function ossAjax( Url, data )
{
    var ok = false;

    $.ajax({
        url: Url,
        data: data,
        async: false,
        cache: false,
        type: 'POST',
        timeout: 10000,
        success: function( data ){
            if( data == "ok" ) {
                ok = true;
            } else {
                ossAddMessage( data, 'error' );
            }
        },
        error: ossAjaxErrorHandler
    });
    return ok;
}

/**
 * Formating date by specific format
 *
 * @param Date date The date to be formated
 * @param int format Wanted format of the date
 * @return string
 */
function ossFormatDateAsString( date, format )
{
    if( format == undefined )
        format = '1';

    var day   = ( date.getDate() < 10 ? '0' : '' ) + date.getDate();
    var month = ( date.getMonth() + 1 < 10 ? '0' : '' ) + ( date.getMonth() + 1 );

    // case values correspond to OSS_Date DF_* constants
    switch( format )
    {
        case '2': // mm/dd/yyyy
            return month + "/" + day + "/" + date.getFullYear();
            break;

        case '3': // yyyy-mm-dd
            return date.getFullYear() + "-" + month + "-" + day;
            break;

        case '4': // yyyy/mm/dd
            return date.getFullYear() + "/" + month + "/" + day;
            break;

        case '5': // yyyymmdd
            return date.getFullYear() + month + day;
            break;

        case '1': // dd/mm/yyyy
        default:
            return day + "/" + month + "/" + date.getFullYear();
            break;
    }
}

/**
 * Turn a string into a JS Date() object
 * @param str The date as dd/mm/YY
 * @return Date The date object
 */
function ossGetDate( id )
{
    dparts = ossDateSplit( id );
    return new Date( dparts[2], dparts[1] - 1, dparts[0], 0, 0, 0, 0 );
}

/**
 * Split a date into an array of day / month / year for the appropriate date format
 * @param id The date field id
 * @return array
 */
function ossDateSplit( id )
{
    var dparts = [];

    // case values correspond to OSS_Date DF_* constants
    switch( $( "#" + id ).attr( 'data-dateformat' ) )
    {
        case '2': // mm/dd/yyyy
            var t = $( "#" + id ).val().split( '/' );
            dparts[0] = t[1];
            dparts[1] = t[0];
            dparts[2] = t[2];
            break;

        case '3': // yyyy-mm-dd
            var t = $( "#" + id ).val().split( '-' );
            dparts[0] = t[2];
            dparts[1] = t[1];
            dparts[2] = t[0];
            break;

        case '4': // yyyy/mm/dd
            var t = $( "#" + id ).val().split( '/' );
            dparts[0] = t[2];
            dparts[1] = t[1];
            dparts[2] = t[0];
            break;

        case '5': // yyyymmdd
            dparts[0] = $( "#" + id ).val().substr( 6, 2 );
            dparts[1] = $( "#" + id ).val().substr( 4, 2 );
            dparts[2] = $( "#" + id ).val().substr( 0, 4 );
            break;

        case '1': // dd/mm/yyyy
        default:
            var t = $( "#" + id ).val().split( '/' );
            dparts[0] = t[0];
            dparts[1] = t[1];
            dparts[2] = t[2];
            break;
    }

    return dparts;
}

//****************************************************************************
// DataTables http://datatables.net/blog/Twitter_Bootstrap_2
//****************************************************************************


/* Default class modification */
$.extend( $.fn.dataTableExt.oStdClasses, {
        "sWrapper": "dataTables_wrapper form-inline"
} );

/* API method to get paging information */
$.fn.dataTableExt.oApi.fnPagingInfo = function ( oSettings )
{
        return {
                "iStart":         oSettings._iDisplayStart,
                "iEnd":           oSettings.fnDisplayEnd(),
                "iLength":        oSettings._iDisplayLength,
                "iTotal":         oSettings.fnRecordsTotal(),
                "iFilteredTotal": oSettings.fnRecordsDisplay(),
                "iPage":          Math.ceil( oSettings._iDisplayStart / oSettings._iDisplayLength ),
                "iTotalPages":    Math.ceil( oSettings.fnRecordsDisplay() / oSettings._iDisplayLength )
        };
}

/* Bootstrap style pagination control */
$.extend( $.fn.dataTableExt.oPagination, {
        "bootstrap": {
                "fnInit": function( oSettings, nPaging, fnDraw ) {
                        var oLang = oSettings.oLanguage.oPaginate;
                        var fnClickHandler = function ( e ) {
                                e.preventDefault();
                                if ( oSettings.oApi._fnPageChange(oSettings, e.data.action) ) {
                                        fnDraw( oSettings );
                                }
                        };

                        $(nPaging).addClass('pagination').append(
                                '<ul>'+
                                        '<li class="prev disabled"><a href="#">&larr; '+oLang.sPrevious+'</a></li>'+
                                        '<li class="next disabled"><a href="#">'+oLang.sNext+' &rarr; </a></li>'+
                                '</ul>'
                        );
                        var els = $('a', nPaging);
                        $(els[0]).bind( 'click.DT', { action: "previous" }, fnClickHandler );
                        $(els[1]).bind( 'click.DT', { action: "next" }, fnClickHandler );
                },

                "fnUpdate": function ( oSettings, fnDraw ) {
                        var iListLength = 5;
                        var oPaging = oSettings.oInstance.fnPagingInfo();
                        var an = oSettings.aanFeatures.p;
                        var i, j, sClass, iStart, iEnd, iHalf=Math.floor(iListLength/2);

                        if ( oPaging.iTotalPages < iListLength) {
                                iStart = 1;
                                iEnd = oPaging.iTotalPages;
                        }
                        else if ( oPaging.iPage <= iHalf ) {
                                iStart = 1;
                                iEnd = iListLength;
                        } else if ( oPaging.iPage >= (oPaging.iTotalPages-iHalf) ) {
                                iStart = oPaging.iTotalPages - iListLength + 1;
                                iEnd = oPaging.iTotalPages;
                        } else {
                                iStart = oPaging.iPage - iHalf + 1;
                                iEnd = iStart + iListLength - 1;
                        }

                        for ( i=0, iLen=an.length ; i<iLen ; i++ ) {
                                // Remove the middle elements
                                $('li:gt(0)', an[i]).filter(':not(:last)').remove();

                                // Add the new list items and their event handlers
                                for ( j=iStart ; j<=iEnd ; j++ ) {
                                        sClass = (j==oPaging.iPage+1) ? 'class="active"' : '';
                                        $('<li '+sClass+'><a href="#">'+j+'</a></li>')
                                                .insertBefore( $('li:last', an[i])[0] )
                                                .bind('click', function (e) {
                                                        e.preventDefault();
                                                        oSettings._iDisplayStart = (parseInt($('a', this).text(),10)-1) * oPaging.iLength;
                                                        fnDraw( oSettings );
                                                } );
                                }

                                // Add / remove disabled classes from the static elements
                                if ( oPaging.iPage === 0 ) {
                                        $('li:first', an[i]).addClass('disabled');
                                } else {
                                        $('li:first', an[i]).removeClass('disabled');
                                }

                                if ( oPaging.iPage === oPaging.iTotalPages-1 || oPaging.iTotalPages === 0 ) {
                                        $('li:last', an[i]).addClass('disabled');
                                } else {
                                        $('li:last', an[i]).removeClass('disabled');
                                }
                        }
                }
        }
} );

//Adding more sort filters
jQuery.extend( jQuery.fn.dataTableExt.oSort, {
    "num-html-pre": function ( a ) {
        var x = String(a).replace( /<[\s\S]*?>/g, "" );
        return parseFloat( x );
    },

    "num-html-asc": function ( a, b ) {
        return ((a < b) ? -1 : ((a > b) ? 1 : 0));
    },

    "num-html-desc": function ( a, b ) {
        return ((a < b) ? 1 : ((a > b) ? -1 : 0));
    }
} );
