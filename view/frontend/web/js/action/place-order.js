define(
    [
     	'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'mage/url',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function ($ ,quote, urlBuilder, storage, url, errorProcessor, customer, fullScreenLoader) {
        'use strict';
        var agreementsConfig = window.checkoutConfig.checkoutAgreements;

        return function (paymentData, redirectOnSuccess, messageContainer) {
            var serviceUrl,
                payload;

            redirectOnSuccess = redirectOnSuccess !== false;
            
            if (agreementsConfig.isEnabled) {
                
            var agreementForm = $('.payment-method._active form[data-role=checkout-agreements]'),
                agreementData = agreementForm.serializeArray(),
                agreementIds = [];

            agreementData.forEach(function(item) {
                agreementIds.push(item.value);
            });

            paymentData.extension_attributes = {agreement_ids: agreementIds};
            }
            

            /** Checkout for guest and registered customer. */
            if (!customer.isLoggedIn()) {
            	serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/set-payment-information', {
            		cartId:  quote.getQuoteId()
                });
                payload = {
                    cartId: quote.getQuoteId(),
                    email: quote.guestEmail,
                    paymentMethod: paymentData,
                    billingAddress: quote.billingAddress()
                };
            } else {
            	serviceUrl = urlBuilder.createUrl('/carts/mine/set-payment-information', {});
                payload = {
                    cartId: quote.getQuoteId(),
                    paymentMethod: paymentData,
                    billingAddress: quote.billingAddress()
                };
            }

            fullScreenLoader.startLoader();

            return storage.post(
                serviceUrl, JSON.stringify(payload)
            ).done(
            		function () {
                        window.location.replace(url.build('hgw/'));
                    }
            ).fail(
                function (response) {
                    errorProcessor.process(response, messageContainer);
                    fullScreenLoader.stopLoader();
                }
            );
        };
    }
);
