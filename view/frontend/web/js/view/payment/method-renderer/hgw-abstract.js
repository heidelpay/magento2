define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Heidelpay_Gateway/js/action/place-order',
        'Magento_Checkout/js/model/payment/additional-validators'
    ],
    function ($, Component, placeOrderAction, additionalValidators) {
        'use strict';
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
                //if (this.savesAdditionalData)
                return this.savesAdditionalData;
            },

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
            }
        });
    }
);
