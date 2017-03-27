define(
    [
        'jquery',
        'Heidelpay_Gateway/js/view/payment/method-renderer/hgw-abstract',
        'Heidelpay_Gateway/js/action/place-order',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/quote'
    ],
    function ($, Component, placeOrderAction, urlBuilder, storage, additionalValidators, customer, quote) {
        'use strict';

        return Component.extend({

            /**
             * Property that indicates, if the payment method is storing
             * additional data.
             */
            savesAdditionalData: true,

            defaults: {
                template: 'Heidelpay_Gateway/payment/heidelpay-directdebit-form',
                hgwIban: '',
                hgwHolder: ''
            },

            initialize: function () {
                this._super();
                this.getAdditionalPaymentInformation();

                return this;
            },

            initObservable: function() {
                this._super()
                    .observe([
                        'hgwIban', 'hgwHolder'
                    ]);

                return this;
            },


            getAdditionalPaymentInformation: function() {
                // recognition: only when there is a logged in customer
                if (customer.isLoggedIn()) {
                    // if we have a shipping address, go on
                    if( quote.shippingAddress() !== null ) {
                        var parent = this;
                        var serviceUrl = urlBuilder.createUrl('/hgw/get-payment-info', {});
                        var hgwPayload = {
                            quoteId: quote.getQuoteId(),
                            paymentMethod: this.item.method
                        };

                        storage.post(
                            serviceUrl, JSON.stringify(hgwPayload)
                        ).done(
                            function(data) {
                                var info = JSON.parse(data);

                                // set iban and account holder, if result is not empty.
                                if( info !== null ) {
                                    parent.hgwIban(info.hgw_iban);
                                    parent.hgwHolder(info.hgw_holder);
                                }
                            }
                        );
                    }
                }
            },

            getCode: function () {
                return 'hgwdd';
            },

            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'hgw_iban': this.hgwIban(),
                        'hgw_holder': this.hgwHolder()
                    }
                };
            },

            validate: function() {
                var form = $('#hgw-directdebit-form');

                return form.validation() && form.validation('isValid');
            }
        });
    }
);