define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Heidelpay_Gateway/js/action/place-order',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Customer/js/model/customer',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/quote'
    ],
    function ($, Component, placeOrderAction, urlBuilder, storage, additionalValidators, customer, customerData, quote) {
        'use strict';

        return Component.extend({

            defaults: {
                template: 'Heidelpay_Gateway/payment/heidelpay-directdebit-form',
                hgwIban: '',
                hgwOwner: ''
            },

            initialize: function () {
                this._super();

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
                                    parent.hgwOwner(info.hgw_holder);
                                }
                            }
                        );
                    }
                }

                return this;
            },

            initObservable: function() {
                this._super()
                    .observe([
                        'hgwIban', 'hgwOwner'
                    ]);

                return this;
            },

            getCode: function () {
                return 'hgwdd';
            },

            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'hgw_iban': this.hgwIban(),
                        'hgw_owner': this.hgwOwner()
                    }
                };
            },

            /**
             * Redirect to hgw controller
             * Override magento placePayment function
             */
            placeOrder: function (data, event) {
                var self = this,
                    placeOrder;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    placeOrder = placeOrderAction(this.getData(), this.redirectAfterPlaceOrder, this.messageContainer);

                    $.when(placeOrder).fail(function () {
                        self.isPlaceOrderActionAllowed(true);
                    }).done(this.afterPlaceOrder.bind(this));
                    return true;
                }
                return false;
            }

        });
    }
);