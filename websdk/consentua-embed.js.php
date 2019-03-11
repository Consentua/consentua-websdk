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
 * This is just a wrapper around the newer ConsentuaEmbed - use that instead!
 *
 * iframe: The iframe element that the interaction should be loaded into
 * clientid: Consentua CLient ID
 * uid: The UID of the user, or false to create a new one
 * templateid: The template to display
 * serviceid: The Consentua service ID
 * [UNUSED]
 * cb_set: A callback for when consent has been set - deprecated
 * lang: The language to use
 * opts: Other options, passed directly as URL parameters to the Consentua Web SDK
 */
function ConsentuaUIWrapper(iframe, clientid, uid, templateid, serviceid, unused, cb_set, lang, opts)
{
    var self = this;

    self.onset = cb_set;
    self.onreceipt = function(){};
    self.onready = function(){};

    var embed = new ConsentuaEmbed(
        {
            iframe: iframe,
            uid: uid,
            clientid: clientid,
            templateid: templateid,
            serviceid: serviceid,
            opts: opts,
            lang: lang,

            onset: function(m){ self.onset(m); },
            onreceipt: function(m){ self.onreceipt(m); },
            onready: function(m){ self.onready(m); }
        }
    );

}

/**
 * This shim migrates from the one-template embed to the multi-template embed
 */
function ConsentuaEmbed(opts)
{
    var self = this;

    // List of required options
    var reqOpts = [
        'clientid',
        'serviceid',
        'templateid',
        'iframe'
    ];

    // Default values for non-required options
    var defOpts = {
        uid: false,
        onset: function(){ },
        onready: function(){ },
        onreceipt: function(){ },
        opts: {}
    };

    fillOpts(opts, reqOpts, defOpts);

    self.onset = opts.onset;
    self.onready = opts.onready;
    self.onreceipt = opts.onreceipt;

    var ce = new ConsentuaMultiEmbed({
        services: [{
            clientid: opts.clientid,
            serviceid: opts.serviceid,
            templateid: opts.templateid,
            uid: opts.uid
        }],
        iframe: opts.iframe,
        onset: function(c){ self.onset(c); },

        // Onready needs to convert from the deep UID structure of the multiEmbed
        // to a single UID
        onready: function(c){
            c.message.uid = c.message.uids[opts.serviceid][opts.templateid];
            self.onready(c);
        },


        onreceipt: function(c){ self.onreceipt(c); },
        opts: opts.opts
    });

    self.embed = ce;
}

/**
 * Embed Consentua
 */
function ConsentuaMultiEmbed(opts)
{
    var self = this;

    // List of required options
    var reqOpts = [
        'services',
        'iframe'
    ];

    // Default values for non-required options
    var defOpts = {
        onset: function(){},
        onready: function(){},
        onreceipt: function(){},
        opts: {}
    };

    var svcReqOpts = [
        'clientid',
        'serviceid',
        'templateid'
    ];

    var svcDefOpts = {
        uid: false
    };

    fillOpts(opts, reqOpts, defOpts);

    for(var i in opts.services) {
        fillOpts(opts.services[i], svcReqOpts, svcDefOpts);
    }

    console.log("Embed consentua", opts);

    self.onset = opts.onset;
    self.onready = opts.onready;
    self.onreceipt = opts.onreceipt;

    // Assemble the JSON we want to pass to the service
    var svcs = [];
    for(var i in opts.services) {
        var s = opts.services[i];
        svcs.push({
            sid: s.serviceid,
            tid: s.templateid,
            cid: s.clientid,
            uid: s.uid
        });
    }

    var svcjson = {
        lang: opts.lang,
        services: svcs
    }

    // Copy additional options
    for(var k in opts.opts) {
        var v = opts.opts[k];
        svcjson[k] = v;
    }

    var sdkbase = "<?php echo ($_SERVER['HTTPS'] ? 'https' : 'http').'://'.$_SERVER['SERVER_NAME'].(($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) ? ':'.$_SERVER['SERVER_PORT'] : ''); ?>/svc/";
    var url = sdkbase + "#" + JSON.stringify(svcjson);

    opts.iframe.setAttribute('src', url);

    var idoc = opts.iframe.contentWindow.document;


    // Set up communication with other windows, and register event handlers
    self.comms = new WindowComms(opts.iframe.contentWindow);
    self.comms.addHandler('consentua-ready', function(msg){
        console.log("Embed is ready", msg);
        opts.iframe.style.height = (msg.message.height + 10) + 'px';
        self.onready(msg);
    });

    self.comms.addHandler('consentua-set', self.onset);

    self.comms.addHandler('consentua-receipt', self.onreceipt);

    self.comms.addHandler('consentua-resize', function(msg){
        opts.iframe.style.height = (msg.message.height + 10) + 'px';
    });


    /**
     * The container iframe auto-sizes, in co-operation with the interaction
     * itself; however, widgets are sometimes rendered invisibly (e.g. within
     * an element that's currently hidden). This observer uses the Intersection
     * Observer API to resize the container widget when it becomes visible.
     */
     let observerOptions = {
       root: null,
       rootMargin: "0px",
       threshold: [0.0, 0.75]
     };

     var updateHeight = function(){
         self.comms.send('consentua-measure', {}); // This sends back a consentua-resize event, handled above
     }

     var visObserver = new IntersectionObserver(updateHeight, observerOptions);
}

/**
 * Helper function; check that opts contains all of the properties listed in
 * reqOpts (an array), and copy any properties from defOpts (an object) that
 * don't exist in opts.
 *
 * This in effect requires the keys listed in reqOpts, and takes default options
 * from defOpts.
 */
function fillOpts(opts, reqOpts, defOpts) {
    reqOpts.map(function(k) {
        if(typeof opts[k] == 'undefined')
            throw "Required option '"+k+"' is not set";
    });

    Object.keys(defOpts).map(function(k) {
        if(typeof opts[k] == 'undefined')
            opts[k] = defOpts[k];
    });
}


/**
 * IE Polyfill for array.keys()
 */
if (![].keys) {
    Array.prototype.keys = function() {
       var k, a = [], nextIndex = 0, ary = this;
       k = ary.length;
       while (k > 0) a[--k] = k;
       a.next = function(){
           return nextIndex < ary.length ?
               {value: nextIndex++, done: false} :
               {done: true};
       };
    return a;
    };
}



/**
 * Inter-document communication, using message passing
 */
<?php
require 'comms.js'; // Include the comms library
?>
