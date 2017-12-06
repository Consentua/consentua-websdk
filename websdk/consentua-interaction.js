/*
 This is the library for consentua *interactions* to use. It provides a
 framework and library to communicate with the rest of the SDK
*/
var consentuacontroller = function(){

    if(!window.parent){
        alert("Parent window is not available; you probably need to use the test wrapper when developing with the Consentua SDK.");
        return;
    }

    var self = this;
    var comms = new WindowComms(window.parent);

    // Tell the parent that we're listening for bootstrap information (like the
    // template we should attach to)
    comms.send('consentua-waiting', false, init);

    // The parent window sends back a message that contains information about the
    // consent template to be used; this handles it
    function init(msg)
    {
        var payload = msg.payload;

        // TODO: Check that the provided template is valid
        window.consentua.template = payload.template;

        // TODO: Add helper methods to the template

        // Tell the current document that the consentua environment is ready
        // This should trigger interaction setup; i.e. the interaction should be
        // listening for it!
        var event = new Event('consentua-ready');
        window.dispatchEvent(event);

        msg.reply("consentua-ready"); // Send a message back to the wrapper to confirm that the widget is (notionally) ready
    }

};

document.addEventListener("DOMContentLoaded", function(){
  window.consentua = new ConsentuaController();
});
