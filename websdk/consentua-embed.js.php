/**
 * Consentua Interaction SDK
 * HTML/Javascript/CSS consent interactions for use in web and mobile apps
 *
 * This script implements the web SDK wrapper; it's what loads and executes
 * the configured interaction via the Web SDK Service
 */

<?php

	header('Content-Type: text/javascript');

?>

/**
 * Instantiate with a reference to iframe that the interaction should be loaded into
 *
 * iframe: The iframe element that the interaction should be loaded into
 * clientid: Consentua CLient ID
 * uid: The UID of the user, or false to create a new one
 * templateid: The template to display
 * serviceid: The Consentua service ID
 * servicekey: The Consentua service key - Will be deprecated
 * cb_set: A callback for when consent has been set - deprecated
 * lang: The language to use
 * opts: Other options, passed directly as URL parameters to the Consentua Web SDK
 */
function ConsentuaUIWrapper(iframe, clientid, uid, templateid, serviceid, servicekey, cb_set, lang, opts)
{
    var self = this;

    self.onset = function(){};
    self.onready = function(){};

    // Legacy: cb_set used to be passed in, now it's a property
    if(typeof cb_set !== 'undefined')
        self.onset = cb_set;

    // Language
    if(typeof lang == 'undefined')
        lang = 'en';

    // Options
    if(typeof opts == 'undefined')
        opts = {};

    var sdkbase = "<?php echo ($_SERVER['HTTPS'] ? 'https' : 'http').'://'.$_SERVER['SERVER_NAME'].(($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) ? ':'.$_SERVER['SERVER_PORT'] : ''); ?>/svc/";

    var url = sdkbase + "#s=" + serviceid + "&k=" + servicekey + "&c=" + clientid + "&t=" + templateid + "&lang=" + lang;

    if(uid !== false){ // uid is optional, it can be set false to auto-generate in the service
        url += "&uid=" + uid;
    }

    for(var k in opts) {
        url += "&" + encodeURIComponent(k) + "=" + encodeURIComponent(opts[k]);
    }

    iframe.setAttribute('src', url)

    var idoc = iframe.contentWindow.document;

    /**
     * Send a custom event of etype to the subDOM
     */
    self.sendMsg = function(etype){
        var e = idoc.createEvent('Event');
        e.initEvent(etype, true, true);

        idoc.dispatchEvent(e);
    }

    /**
     * Initialise the UI
     */
    var initUI = function(ui_url) {

        // Tell the interaction that the DOM is ready
        self.sendMsg('consentua-ready');
    }

    self.recv = function(event)
    {
        if(event.source != iframe.contentWindow)
        {
            console.debug("Received message didn't come from consentua iframe", event.source, iframe);
            return;
        }

        if (!event.origin.match(/^https?:\/\/((.+\.consentua\.com)|(localhost(:[0-9]+)?)|(127\.0\.0\.(1|2|3)(:[0-9]+)?))/)) // Allow 127.0.0.x for development
        {
            console.error("Message did not come from Consentua Web Service", event.origin);
            return;
        }

        var msg = event.data;
        console.debug("Message from service", msg);

        // When the interaction is ready, set the iframe height
        if(msg.type == 'consentua-ready'){
            console.log("Embed is ready", msg);
            iframe.style.height = (msg.message.height + 20) + 'px';
            self.onready(msg);
        }
        // When consent is set, pass it to the callback
        else if (msg.type == 'consentua-set'){
            self.onset(msg);
        }
    };

    window.addEventListener("message", self.recv);
}
