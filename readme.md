Consentua Web Integration
=========================

This repository contains everything (and more!) that's required to integrate Consentua into web pages.

There are two use cases for integrating with Consentua:
1. Using Consentua to obtain consent as part of a user journey
2. Building new interactions for obtaining consent


## Embedding Consentua into a Web Page

See index.html, which includes examples and documentation for deploying Consentua on a webpage. Importantly, you do NOT need to clone this repository!


## Building new interactions for obtaining consent

To build custom consent interactions/interfaces, see the example provided in ui-simple which is heavily commented. You can load the javascript libraries (consentua-interaction.js, and comms.js) directly from websdk.consentua.com

### For testing: 

* Host your interaction on a web server, it's OK if this is only available locally.
* Use the normal process to embed a consentua template into another web page, but pass an "ix" argument via the args option of the ConsentuaUIWrapper. That argument should be the URL of the interaction, something like `{ix: "http://127.0.0.1:8080/my-interaction/"}`
* Load the embedding page in a web browser. The live consentua service (or test/development, depending on which mebed library you've loaded) will embed the interaction that you've specified via the ix argument, rather that the one that the specified template is usually bound to.

### For deployment:

Once complete, you'll need to contact support to have your custom interaction verified and made available for you to bind to consent templates in your Consentua account.
