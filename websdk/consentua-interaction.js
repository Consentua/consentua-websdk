/*
 This is the library for consentua *interactions* to use. It provides a
 framework and library to communicate with the rest of the SDK
*/
var ConsentuaController = function () {
    if (!window.parent) {
        alert("Parent window is not available; you probably need to use the test wrapper when developing with the Consentua SDK.");
        return;
    }

    var self = this;
    var comms = new WindowComms(window.parent);

    // Tell the parent that we're listening for bootstrap information (like the
    // template we should attach to)
    console.log("Waiting for consentua template...");
    comms.send('consentua-waiting', false, init);

    var consents = {};

    // The parent window sends back a message that contains information about the
    // consent template to be used; this handles it
    function init(msg) {
        console.log("Received consentua bootstrap info", msg);

        self.setTemplate(msg.message.template);

        // Set up list of what has already been consented to
        var consentMsg = msg.message.consents;

        var consents = [];
        for (var pgid in self.template.getPurposeGroups()) {
            consents[consentMsg[pgid].PurposeId] = typeof consentMsg[pgid] == "undefined" ? null : consentMsg[pgid].Consent;
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

        self.readyTimeoutWarn = window.setTimeout(function(){ alert("Timeout: The consent UI must call window.consentua.ready() when it's ready!");}, 5000);

        // Tell the current document that the consentua environment is ready
        // This should trigger interaction setup; i.e. the interaction should be
        // listening for it!

        // jQuery
        if (typeof $ != 'undefined')
            $(document).trigger('consentua-ready');

        // and NATIVE
        var event = new Event('consentua-ready');
        document.body.dispatchEvent(event);



    }

    /**
     * Set the consent template (and insert helper methods)
     */
     self.setTemplate = function(t)
     {
         // TODO: Check that the provided template is valid
         console.log('Set Template', t);
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
            console.log(pg);
            for(var pid in pg.Purposes)
            {
                var p = pg.Purposes[pid];
                var b = {};
                consents[p.Id] = bconsented;
            }
        }

        comms.send('consentua-set', {
            consents: consents,
            complete: self.isConsentComplete()
        });
    }

};

document.addEventListener("DOMContentLoaded", function () {
    window.consentua = new ConsentuaController();
});
