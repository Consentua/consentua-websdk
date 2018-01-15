/**
 * Consentua Interaction SDK
 * HTML/Javascript/CSS consent interactions for use in web and mobile apps
 *
 * This script implements the web SDK wrapper; it's what loads and executes
 * the configured interaction via the Web SDK Service
 */

/**
 * Instantiate with a reference to iframe that the interaction should be loaded into
 */
function ConsentuaUIWrapper(iframe, clientid, templateid, serviceid, servicekey, cb_set)
{
    var self = this;

    //var sdkbase = "https://websdk.consentua.com/";
    var sdkbase = "http://127.0.0.1:8080/svc/";

    iframe.setAttribute('src', sdkbase + "#s=" + serviceid + "&k=" + servicekey + "&c=" + clientid + "&t=" + templateid )

    var idoc = iframe.contentWindow.docuent;

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
            console.log("Received message didn't come from consentua iframe", event.source, iframe);
            return;
        }

        if(!event.origin.match(/^https?:\/\/(websdk.consentua.com\/|127\.0\.0\.1(:[0-9]+)?)/)) // Allow 127.0.0.1 for development
        {
            console.error("Message did not come from Consentua Web Service", event.origin);
            return;
        }

        var msg = event.data;
        console.debug("Message from service", msg);

        // When the interaction is ready, set the iframe height
        if(msg.type == 'consentua-ready'){
            // Fit frame to interaction height
            iframe.style.height = '600px';
        }
        // When consent is set, pass it to the callback
        else if (msg.type == 'consentua-set'){
            cb_set(msg);
        }
    };

    window.addEventListener("message", self.recv);
}
