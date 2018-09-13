define(
    [
        'jquery',
        'Heidelpay_Gateway/js/view/payment/method-renderer/hgw-abstract',
        'Heidelpay_Gateway/js/action/place-order',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/quote',
        'mage/url'
    ],
    function ($, Component, placeOrderAction, urlBuilder, storage, additionalValidators, customer, quote, url) {
        'use strict';

        return Component.extend({

            /**
             * Property that indicates, if the payment method is storing
             * additional data.
             */
            savesAdditionalData: true,

            defaults: {
                template: 'Heidelpay_Gateway/payment/heidelpay-ideal-form',
                hgwBankSelection: 'INGBNL2A',
                hgwBrandValues: [null],
                hgwBrandNames: [null],
                hgwHolder: ''
            },

            // set observers to update values in frontend when variable is changed.
            initObservable: function() {
                this._super()
                    .observe([
                        'hgwBrandValues', 'hgwBrandNames', 'hgwBankSelection', 'hgwHolder'
                    ]);

                return this;
            },

            getData: function () {
                console.log(this.hgwBankSelection)
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'hgw_bank_name': this.hgwBankSelection(),
                        'hgw_holder': this.hgwHolder()
                    }
                };
            },

            initialize: function () {
                this._super();
                //this.getAdditionalPaymentInformation();
                this.hgwHolder(this.getFullName());
                this.setAvailableBanks();

                console.log(this.hgwBrandNames);

                $( document ).ajaxStop(function() {
                    return this;
                });
            },


            getCode: function () {
                return 'hgwidl';
            },

            setAvailableBanks: function () {
                var method = this.item.method;
                var payment = window.checkoutConfig.payment;
                console.log(method);
                console.log(payment);

                //if (payment !== undefined) {
                    $.ajax({
                        showLoader: true,
                        url: url.build('hgw/index/initializepayment'),
                        data: {
                            method: method
                        },
                        type: 'POST',
                        dataType: 'json',
                        context: this
                    }).done(function (data) {
                        var response = JSON.parse(data);
                        // set the iDeal brand information, which comes from the payment
                        if (response !== null) {
                            this.hgwBrandValues(response.brandValues);
                            this.hgwBrandNames(response.brandNames);
                            console.log('json done');
                            console.log(response.brandValues);
                            console.log(response.brandNames);
                            return this;
                        }
                        return this;
                    }).fail(function (response) {
                        console.log('response fail: ' + response);
                        // errorProcessor.process(response, this.messageContainer);
                        // fullScreenLoader.stopLoader();
                        // alert ("Error");
                        // window.location.replace(url.build('checkout/'));
                    });
                //}
            }
        });
    }
);