/**
 * Widget save AJAX fix.
 *
 * @see https://wordpress.stackexchange.com/a/37707
 */
jQuery(document).ajaxComplete(function(event, XMLHttpRequest, ajaxOptions) {

    // Determine which ajax request is this (we're after "save-widget").
    var request = {},
        pairs = ajaxOptions.data.split('&'),
        i, split, widget;
    for (i in pairs) {
        split = pairs[i].split('=');
        request[decodeURIComponent(split[0])] = decodeURIComponent(split[1]);
    }

    // Only proceed if this was a widget-save request.
    if (request.action && (request.action === 'save-widget')) {

        // Locate the widget block.
        widget = jQuery('input.widget-id[value="' + request['widget-id'] + '"]').parents('.widget');

        // Trigger manual save, if this was the save request and if we didn't get the form html response (the wp bug).
        if (!XMLHttpRequest.responseText) {
            wpWidgets.save(widget, 0, 1, 0);
        }

        // We got an response, this could be either our request above, or a correct widget-save call, so fire an event on which we can hook our js.
        else {
            jQuery('DO_OUR_STUFF');
        }
    }
});
