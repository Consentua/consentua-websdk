/*
 * This is the library for consentua *interactions* to use. It provides a
 * framework and library to communicate with the rest of the SDK
 */
 console.oldlog = console.log;
 console.log = function(){
     var args = Array.prototype.slice.call(arguments);
     console.oldlog.apply(this, ["%c[I]%c", "color: #4286f4; font-weight: bold;", ""].concat(args));
 }

var ConsentuaController = function () {
    if (!window.parent) {
        alert("Parent window is not available; you probably need to use the test wrapper when developing with the Consentua SDK.");
        return;
    }

    var self = this;
    var comms = new WindowComms(window.parent);

    // Tell the parent that we're listening for bootstrap information (like the
    // template we should attach to)
    console.debug("Waiting for consentua templates...");
    comms.send('consentua-waiting', false, init);

    var services = [];

    // The parent window sends back a message that contains information about the
    // consent template to be used; this handles it
    function init(msg) {
        console.debug("Received consentua bootstrap info", msg);

        for(var i in msg.message.services)
        {
            var info = msg.message.services[i];
            var service = new ConsentuaService(info.serviceid, self);
            service.setTemplate(info.template);
            service.setConsent(info.consent);
            services.push(service);
        }

        /**
         * When Consentua only showed one template at a time, it was exposed as
         * window.consentua.template
         *
         * If there's only one template loaded, then expose it via that legacy
         * route
         */
        if(services.length == 1) {
            self.template = services[0].getTemplate();
        }

        /**
         * Get all the loaded service/template objects
         */
        self.getServices = function() {
            return services;
        }

        /**
         * Setup window.consentua.ready()
         * The interaction should call this when it has finished rendering
         */
        self.ready = function()
        {
            window.clearTimeout(self.readyTimeoutWarn);

            var body = document.body;
            var html = document.documentElement;
            var height = Math.max( body.scrollHeight, body.offsetHeight,
                                   html.clientHeight, html.scrollHeight, html.offsetHeight );

            comms.send("consentua-ready", {
                height: height
            }); // Send a message back to the wrapper to confirm that the widget is (notionally) ready
        }

        // Handle resizing
        var lastBodyHeight = 0;
        var sendResize = function(force){

            if(typeof force == 'undefined')
                force = false;

            var body = document.body;
            var html = document.documentElement;
            var height = Math.max( body.scrollHeight, body.offsetHeight,
                                   html.clientHeight, html.scrollHeight, html.offsetHeight );

            console.log("Interaction height", height, "Last was", lastBodyHeight);

            if(force || lastBodyHeight != height)
            {
                lastBodyHeight = height;
                comms.send("consentua-resize", {
                    height: height
                });
            }
        }

        comms.addHandler('consentua-measure', function(msg){
            sendResize(true);
        });

        if(typeof ResizeObserver !== 'undefined') // Use Chrome's ResizeObserver if possible
        {
            var ro = new ResizeObserver(function(els){
                sendResize();
            });

            ro.observe(document.body);
        }
        else // Detect manually
        {
            var lastHeight = 0;
            window.setInterval(function(){
                var body = document.body;
                var html = document.documentElement;
                var height = Math.max( body.scrollHeight, body.offsetHeight,
                                       html.clientHeight, html.scrollHeight, html.offsetHeight );
               if(height != lastHeight) {
                   lastHeight = height;
                   sendResize();
               }
            }, 500);
        }

        self.readyTimeoutWarn = window.setTimeout(function(){ alert("Timeout: The consent UI must call window.consentua.ready() when it's ready!");}, 5000);

        // Tell the current document that the consentua environment is ready
        // This should trigger interaction setup; i.e. the interaction should be
        // listening for it!

        // jQuery
        if (typeof $ != 'undefined')
            $(document).trigger('consentua-ready');

        // and native event
        if(typeof(Event) === 'function') {
            var event = new Event('consentua-ready');
        } else { // Legacy; IE11 etc.
            var event = document.createEvent('Event');
            event.initEvent('consentua-ready', true, true);
        }

        document.body.dispatchEvent(event);
    }



    /**
     * Queue a consent update to be sent to the service; they're batched and sent
     * after 100ms
     */
    var cqueue = [];
    var cpushwaiting = false;
    self.queueConsent = function(message) {
        cqueue.push(message);

        if(cpushwaiting)
            return;

        cpushwaiting = true;
        window.setTimeout(sendConsent, 100);
    }

    var sendConsent = function() {
        cpushwaiting = false;
        comms.send('consentua-set-bulk', cqueue);
        cqueue = [];
    }

};

/**
 * Represents a service, bound to a specific template
 */
function ConsentuaService(serviceid, controller)
{
    var self = this;

    /**
     * It's common for multiple consents to be set together; these methods allow the message back to the service
     * to batch those consents together.
     * These methods push consent state up to the ConsentuaController, which should
     * send it back to the service.
     */
    var pushWaiting = false;
    var pushConsentDelayed = function()
    {
        if(pushWaiting)
            return;

        pushWaiting = true;

        window.setTimeout(pushConsent, 100);
    }

    var pushConsent = function()
    {
        pushWaiting = false;
        var m = {
                consents: consents,
                extra: metadata,
                complete: self.isConsentComplete(),
                serviceid: serviceid,
                templateid: self.template.Id
            };

        controller.queueConsent(m);
    }


    /**
     * Set the consent template (and insert helper methods)
     */
     self.setTemplate = function(t)
     {
         // TODO: Check that the provided template is valid
         self.template = t;

         for (var id in self.template.PurposeGroups)
         {
             self.template.PurposeGroups[id].PurposeGroupID = id; // Explicitly add an ID to each purpose group
         }

         // Add helper methods to the template
         self.template.getPurposeGroups = function ()
         {
             return self.template.PurposeGroups;
         }

         self.template.getPurposeGroupByID = function (id)
         {
             if (typeof self.template.PurposeGroups[id] == "undefined") {
                 return false;
             }

             return self.template.PurposeGroups[id];
         }
     }

     self.getTemplate = function() {
         return self.template;
     }

     var consents = {};
     self.setConsent = function(consentMsg)
     {
         // Set up list of what has already been consented to
         // Only do this for purposes that appear in the template

         // Flatten the initial consent list
         var consentList = {};
         for(var i in consentMsg)
         {
             var c = consentMsg[i];
             consentList[c.PurposeId] = c.Consent;
         }

         consents = {};

         // Iterate each purpose group in the template
         var pgs = self.template.getPurposeGroups();
         for (var pgid in pgs) {

             var pg = pgs[pgid];

             // Then each purpose in the group
             for(var i in pg.Purposes)
             {
                 var pid = pg.Purposes[i].Id;
                 consents[pid] = typeof consentList[pid] == "undefined" ? null : consentList[pid];
             }
         }

         console.debug("Initial consent settings for service ", serviceid, consentList, consents);
     }

    /**
     * Check if all values on the template are set to true or false (ie not null)
     */
    self.isConsentComplete = function ()
    {
        var pgs = self.template.getPurposeGroups();

        for (var pgid in pgs)
        {
            if (pgs[pgid] === null)
                return false;
        }

        return true;
    }

    /**
     * Get current consent settings for all purposes in the template
     * Format is:
     *             [{purposeId: x, consent: true}, ...]
     */
    self.getConsent = function ()
    {
        var cmodel = [];
        for (var k in consents) {
            cmodel.push({
                purposeId: k,
                consent: consents[k]
            });
        }

        return cmodel;
    }

    /**
     * Get consent setting for a single purpose
     */
    self.getPurposeConsent = function(purposeId)
    {
        return typeof consents[purposeId] == 'undefined' ? null : consents[purposeId];
    }

    /**
     * Get all consent settings for a purpose group
        {
            purposeId1: true,
            purposeId2: null,
            purposeId3: false
        }
     */
     self.getPgConsent = function (purposeGroupId)
     {
         var pg = self.template.getPurposeGroupByID(purposeGroupId);

         var out = {};

         for(var i in pg.Purposes)
         {
             var pid = pg.Purposes[i].Id;
             out[pid] = self.getPurposeConsent(pid);
         }

         return out;
     }

     /**
      * Check if we have consent to every purpose in the given purpose group(s)
      * pgid: A purpose group ID, or array of purpose group IDs
      */
     self.isConsentedGroup = function (purposeGroupId)
     {
         var c = self.getPgConsent(purposeGroupId);

         for(var i in c)
         {
             if(c[i] !== true)
                 return false;
         }

         return true;
     }


    /**
     * Set consent for a purpose group
     *
     * Consent should be set per purpose GROUP. It can be true (consented), false (not consented) or null (deliberately undefined).
     * The interaction is considered "complete" from Consentua's point of view once all groups are set to non-null values
     *
     * purposeGroupIds: An array of purposegroup IDs from the template
     * consented: Whether consent is granted, or not, to the specified purpose groups
     *
     * NB: When switching between groups it is important to REMOVE consent from the old group
     *     BEFORE granting consent to the new group. Otherwise purposes that appear in both will
     *     be marked as non-consented!
     */
    self.setPgConsent = function (purposeGroupIds, consented)
    {
        // Make sure consented is a boolean
        var bconsented;
        if (consented === null) {
            bconsented = null;
        }

        if (typeof consented == "boolean") {
            bconsented = consented;
        } else if (consented == 1 || consented == "true") { // Will match 1 or "1"
            bconsented = true;
        } else {
            bconsented = false;
        }

        // Allow a single pgid to be supplied instead of an array
        if (Number.isInteger(purposeGroupIds)) {
            purposeGroupIds = [purposeGroupIds];
        }

        for(var i in purposeGroupIds)
        {
            var pgid = purposeGroupIds[i];
            var pg = self.template.getPurposeGroupByID(pgid);

            for(var pid in pg.Purposes)
            {
                var p = pg.Purposes[pid];
                var b = {};
                consents[p.Id] = bconsented;
            }
        }

        pushConsentDelayed();
    }

    self.setPurposeConsent = function(purposeIds, consented)
    {
        // Make sure consented is a boolean
        var bconsented;
        if (consented === null) {
            bconsented = null;
        }

        if (typeof consented == "boolean") {
            bconsented = consented;
        } else if (consented == 1 || consented == "true") { // Will match 1 or "1"
            bconsented = true;
        } else {
            bconsented = false;
        }

        // Allow a single pgid to be supplied instead of an array
        if (Number.isInteger(purposeIds)) {
            purposeIds = [purposeIds];
        }

        for(var i in purposeIds)
        {
            consents[purposeIds[i]] = bconsented;
        }

        pushConsentDelayed();
    }


    /**
     * Interactions can set metadata that will be stored (via setConsentsEX) along with
     * the consent record. This metadata is sent with every call to setPgConsent /
     * setPurposeConsent
     *
     */
    var metadata = {};
    self.setMetadata = function(name, value) {
        metadata[name] = value;
    }

    /**
     * i18n
     *
     * Interactions often need to use custom strings, but we'd like them to benefit from the language settings
     * that are set in the consent template (and possibly from a library of pre-translated strings, too).
     * These methods allow strings to be registered in different languages; and then retrieved in whatever
     * language the template itself is using.
     */

      /**
       * Get the language for the current template; this is the language that strings will be returned in, if possible
       */
      self.getLanguage = function()
      {
          return self.template.Language;
      }

     /**
      * Add strings to the dictionary; id is a unique identifier for the set of strings. strings is an object
      * in the form
      *
      *       {'en': 'a string in english', 'fr': 'A string in french'}
      *
      * The first registered string is used in the event that no translation is available. Multiple calls to
      * addString for the same ID can be used to add additional translations.
      */
     var i18n = {};

     self.addString = function(id, strings)
     {
         if(typeof i18n[id] == 'undefined')
         {
             i18n[id] = {};
         }

         for(var lang in strings)
         {
             i18n[id][lang] = strings[lang];
         }
     }

     self.getString = function(id)
     {
         var lang = self.getLanguage();

         if(typeof i18n[id] == 'undefined')
         {
            return "[Unknown string '" + id + "']";
         }

         if(typeof i18n[id][lang] == 'undefined')
         {
             return i18n[id][Object.keys(i18n[id])[0]];
         }

         return i18n[id][lang];
     }
}

document.addEventListener("DOMContentLoaded", function () {
    window.consentua = new ConsentuaController();
});
