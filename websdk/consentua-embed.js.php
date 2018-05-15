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
 */
function ConsentuaUIWrapper(iframe, clientid, uid, templateid, serviceid, servicekey, cb_set)
{
    var self = this;

    self.onset = function(){};
    self.onready = function(){};

    // Legacy: cb_set used to be passed in, now it's a property
    if(typeof cb_set !== 'undefined')
        self.onset = cb_set;

    var sdkbase = "<?php echo ($_SERVER['HTTPS'] ? 'https' : 'http').'://'.$_SERVER['SERVER_NAME'].(($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) ? ':'.$_SERVER['SERVER_PORT'] : ''); ?>/svc/";

    var url = sdkbase + "#s=" + serviceid + "&k=" + servicekey + "&c=" + clientid + "&t=" + templateid;

    if(uid !== false){ // uid is optional, it can be set false to auto-generate in the service
        url += "&uid=" + uid;
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

        if (!event.origin.match(/^https?:\/\/((.+\.consentua\.com)|(localhost(:[0-9]+)?)|(127\.0\.0\.1(:[0-9]+)?))/)) // Allow 127.0.0.1 for development
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
