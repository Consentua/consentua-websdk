Consentua Web Integrations
==========================

This repository contains everything (and more!) that's required to integrate Consentua into web pages.

There are two use cases for integrating with Consentua:
1. Using Consentua to obtain consent as part of a user journey
2. Building new interactions for obtaining consent


## 1. Embedding Consentua into a Web Page


1. Include the Consentua embedding library in the HEAD section of the page
```
<script src="https://websdk-test.consentua.com/websdk/consentua-embed.js" type="text/javascript"></script>
```

2. Add an iframe element for the consentua element to be loaded into
```
<iframe id="consentua-widget" src="" style="box-model: border-box; width: 100%; max-width: 600px; min-height: 700px;"></iframe>
```

3. Add a short script to set up the consentua interaction, and receive the output. jQuery is assumed in the snippet below, but is not required.
```
  <script>
    // Consentua account details
    var cid = 'X'; // Consentua customer ID
    var sid = 'Y'; // Consentua service ID
    var skey = 'Z'; // Consentua service key
    var tid = 'Q'; // Consentua consent template ID
    
    // UID is a string that identifies the user within your own service; set 
    // this to false to generate one automatically (for instance in flows where 
    // identity is not yet known, like a newsletter sign-up)
    var uid = ''; 

    // When consent is set, it's passed to a callback function
    // You'll probably want to store parts of this message, and update page state to
    // (for instance) allow form submission to take place
    var cb = function(msg){
        console.log("Consent received from Consentua", msg);
    };

    var iframe = document.getElementById('consentua-widget');
    var cwrap = new ConsentuaUIWrapper(iframe, cid, uid, tid, sid, skey, cb);
  </script>
```

## 2. Building custom interactions

By default, Consentua chooses an appropriate interaction based on the properties such as the size and complexity of the consent template that's being displayed.

## 3. I want to run my own websdk server/ run locally 

Clone this repo and make sure you have node/npm installed and bower

Install the dependencies

```
$ npm i
cd ui-polymer
$ bower i
```

Run the server

```
$ npm run start
```

Server will now be running on `http://localhost:3000` with a example page at `/wrapper.html`


### Deploying to [insert cloud service here]

TODO
