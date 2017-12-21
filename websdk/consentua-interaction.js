/*
 This is the library for consentua *interactions* to use. It provides a
 framework and library to communicate with the rest of the SDK
*/
var ConsentuaController = function(){

    if(!window.parent){
        alert("Parent window is not available; you probably need to use the test wrapper when developing with the Consentua SDK.");
        return;
    }

    var self = this;
    var comms = new WindowComms(window.parent);

    // Tell the parent that we're listening for bootstrap information (like the
    // template we should attach to)
    console.log("Waiting for consentua template...");
    comms.send('consentua-waiting', false, init);

    // The parent window sends back a message that contains information about the
    // consent template to be used; this handles it
    function init(msg)
    {
        console.log("Received consentua bootstrap info", msg);

        // TODO: Check that the provided template is valid
        self.template = msg.message.template;
        self.consents = msg.message.consents;

        // TODO: Add helper methods to the template

        // Tell the current document that the consentua environment is ready
        // This should trigger interaction setup; i.e. the interaction should be
        // listening for it!

        // jQuery
        if(typeof $ != 'undefined')
          $(document).trigger('consentua-ready');

        // and NATIVE
        var event = new Event('consentua-ready');
        document.body.dispatchEvent(event);


        comms.send("consentua-ready", {height: document.scrollHeight}); // Send a message back to the wrapper to confirm that the widget is (notionally) ready
    }

    /**
     * Helpers to get/set consent
     */
    self.getConsent = function(purposeGroupId, consented){
        return self.consents[purposeGroupID];
    }

    self.setConsentGroup = function(purposeGroupId, consented){

        for(var purpose in self.consents) {
          setConsent(purpose, consented);
        }

        comms.send('consentua-set', {consents: self.consents});
    }

    self.setConsent = function(purposeID, consented) {
        self.consents[purposeID] = consented;
    }

    self.setConsentPurpose = function(purposeId, consented){
        setConsent(purposeId, consented);
        comms.send('consentua-set', {consents: self.consents});
    }

    /**
     *
     */

};

document.addEventListener("DOMContentLoaded", function(){
  window.consentua = new ConsentuaController();
});
