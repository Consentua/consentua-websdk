"use strict";
var express = require('express');

//------------------------------------------------------------------------------
// Express Setup
//------------------------------------------------------------------------------
var app = express();

//set app port
app.set('port', process.env.VCAP_APP_PORT || 3000);

// Force HTTPS redirect unless we are using localhost
app.use(httpsRedirect);

function httpsRedirect(req, res, next) {
    if (req.protocol === 'https' || req.header('X-Forwarded-Proto') === 'https' || req.hostname === 'localhost') {
        return next();
    }
    res.status(301).redirect("https://" + req.headers['host'] + req.url);
}

// start server
app.use(express.static('/'))

// Tell the app to listen for requests on port
app.listen(app.get('port'), function () {
    console.log('app listening on port ' + app.get('port'));
});


//------------------------------------------------------------------------------
// Routes
//------------------------------------------------------------------------------


app.use('/', express.static(__dirname + '/'));