/**
 * This script is run by the service at websdk.consentua.com
 */
$().ready(function(){

// Parse info from hash
var h = window.location.hash;
var hp = h.replace(/^#/, '').split(/&/);
var args = {};
for(var i in hp){
  var a = hp[i].split(/=/);
  args[a[0]] = a[1];
}

console.log("Initialise Consentua Web SDK Service", args);

if(typeof args['s'] == 'undefined' || typeof args['k'] == 'undefined' || typeof args['c'] == 'undefined' || typeof args['t'] == 'undefined'){
  console.error("Required parameter(s) missing - Check and update your consentua embed code").show();
  return;
}

/**
* Set up messaging with the embedding page, and the interaction itself
*/
var wrapcomms = new WindowComms(window.parent);
var intcomms = new WindowComms($('#consentua-interaction').get(0).contentWindow);

var c = new ConsentuaClient(args['c'], args['s'], args['k'], 'en');

// Do service login, then create a new user
c.login().fail(function(){console.err("Service login failed");}).then(function(){
    /**
     * A user identifier *may* be passed as the uid field in the hash, but otherwise an anonymous one is generated
     */
    if(typeof args['uid'] == 'undefined'){
      var ts = Date.now();
      args['uid'] = 'anon-' + args['s'] + '-' + Math.floor(ts / 1000) + '-' + Math.floor(Math.random() * 1000000);
    }

    var au = c.addUserIfNotExist(args.uid);
    au.then(function(userid){
        console.log("Consentua UID: ", args.uid, "API UserID: ", userid);
    });

    /**
     * In parallel, download the template
     */
     var gt = c.getTemplate(args.t);
     gt.done(function(template){
         console.log("Consentua template:", template);
     });

     /**
      * When the template and the user are both ready, load the interaction in the child iframe
      */
     // TODO: Should wait for user to be ready, but that's not working in client atm?!
     $.when(gt, au).then(function(template, userid){

         // TODO: Interaction type should be in template, but atm it isn't, so polyfill it
         if(typeof template.ixUrl == 'undefined') {

             console.log("Interaction URL is not provided by template, picking based on DisplayType", template.DisplayType);

             switch(template.DisplayType.toLowerCase())
             {
                 case 'linear':
                    template.ixUrl = '/ui-slider/main.html';
                    break;
                case 'binary':
                    template.ixUrl = '/ui-simple/main.html';
                    break;
             }

         }

         console.log("Template and user account are ready; loading interaction", template.ixUrl);

         // TODO: Check for existing user consents; this could be done while the interaction itself loads

         $('#consentua-interaction').attr('src', template.ixUrl);
     });
});



// Wait for the interaction to be ready, then send it the template information
// NB: Template info will already be loaded, so this should be quick
intcomms.addHandler('consentua-waiting', function(msg){
   console.log("Interaction indicated that it is waiting for template");

   // Now load the user, and their existing consents
   // TODO: Parallelise this, while interaction loads - see above

   var user = {NOT_IMPLEMENTED: true};

   var pTemplate = c.getTemplate(args['t']);
   var pConsents = c.getConsents(args['uid']);

   // When the existing consents and the template are ready, give them to the interaction
   $.when(pTemplate, pConsents).then(function(template, consents){
      msg.reply({template: template, consents: consents, user: user});
   });
 });

// Wait for interaction to be ready
intcomms.addHandler('consentua-ready', function(msg){
    console.log("Interaction indicated to service that it is ready");

    // Fit frame to interaction height
    var iframe = $('#consentua-interaction').get(0);
    iframe.style.height = msg.height; // The interaction should tell us how high it wants to be

    // Tell the embedding page, too
    wrapcomms.send('consentua-ready');
});

// Wait for consent to be set
intcomms.addHandler('consentua-set', function(msg){

  // TODO: Save the consent to API

  // Tell the customer site that the consent interaction is complete
  wrapcomms.send('consentua-set', {
      uid: args['uid'],
      consents: msg.consents
  });
});


});
