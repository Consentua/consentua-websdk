<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Consentua Linear UI (Legacy)</title>

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
        window.consentua.addString('select', {'en': 'Use this option'});
        window.consentua.addString('agree-multi', {'en': 'I agree to these uses of my data'});

        console.log("Consentua is ready; setting up the default interaction", window.consentua.template);

        $('#question').text(window.consentua.template.Question);
        $('#explanation').text(window.consentua.template.Explanation);

        var ps = window.consentua.template.getPurposeGroups()[0].Purposes; // Get purposes in group 0
        console.log("Purposes", ps);
        for(var i in ps)
        {
            var p = ps[i];
            var purposes = "";
            console.debug("P " + i, p);

            var li = $("<li class=\"group\"></li>").appendTo($('#groups'));
            var purposes = $('<ol></ol>').appendTo(li);

            purposes.append("<li><div class=\"txt\">" +
            "<span class=\"txt_data_pre\">" + p.DataUseText + "</span> <span class=\"txt_data\">" + p.DataUse + "</span>" +
            "<span class=\"txt_purpose_pre\">" + p.DataPurposeText + "</span> <span class=\"txt_purpose\">" + p.DataPurpose + "</span>" +
            "</div></li>");

            var agree = $("<div class=\"agree\"><span class=\"agree-txt\">" + window.consentua.getString('select') + "</span></div>").appendTo(li);
            var check = $("<label class=\"switch\"><input type=\"radio\" name=\"choice\" /><span class=\"slider\"></span></span>").appendTo(agree);

            (function(pid, groupel){ // Trap purpose id in a closure
                $(check).on('change', function(e, cascade){
                    var t = $(e.target);
                    var consent = $(t).is(':checked');
                    console.log("Select", pid, consent);

                    if(consent){
                        window.consentua.setPurposeConsent(pid, true);
                        groupel.addClass('allowed');
                    }
                    else {
                        window.consentua.setPurposeConsent(pid, false);
                        groupel.removeClass('allowed');
                    }

                    // Update the other, non-selected, radios
                    if(typeof cascade == 'undefined' || cascade != false)
                    {
                        $('input[type="radio"]').each(function(i, el){
                            if(!$(el).is(':selected')) {
                                $(el).trigger('change', [false]);
                            }
                        });
                    }
                });
            })(p.Id, li);

            var precheck = false;
            if(precheck = window.consentua.getPurposeConsent(p.PurposeID))
            {
                check.click();
            }

            console.log("Existing consent for " + p.Id, precheck);
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
