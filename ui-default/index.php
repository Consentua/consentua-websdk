<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Consentua Default UI</title>

    <link rel="stylesheet" href="default.css" type="text/css" />

    <script
    src="https://code.jquery.com/jquery-3.2.1.slim.min.js"
    integrity="sha256-k2WSCIexGzOj3Euiig+TlR8gA0EmPjuc79OEeY5L45g="
    crossorigin="anonymous"></script>

    <!-- This is the main library; eventually it should be loaded from the
    online web sdk server.
-->
<script src="../websdk/consentua-interaction.js"></script>
<script src="../websdk/comms.js"></script>

</head>
<body>

    <script>

    $(document).on('consentua-ready', function()
    {
        console.log("Consentua is ready; setting up the default interaction", window.consentua.template);

        var pgs = window.consentua.template.getPurposeGroups();
        console.log("Purpose groups", pgs);
        for(var i in pgs)
        {
            var pg = pgs[i];
            var purposes = "";
            console.debug("PG " + i, pg);

            var li = $('#groups').append("<li></li>");


            li.append();
            var purposes = li.append('<ol class="purposes"></ol>');
            for(var j in pg.Purposes) {
                purposes.append("<li>" +
                "<span class=\"txt_data_pre\">" + pg.Purposes[j].DataUseText + "</span><span class=\"txt_data\">" + pg.Purposes[j].DataUse + "</span>" +
                "<span class=\"txt_purpose_pre\">" + pg.Purposes[j].DataPurposeText + "</span><span class=\"txt_purpose\">" + pg.Purposes[j].DataPurpose + "</span>" +
                "</li>");


            }
        }


        $('#loading').hide();
        $('#main').show();

        $('#main input').on('change', function(){

            var consent = $(this).is(':checked');





        });

        // Tell the controller that the UI is ready
        window.consentua.ready();

    });
    </script>

    <div id="loading">
        Please wait...
    </div>

    <div id="main">

        <div id="intro">

        </div>

        <ol id="groups">

        </ol>

    </div>

</body>
</html>
