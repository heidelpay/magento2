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
                hgwBankSelection: '',
                hgwBrandValues: [null],
                hgwHolder: ''
            },

            // set observers to update values in frontend when values are changed.
            initObservable: function() {
                this._super()
                    .observe([
                        'hgwBrandValues', 'hgwBankSelection', 'hgwHolder'
                    ]);

                return this;
            },

            getData: function () {
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


                $( document ).ajaxStop(function() {
                    return this;
                });
            },

            getCode: function () {
                return 'hgwidl';
            },

            setAvailableBanks: function () {
                var method = this.item.method;

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
                        this.hgwBrandValues(response);
                    }

                    return this;
                }).fail(function (response) {
                    console.log('request failed: no banks available');
                });

            },
            validate: function() {
                var form = $('#hgw-ideal-form');

                return form.validation() && form.validation('isValid');
            }
        });
    }
);