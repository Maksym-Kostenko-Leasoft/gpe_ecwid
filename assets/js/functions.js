window.addEventListener( "load", function () {

    // Access the form element...
    let form = document.getElementById( "emspay_app_settings" );

    // ...and take over its submit event.
    form.addEventListener( "submit", function ( event ) {
        event.preventDefault();

        sendData();
    });

    function sendData() {
        const XHR = new XMLHttpRequest();

        // Bind the FormData object and the form element
        const FD = new FormData( form );

        var card_ems = document.getElementsByClassName("a-card--ems");

        // Define what happens on successful data submission
        XHR.addEventListener( "load", function(event) {
            var jsonData = JSON.parse(event.target.response);

            card_ems[0].classList.remove("hidden");
            if(jsonData.success) {
                card_ems[0].classList.remove("a-card--error");
                card_ems[0].classList.add("a-card--success");
            } else {
                card_ems[0].classList.remove("hidden");
                card_ems[0].classList.add("a-card--error");
            }
            card_ems[0].getElementsByClassName('cta-block__content')[0].childNodes[0].nodeValue=jsonData.msg;
        });

        // Define what happens in case of error
        XHR.addEventListener( "error", function( event ) {
            card_ems[0].classList.remove("hidden");
            card_ems[0].classList.add("a-card--error");
            card_ems[0].getElementsByClassName('cta-block__content')[0].childNodes[0].nodeValue=event.target.responseText;
        });

        // Set up our request
        XHR.open( "POST", document.getElementById('emspay_app_settings').action );

        // The data sent is what the user provided in the form
        XHR.send( FD );
    }
});

