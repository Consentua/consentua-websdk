/**
 * Consentua Interaction SDK
 * HTML/Javascript/CSS consent interactions for use in web and mobile apps
 *
 * This script implements the web SDK wrapper; it's what loads and executes
 * the configured interaction
 */

/**
 * Instantiate with a reference to iframe that the interaction should be loaded into
 */
function ConsentuaUIWrapper(iframe, template)
{
    var self = this;

    /**
     * Send a custom event of etype to the subDOM
     */
    self.send = function(etype){
        var e = idoc.createEvent('Event');
        e.initEvent(etype, true, true);

        idoc.dispatchEvent(e);
    }

    /**
     * Initialise the UI by loading support libraries etc into the DOM
     */
    var initUI = function(ui_url) {

        // Tell the interaction that the DOM is ready
        self.sendMsg('consentua-ready');
    }
}
