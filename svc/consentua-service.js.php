<?php

	header("Content-type: text/javascript");

	// Load environment configuration
	require 'config.php';

?>

/**
 * This script is run by the service at websdk.consentua.com
 */

console.log("Service loaded");
$().ready(function(){

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
var init = [];
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

    init.push(cw.getDeferred()); // Initialise the client, store the promise
}

console.log(clients);

if(masterclient === false) {
    console.error("NO SERVICES REQUESTED");
}

console.log("Clients configured", masterclient, clients);

$.when(init).done(function(){ console.log("Clients ready, loading interaction"); masterclient.getTemplate().done(loadInteraction); } );


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
        if(typeof uid == 'undefined')
        {
            var au = c.addUser();
            au.then(function(user){
                console.log("Created Consentua user", user.UserId, " with identifier ", user.Identifier, user);
                uid = user.Identifier; // Store the created identifier
            });
        }
        else
        {
            var au = c.testIfUserExists(uid);
            au.then(function(exists, data){

                if(!exists)
                {
                    console.error("User " + uid + " was not found");
                    throw "unknown uid";
                }

                console.log("Consentua UID: ", uid);
            });
        }

        /**
         * In parallel, download the template
         */
         var gt = c.getTemplate(args.t);
         gt.done(function(template){
             console.log("Consentua template:", template);
         });

         /**
          * When the template and the user are both ready, resolve the deferred
          */
         $.when(gt, au).done( function(res){ initdef.resolve(res); } );
    }

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


/**
 * Stage 2: Load the interaction and any existing user consents
 */
function loadInteraction(template)
{

    // Interaction can be overridden by an argument from the calling page
    if(typeof args['ix'] !== 'undefined') {
		console.log("Interaction URL has been overridden by calling page", args['ix']);
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

       for(var sid in clients) {
           for(var tid in clients[sid]) {
               var cwrap = clients[sid][tid];
               dTemplates.push(cwrap.getTemplate());
               dConsents.push(cwrap.getConsents());
               dUIDs.push(cwrap.getUID());
           }
       }



       // When the existing consents and the template are ready, give them to the interaction
       $.when(dTemplates, dConsents, dUIDs).then(function(templates, consents, uids){

           console.log("Templates, Consents and UIDs have been retrieved, sending to interaction", templates, consents, uids);

           for(var i in templates) {
               info.push({template: templates[i], consents: consents[i], uid: uids[i]});
           }

            msg.reply(info);

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
        wrapcomms.send('consentua-ready', {height: height, uid: args['uid']});
    });

    /**
     * Wait for consent to be set
     */
    intcomms.addHandler('consentua-set-bulk', function(msg)
    {
        // TODO: Handle multiple
        // Route based on serviceid / templateid
        var queue = msg.message;

        for(var i in queue)
        {
            var atom = queue[i];

            // Route to the correct wrapper
            var wrapper = clients[atom.serviceid][atom.templateid];

            if(!wrapper) {
                console.error("Wrapper not found for service", serviceid, "template", templateid, clients);
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
            });

            // Tell the customer site that the consent interaction is complete
            wrapcomms.send('consentua-set', {
                uid: args['uid'],
                serviceid: atom.serviceid,
                templateid: atom.templateid,
                consents: atom.consents,
                complete: atom.complete
            });
        }
    });
}

}); // End ready handler
