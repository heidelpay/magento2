define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Heidelpay_Gateway/js/action/place-order',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/checkout-data',
        'moment'
    ],
    function ($, Component, placeOrderAction, additionalValidators, selectPaymentMethodAction, checkoutData, moment) {
        'use strict';

        var self = this;

        // add IBAN validator
        $.validator.addMethod(
            'validate-iban', function (value) {
                var pattern = /[A-Z]{2}[0-9]{13,29}/i;
                return (pattern.test(value));
            }, $.mage.__('The given IBAN is invalid.')
        );

        return Component.extend({

            /**
             * Property that indicates, if the payment method is storing
             * additional data.
             */
            savesAdditionalData: false,

            defaults: {
                template: 'Heidelpay_Gateway/payment/heidelpay-form'
            },

            /**
             * Indicates if the payment method is storing addtional
             * information for the payment.
             *
             * @returns {boolean}
             */
            isSavingAdditionalData: function() {
                return this.savesAdditionalData;
            },

            /**
             * Function to load additional payment data if the payment method requires/offers it.
             *
             * This method needs to be overloaded by the payment renderer components, if
             * additional information is needed.
             */
            getAdditionalPaymentInformation: function() {},

            /**
             * Function to receive the customer's birthdate.
             *
             * This method needs to be overloaded by the payment renderer component, if needed.
             */
            getBirthdate: function() {},

            /**
             * Redirect to hgw controller
             * Override magento placepayment function
             */
            placeOrder: function (data, event) {
                var self = this,
                    placeOrder;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    placeOrder = placeOrderAction(
                        this.getData(),
                        this.redirectAfterPlaceOrder,
                        this.messageContainer,
                        this.isSavingAdditionalData()
                    );

                    $.when(placeOrder).fail(function () {
                        self.isPlaceOrderActionAllowed(true);
                    }).done(this.afterPlaceOrder.bind(this));
                    return true;
                }
                return false;
            },

            /**
             * Extends the parent selectPaymentMethod function.
             *
             * @return {Boolean}
             */
            selectPaymentMethod: function () {
                // call to the function which pulls additional information for a payment method, if needed.
                this.getAdditionalPaymentInformation();

                // from here, the body matches the default selectPaymentMethod function.

                selectPaymentMethodAction(this.getData());
                checkoutData.setSelectedPaymentMethod(this.item.method);

                return true;
            }
        });
    }
);
