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

        // TODO: Check that the provided template is valid
        self.template = msg.message.template;

        // TODO: Add helper methods to the template
        self.template.getPurposeGroups = function () {
            return self.template.PurposeGroups;
        }

        self.template.getPurposeGroupByID = function (id) {
            let o = id - 1;
            if (typeof self.template.PurposeGroups[o] == "undefined") {
                return false;
            }
            return self.template.PurposeGroups[o];
        }

        // Set up list of what has been consented to
        var consentMsg = msg.message.consents;
        for (var pgid in consentMsg) {
            consents[consentMsg[pgid].PurposeId] = consentMsg[pgid].Consent;
        }
        // consents = msg.message.consents;
        // // check that consents has a field for every purpose
        // var pgs = self.template.getPurposeGroups();
        // for(var pgid in pgs){ // Purposes are grouped into purpose groups
        //     for(var pid in pgs[pgid].Purposes){
        //         purposeId = pgs[pgid].Purposes[pid].Id;
        //         consents[purposeId] = typeof consents[purposeId] == "undefined" ? null : consents[purposeId];
        //     }
        // }

        // Tell the current document that the consentua environment is ready
        // This should trigger interaction setup; i.e. the interaction should be
        // listening for it!

        // jQuery
        if (typeof $ != 'undefined')
            $(document).trigger('consentua-ready');

        // and NATIVE
        var event = new Event('consentua-ready');
        document.body.dispatchEvent(event);

        comms.send("consentua-ready", {
            height: 600
        }); // Send a message back to the wrapper to confirm that the widget is (notionally) ready
    }

    /**
     * Check if all values on the template are set to true or false (ie not null)
     */
    self.isConsentComplete = function () {
        var pgs = self.template.getPurposeGroups();
        for (var pgid in pgs) {
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
    self.getConsent = function () {
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
     * Set consent
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
    self.setConsent = function (purposeGroupIds, consented) {
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
        consents[purposeGroupIds] = bconsented;

        comms.send('consentua-set', {
            consents: consents,
            complete: self.isConsentComplete()
        });
    }

    /**
 * Set all consent
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
    self.setPgConsent = function (purposeGroupIds, consented) {
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