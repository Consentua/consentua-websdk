Expose via window.consentua

deployed live at [websdk.mybluemix.net](https://websdk.mybluemix.net/wrapper.html)

[codepen](https://codepen.io/sideshowjack/pen/bayjGb)

## developing
install polymer dependencies
```
npm install
#ui-polymer/
$ bower install
```
then start the server
```
npm start
```

## deploying
make sure you have `cf`  and `polymer-cli` cli's installed
```
#ui-polymer/
$ polymer build
```
```
$ cf login
$ cf push websdk
```

## ui-polymer

### notes:
* currently only 100% works with a SINGLE BINARY template
* has 3 routes
  * `/ui-polymer#/binary`
  * `/ui-polymer#/linear`
  * `/ui-polymer#/mixed` for templates with more than 1 pg, can be mixed
  * `/ui-polymer#/none` will display err message 'no templates found :('

## consentua-sdk-js

Supply:
* information about current request
  * template
  * current token (if any); else some kind of "null" token
  * user? like what? just the UID in case they want to look stuff up?

Receive:
* consent granted; plus information about WHAT, as a js object
* interaction aborted



## Web Mode

In web mode we load the interaction in an iframe.

We might use nested iframes?

Calling App [Untrusted, off-domain]
  -> Consentua wrapper [Trusted, websdk.consentua.com] : Talk to consentua service, load appropriate interaction
    -> Interaction [Semi-trusted, client-xxx.consentua.com] : Display interaction, talk to parent frame through window.postMessage

MAIN APP
  -> CLIENTXXX.CONSENTUA.COM/UI/XXX/

Interaction is by way of window.postMessage

## Interaction Anatomy 

Interactions must have:
* main.html: This HTML file is loaded into the iframe/webview
