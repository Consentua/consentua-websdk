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
var intcomms = new WindowComms($('#consentua-interaction').get(0));

var c = new ConsentuaClient(args['c'], args['s'], args['k'], 'en');

/**
* A user identifier *may* be passed as the uid field in the hash, but otherwise an anonymous one is generated
*/
if(typeof args['uid'] == 'undefined'){
  var ts = Date.now();
  args['uid'] = 'anon-' + args['s'] + '-' + Math.floor(ts / 1000) + '-' + Math.floor(Math.random() * 1000000);
  c.addUser(args.uid);
}
else{

}

console.log("Consentua UID: " + args.uid);

c.login();



// Wait for the interaction to be ready, then send it the template
intcomms.addHandler('consentua-waiting', function(msg){
  c.getTemplate(args['t'], function(template){
      msg.reply(template);
  });
});

// Wait for consent to be set
intcomms.addHandler('consentua-set', function(msg){

  // TODO: Save the consent


  wrapcomms.send('consentua-done', {
      uid: args['uid'],
      consents: msg.consents
  });
});


});
