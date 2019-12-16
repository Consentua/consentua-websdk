<?php

	header("Content-type: text/javascript");

	// Load environment configuration
	require 'config.php';

?>

/**
 * This script is run by the service at websdk.consentua.com
 */

$().ready(function(){

console.log("\n%c\
      _____ ____  _   _  _____ ______ _   _ _______ _    _             \n\
     / ____/ __ \\| \\ | |/ ____|  ____| \\ | |__   __| |  | |  /\\  TM    \n\
    | |   | |  | |  \\| | (___ | |__  |  \\| |  | |  | |  | | /  \\       \n\
    | |   | |  | | . ` |\\___ \\|  __| | . ` |  | |  | |  | |/ /\\ \\      \n\
    | |___| |__| | |\\  |____) | |____| |\\  |  | |  | |__| / ____ \\     \n\
     \\_____\\____/|_| \\_|_____/|______|_| \\_|  |_|   \\____/_/    \\_\\    \n\
                                                                       \n\
%c\n  Powered by Consentua\n\n\
  %cwww.consentua.com\n\
  \n\
  To aid debugging, console messages have a component indicator:\n\
  %c[C] %c: Consentua Service\n\
  %c[I] %c: Consent Interaction\n\n\
",
"background: #9a1144; color: #fff; font-weight: bold;", // Logotype
"background: #fff; color: #666;", "font-weight: bold", // Text
"color: #9a1144; font-weight: bold;", "", // [C]
"color: #4286f4; font-weight: bold;", "", // [I]

);

console.oldlog = console.log;
console.log = function(){
    var args = Array.prototype.slice.call(arguments);
    console.oldlog.apply(this, ["%c[C]%c", "color: #9a1144; font-weight: bold;", ""].concat(args));
}

/* A lump of JSON is passed as the hash to configure the service; it should have
 * the format:
 *  {
        services: [
            {
                sid: 'x', // Service ID
                cid: 'y', // Client ID
                tid: 'z', // Template ID
                uid: 'p', // User ID
                skey: 'k' // (optional, most ops don't need a service key)
            }
        ],
        lang: 'en', (optional, defaults to en)
        ix: 'https://interaction/url/' (optional, otherwise taken from first template)
 *  }
 *
 */
var hash = decodeURI(window.location.hash.replace(/^#/, ''));
console.log("Received JSON args", hash);
var args = JSON.parse(hash);

$('#consentua-interaction').hide(); // Hide the iframe

console.log("Initialise Consentua Web SDK Service", args);

if(typeof args['services'] == 'undefined'){
  console.error("Required parameter(s) missing - Check and update your consentua embed code").show();
  return;
}

if(typeof args['lang'] == 'undefined')
	var lang = 'en';
else
	var lang = args['lang'];



/**
 * Set up messaging with the embedding page, and the interaction itself
 */
var wrapcomms = new WindowComms(window.parent);
var intcomms = new WindowComms($('#consentua-interaction').get(0).contentWindow);

var apipath = "<?php echo $_CONF['api-path']; ?>";


// For each service, set up a client
// We'll route updates from the interaction to the correct client when they're
// received
var clients = {};
var cwinit = []; // Will hold ClientWrapper intialisation promises
var masterclient = false;
var clientcount = 0;
for(var i in args.services) {

    var svc = args.services[i];

    if(typeof svc.sid == 'undefined' || typeof svc.cid == 'undefined' || typeof svc.tid == 'undefined') {
        console.error("Missing parameter for service " + i, svc, "(require sid, cid, tid)");
        continue;
    }

    if(typeof svc.skey === 'undefined') {
        svc.skey = false;
    }

    //console.log("Configuring service", svc);

    var c = new ConsentuaClient({
    	serviceID: svc.sid,
    	clientID: svc.cid,
        serviceKey: svc.skey,
    	lang: lang,
    	baseURL: apipath
    });

    var cw = new ClientWrapper(c, svc.tid, svc.uid);

    // Store the client for later; NB it's possible to have multiple clients for the same
    // service, as long as they are bound to different templates.
    if(typeof clients[svc.sid] == 'undefined')
        clients[svc.sid] = {};

    clients[svc.sid][svc.tid] = cw;
    clientcount++;

    if(masterclient === false)
        masterclient = cw;

    cwinit.push(cw.getDeferred()); // Initialise the client, store the promise
}

if(masterclient === false) {
    console.error("NO SERVICES REQUESTED");
}

console.debug("Clients configured", masterclient, clients, cwinit);

$.when(cwinit).done(function()
{
    console.debug("Clients ready, loading interaction");
    masterclient.getTemplate().done(loadInteraction);
} );


/**
 * Convenience class to wrap a client up along with a template ID and user ID
 */
function ClientWrapper(client, tid, uid) {

    var self = this;

    var initdef = $.Deferred();

    /**
     * Load the template, and validate or create a UID
     */
    var init = function() {
        // Either create a new user, of retrieve the one specified for the service
        if(typeof uid == 'undefined' || uid === false)
        {
            var au = client.addUser();
            au.done(function(user){
                console.log("Created Consentua user", user.UserId, " with identifier ", user.Identifier, user);
                uid = user.Identifier; // Store the created identifier
            }).fail(function(){
                console.error("Could not create a new user, are the credentials correct?", client.debugInfo({userID: uid, templateID: tid}));
            });
        }
        else
        {
            var au = client.testIfUserExists(uid);
            au.then(function(exists, data){

                if(!exists)
                {
                    console.error("User " + uid + " was not found, are the credentials correct?", client.debugInfo({userID: uid, templateID: tid}));
                    throw "unknown uid";
                }

                console.log("Consentua UID: ", uid);
            });
        }

        /**
         * In parallel, download the template
         */
         var gt = client.getTemplate(tid);
         gt.done(function(template){
             console.log("Consentua template:", template);
         }).fail(function(){
             console.error("Could not retrieve template, are credentials correct?", client.debugInfo({userID: uid, templateID: tid}));
         });

         /**
          * When the template and the user are both ready, resolve the deferred
          */
         $.when(gt, au).done( function(res){
             initdef.resolve(res);
         } );
    }
    init();

    /**
     * Return a deferred object that resolves once the client is set up and has
     * retrieved all the bits and pieces
     */
    self.getDeferred = function() {
        return initdef;
    }

    /**
     * Get the underlying client
     */
    self.getClient = function() {
        return client;
    }

    /**
     * Get the UID, returns a Deferred
     */
    self.getUID = function() {
        var def = $.Deferred();
        initdef.done(function(){
            def.resolve(uid);
        });
        return def;
    }

    /**
     * Get the template, returns a Deferred
     */
    self.getTemplate = function() {
        var def = $.Deferred();

        initdef.done(function(){
            client.getTemplate(tid).done(def.resolve);
        });
        return def;
    }

    /**
     * Get extant consents, returns a Deferred
     */
    self.getConsents = function() {
        var def = $.Deferred();
        initdef.done(function(){
            client.getConsents(uid).done(def.resolve);
        });
        return def;
    }
}

// Helper function for using $.when with arrays of Deferred objects
var waCount = 0;
function whenAll(defs) {
    var myNum = ++waCount;
    console.debug("Compound Deferred " + myNum + " created with " + defs.length + " deferrals");
    var def = $.Deferred();
    $.when.apply($, defs).done(function(){
        console.debug("Compound Deferred " + myNum + " has completed");
        def.resolve(arguments);
    });
    return def;
}


/**
 * Stage 2: Load the interaction and any existing user consents
 */
function loadInteraction(template)
{
    console.log("Loading interaction for template", template);

    // Interaction can be overridden by an argument from the calling page
    if(typeof args['ix'] !== 'undefined') {
		console.warn("@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@\n\n\
 WARNING: The Interaction URL of a Consentua template has been overridden\n\
 at runtime.\n\n\
 In many cases that's OK, but the auditability of your consent records \n\
 might be reduced. For greater auditability, please contact Consentua \n\
 support to have your custom interaction delivered through our delivery \n\
 service.\n\n\
@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@\n\
", args['ix']);
        template.ixUrl = args['ix'];
    }
    else
	{
		template.ixUrl = template.InteractionUrl;
    }

    console.log("Template(s) and user account(s) are ready; loading interaction", template.ixUrl);

    // Load the interaction into the iframe
    $('#consentua-interaction').attr('src', template.ixUrl);

    /**
     * Wait for the interaction to load, then send it the template information
     */
    intcomms.addHandler('consentua-waiting', function(msg)
    {
       console.log("Interaction indicated that it is waiting for template");

       var user = {id: args.uid};

       // Retrieve all templates, and all consents, and send them to the waiting
       // interaction
       var dTemplates = [];
       var dConsents = [];
       var dUIDs = [];

       for(var sid in clients) {
           for(var tid in clients[sid]) {
               var cwrap = clients[sid][tid];
               dTemplates.push(cwrap.getTemplate());
               dConsents.push(cwrap.getConsents());
               dUIDs.push(cwrap.getUID());
           }
       }

       /**
        * When the existing consents and the template are ready, send them to the interaction
        */
       $.when(whenAll(dTemplates), whenAll(dConsents), whenAll(dUIDs)).done(function(templates, consents, uids){

           console.log("Templates, Consents and UIDs have been retrieved, sending to interaction", templates, consents, uids);

           var info = [];
           for(var i in templates) {
               info.push({serviceid: templates[i].ServiceID, template: templates[i], consents: consents[i], uid: uids[i]});
           }

            msg.reply({services: info});

            // Show the iframe and hide the loading indicator
            $('#loading').hide();
            $('#consentua-interaction').show();
       });
     });

    /**
     * Wait for interaction to be ready
     */
    intcomms.addHandler('consentua-ready', function(msg)
    {
        console.log("Interaction indicated to service that it is ready", msg);

        // Fit frame to interaction height
        var iframe = $('#consentua-interaction').get(0);
        var height = msg.message.height + 20;
        iframe.style.height = height + 'px';

        // Tell the embedding page, too
        var uids = {};
        var waits = [];
        console.log("Assemble UID list", clients);
        for(var sid in clients) {
            uids[sid] = {};
            for(var tid in clients[sid]) {
                var c = clients[sid][tid];
                (function(sid, tid){
                    var d;
                    waits.push(d = c.getUID());
                    d.done(function(uid){
                        uids[sid][tid] = uid;
                    });
                })(sid, tid);
            }
        }

        whenAll(waits).done(function(){
            wrapcomms.send('consentua-ready', {height: height, uids: uids});
        });
    });

    /**
     * Handle resizes
     */
    intcomms.addHandler('consentua-resize', function(msg)
    {
        // Fit frame to interaction height
        var iframe = $('#consentua-interaction').get(0);
        var height = msg.message.height;
        iframe.style.height = height + 'px';
        wrapcomms.send('consentua-resize', {height: height});
    });

    // The wrapper can request an explicit measurement
    wrapcomms.addHandler('consentua-measure', function(msg)
    {
        intcomms.send('consentua-measure', msg.message);
    });

    /**
     * Wait for consent to be set
     */
    intcomms.addHandler('consentua-set-bulk', function(msg)
    {
        // TODO: Handle multiple
        // Route based on serviceid / templateid
        var queue = msg.message;
        console.log("Set bulk consent", queue);

        for(var i in queue)
        {
            var atom = queue[i];

            // Route to the correct wrapper
            var wrapper = clients[atom.serviceid][atom.templateid];

            if(!wrapper) {
                console.error("Wrapper not found for service", atom.serviceid, "template", atom.templateid, clients);
                continue;
            }

            console.log("Interaction sent updated consent", atom, "routing to wrapper", wrapper);

			// Look for additional information from the interaction to store with the consent record
			if(typeof atom.extra !== "object") {
				var extra = {};
			} else {
				var extra = atom.extra;
			}

			// Store our own metadata in there, too
			extra._ix = template.ixUrl; // This is the template actually being shown - set above
			extra._ua = window.navigator.userAgent;
			extra._lang = lang;
			extra._url = window.location.href;

            // Save the consent settings
            $.when(wrapper.getUID(), wrapper.getTemplate()).done(function(uid, template)
            {
                wrapper.getClient().setConsents(uid, template.templateid, msg.message.consents, extra).then(function(res){
        			// Once consent has been set, give the receipt URL to the embedding page
        			wrapcomms.send('consentua-receipt', {
        				serviceid: msg.message.serviceid, receiptURL: apipath + "/ConsentReceipt/GetConsentReceipt?version=KI-CR-v1.1.0&consentReceiptId=" + res.ConsentReceiptId
        			});
        		});

                // Tell the customer site that the consent interaction is complete
                wrapcomms.send('consentua-set', {
                    uid: uid,
                    serviceid: atom.serviceid,
                    templateid: atom.templateid,
                    consents: atom.consents,
                    complete: atom.complete
                });
            });

        }
    });
}

}); // End ready handler
