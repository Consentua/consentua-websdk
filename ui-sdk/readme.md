Consentua Interaction SDK

This is the SDK for interaction developers.  i.e developers that want to build
widgets for others to embed into their consentua-enabled apps

Regardless of the platform in use (and they should all behave identically), these
files will be available, relative to the developed UI itself, at /sdk/

During development it will be sufficient to have a copy of these files inside an
/sdk/ directory, BUT they will be replaced with the most recent 'clean' version
when the interaction is delivered via Consentua for real.

* consentua.js: Implements window.consentua, the main interface for interacting
with the SDK.  ** Interactions MUST load this script. (/sdk/consentua.js) **
* resources: Here we'll put images and other things that interface builders can
reuse
