window.addEventListener("load", function() {
    var clientID = 'emspay-dev',
        publicConfig = JSON.parse(Ecwid.getAppPublicConfig(clientID)),
        paymentMethodTitle = '';

    // Set payment method title
    Ecwid.OnAPILoaded.add(function () {
        getPaymentTitle(function(responseText) {
            paymentMethodTitle = responseText;
        });
    });

    // Execute the code after the necessary page has loaded
    Ecwid.OnPageLoaded.add(function (page) {
        if (page.type == "CHECKOUT_PAYMENT_DETAILS") {
            ecwidUpdatePaymentData();
        }
    });

    // Function to process the payment page
    function ecwidUpdatePaymentData() {
        var iconsSrcList = [];
        // Custom styles for icons for our application
        var customStyleForPaymentIcons = document.createElement('style');
        customStyleForPaymentIcons.innerHTML = ".ecwid-PaymentMethodsBlockSvgCustom { display: inline-block; width: 40px; height: 26px; background-color: #fff !important; border: 1px solid #e2e2e2 !important;}";
        document.querySelector('body').appendChild(customStyleForPaymentIcons);

        // Set your custom icons or use your own URLs to icons here
        for (let i = 0; i < publicConfig.gateways.length; i++) {
            iconsSrcList.push(publicConfig.appUrl + '/assets/images/' + publicConfig.gateways[i] + '.png');
        }

        setTimeout(function () {
            //var optionsContainers = document.getElementsByClassName('ecwid-Checkout')[0].getElementsByClassName('ecwid-PaymentMethodsBlock-PaymentOption');
            var optionsContainers = document.getElementsByClassName('ec-cart')[0].getElementsByClassName('ec-radiogroup__item');
            for (var i = 0; i < optionsContainers.length; i++) {

                if (paymentMethodTitle && optionsContainers[i].innerHTML.indexOf(paymentMethodTitle) !== -1) {
                    var container = getPaymentContainer(optionsContainers[i]);
                    if (container && container.getElementsByTagName('img').length === 0) {
                        for (i = 0; i < iconsSrcList.length; i++) {
                            var image = document.createElement('img');
                            image.setAttribute('src', iconsSrcList[i]);
                            image.setAttribute('class', 'ecwid-PaymentMethodsBlockSvgCustom');
                            if (container.children.length !== 0) {
                                image.style.marginLeft = '5px';
                            }
                            container.appendChild(image);
                        }
                    }
                }
            };
        }, 1000);
    }

    // Function to process current payment in the list
    function getPaymentContainer(label) {
        var container = label.getElementsByClassName('payment-methods');
        if (container.length === 0) {
            container = [document.createElement('div')];
            container[0].className += 'payment-methods';
            label.getElementsByClassName('ec-radiogroup__info')[0].appendChild(container[0]);
        }

        return container[0];
    }

    // Get payment title from store profile
    function getPaymentTitle(cb){
        // Execute the code after API loaded
        const XHR = new XMLHttpRequest();
        const url = "https://app.ecwid.com/api/v3/" + Ecwid.getOwnerId() + "/profile/paymentOptions?token=" + Ecwid.getAppPublicToken(clientID);

        // Set up our request
        XHR.open( "GET", url, true );
        XHR.responseType = 'json';

        // The data sent is what the user provided in the form
        XHR.send();

        XHR.onreadystatechange = function() {
            if (XHR.readyState == 4 && XHR.status == 200) {
                XHR.response.forEach(function(item, i, arr) {
                    if(item.appClientId == clientID) {
                        if (typeof cb === 'function') cb(item.checkoutTitle);
                    }
                });
            } else {
                if (typeof cb === 'function') cb(false);
            }
        };
    }
});