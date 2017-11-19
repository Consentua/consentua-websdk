function WindowComms(targetWindow)
{
    var self = this;

    var commsID = 0; // Track messages sent via comms

    var handlers = {};

    var replyHandlers = {};

        // Send a message to the parent window
        self.send = function(msgType, msgPayload, msgReplyHandler, msgId){

            // Replies keep the ID of the parent, but otherwise a new ID should be assigned
            if(typeof msgId == 'undefined'){
              var id = ++commsID;
            }

            if(typeof msgReplyHandler !== 'undefined'){
                replyHandlers[id] = msgReplyHandler;
            }

            targetWindow.parent.postMessage({type: msgType, id: id, message: msgPayload}, "*");
        };

        // Receive messages from the other window, and see if there's a handler for them
        self.recv = function(msg){

            // Add a reply method to the message so that it can be easily replied to
            msg.reply = function(msgPayload){
                self.send('_reply', msgPayload, undefined, msg.id);
            }

            // Find a handler for the message
            if(typeof comms.handlers[msg.type] !== 'undefined')
            {
                handlers[msg.type](msg);
            }
            else
            {
                console.error("No handler for received message", msg);
            }
        };

        // Register a handler
        self.addHandler(type, cb){
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
}
