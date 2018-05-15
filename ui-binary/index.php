<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Consentua Binary UI</title>

    <link rel="stylesheet" href="default.css" type="text/css" />

    <script
    src="https://code.jquery.com/jquery-3.2.1.slim.min.js"
    integrity="sha256-k2WSCIexGzOj3Euiig+TlR8gA0EmPjuc79OEeY5L45g="
    crossorigin="anonymous"></script>

    <!-- This is the main library; eventually it should be loaded from the
    online web sdk server. -->
<script src="../websdk/consentua-interaction.js"></script>
<script src="../websdk/comms.js"></script>

</head>
<body>

    <script>

    $(document).on('consentua-ready', function()
    {
        /**
         * Register custom strings with the consentua i18n engine
         */
        window.consentua.addString('agree-single', {'en': 'I agree to this use of my data'});
        window.consentua.addString('agree-multi', {'en': 'I agree to these uses of my data'});

        window.consentua.addString('will-use', {'en': 'We will use'});
        window.consentua.addString('for-purpose', {'en': 'For the purpose of'});

        console.log("Consentua is ready; setting up the default interaction", window.consentua.template);

        $('#question').text(window.consentua.template.Question);
        $('#explanation').text(window.consentua.template.Explanation);

        var pgs = window.consentua.template.getPurposeGroups();
        console.log("Purpose groups", pgs);
        for(var i in pgs)
        {
            var pg = pgs[i];
            var purposes = "";
            console.debug("PG " + i, pg);

            var li = $("<li class=\"group\"></li>").appendTo($('#groups'));
            var purposes = $('<ol></ol>').appendTo(li);

            for(var j in pg.Purposes) {
                purposes.append("<li><div class=\"txt\">" +
                "<span class=\"txt_data_pre\">" +  window.consentua.getString('will-use') + "</span> <span class=\"txt_data\">" + pg.Purposes[j].DataUse + "</span>" +
                "<span class=\"txt_purpose_pre\">" +  window.consentua.getString('for-purpose') + "</span> <span class=\"txt_purpose\">" + pg.Purposes[j].DataPurpose + "</span>" +
                "</div></li>");
            }

            var agree = $("<div class=\"agree\"><span class=\"agree-txt\">" + window.consentua.getString(pg.Purposes.length > 1 ? 'agree-multi' : 'agree-single') + "</span></div>").appendTo(li);
            var check = $("<label class=\"switch\"><input type=\"checkbox\" /><span class=\"slider\"></span></span>").appendTo(agree);

            (function(pgid, groupel){ // Trap purposegroup id in a closure
                $(check).on('change', function(e){
                    var t = $(e.target);
                    var consent = $(t).is(':checked');
                    console.log("Toggle", pgid, consent);

                    if(consent){
                        groupel.addClass('allowed');
                    }
                    else {
                        groupel.removeClass('allowed');
                    }

                    window.consentua.setPgConsent(pgid, consent);
                });
            })(pg.PurposeGroupID, li);

            var precheck = false;
            if(precheck = window.consentua.isConsentedGroup(pg.PurposeGroupID))
            {
                check.click();
            }

            console.log("Existing consent for " + pg.PurposeGroupID, precheck);
        }



        $('#loading').hide();
        $('#main').show();

        // Tell the controller that the UI is ready
        window.consentua.ready();

    });
    </script>

    <div id="loading">
        Please wait...
    </div>

    <div id="main">

        <div id="intro">
            <h1 id="question"></h1>
            <p id="explanation"></p>
        </div>

        <ol id="groups">

        </ol>

    </div>

</body>
</html>
