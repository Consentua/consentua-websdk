Expose via window.consentua

## ui-polymer

### notes:
* has 3 routes
  * `/ui-polymer#/binary`
  * `/ui-polymer#/linear`
  * `/ui-polymer#/mixed` for templates with more than 1 pg, can be mixed
  * `/ui-polymer#/none` will display err message 'no templates found :('

### SDK feature requests:
* ~~needs to be able to handle multiple templates, will be better to use dynamic polymer pages for each template than load new instance of the page with each template~~
* remove `getConsent()` in favor of having the consent already attached to the purposes eg `template.PurposeGroups[0].Purposes[0].Consent = true`
  * if not possible then please make the object returned by `getConsent()` not use the id's as the object keys eg use `{purposeId: 1, consent: true}`
* add seperate `setPgConsent(pg, consent)` for setting entire purpose group consents t/f

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
