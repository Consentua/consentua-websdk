function WindowComms(targetWindow)
{
    var self = this;

    var commsID = 0; // Track messages sent via comms

    var handlers = {};

    var replyHandlers = {};

    console.log("Create comms channel between", window, targetWindow);

    // Send a message to the parent window
    self.send = function(msgType, msgPayload, msgReplyHandler, msgId){

        // Replies keep the ID of the parent, but otherwise a new ID should be assigned
        if(typeof msgId == 'undefined'){
            var msgId = ++commsID;
        }

        if(typeof msgReplyHandler !== 'undefined'){
            replyHandlers[msgId] = msgReplyHandler;
        }

        console.log("Send", msgType, msgPayload);

        targetWindow.postMessage({type: msgType, id: msgId, message:msgPayload}, "*"); };

    // Receive messages from the other window, and see if there's a handler for them
    self.recv = function(event){

        console.log("Receive message", event);

        if(event.source != targetWindow) // Only handle messages that come from the bound window
        {
            console.log("Received message doesn't belong to this handler (msg / bound window)", event, targetWindow);
            return;
        }

        var msg = event.data;

        // Add a reply method to the message so that it can be easily replied to
        msg.reply = function(msgPayload){
            self.send('_reply', msgPayload, undefined, msg.id);
        }

        // Find a handler for the message
        if(typeof handlers[msg.type] !== 'undefined')
        {
            handlers[msg.type](msg);
        }
        else
        {
            console.error("No handler for received message", msg);
        }
    };

    window.addEventListener("message", self.recv);

    // Register a handler
    self.addHandler = function(type, cb){
        handlers[type] = cb;
    }

    // Set up the special handler for replies
    self.addHandler('_reply', function(msg){
        if(typeof replyHandlers[msg.id] !== 'undefined')  {
            replyHandlers[msg.id](msg);
        }
        else {
          console.error("Received an unexpected reply (no reply handler is available)", msg);
        }
    });

};
