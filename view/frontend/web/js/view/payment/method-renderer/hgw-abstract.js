define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Heidelpay_Gateway/js/action/place-order',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/select-billing-address',
        'moment'
    ],
    function ($, Component, placeOrderAction, additionalValidators, selectPaymentMethodAction, checkoutData, quote, selectBillingAddress, moment) {
        'use strict';

        // add IBAN validator
        $.validator.addMethod(
            'validate-iban', function (value) {
                var pattern = /^[A-Z]{2}[0-9]{13,29}$/i;
                return (pattern.test(value));
            }, $.mage.__('The given IBAN is invalid.')
        );
        // if selected date was invalid method will be called with date = null and validation will fail.
        $.validator.addMethod(
            'valid-date', function (date){
                return (date);
            }, $.mage.__('Invalid date.')
        );
        $.validator.addMethod(
            'is-customer-18', function (date){
                var inputDate = new Date(date);
                var currentDate = new Date();
                var is18 = new Date(currentDate-inputDate).getFullYear() - new Date(0).getFullYear() >= 18;

                return is18;
            }, $.mage.__('You have to be at least 18.')
        );

        $.validator.setDefaults({
            ignore: ''
        });

        return Component.extend({

            /**
             * Property that indicates, if the payment method is storing
             * additional data.
             */
            savesAdditionalData: false,

            defaults: {
                template: 'Heidelpay_Gateway/payment/heidelpay-form',
                useShippingAddressAsBillingAddress: false,
                hgwDobYear: '',
                hgwDobMonth: '',
                hgwDobDay: '',
                hgwSalutation: ''
            },

            /**
             * Indicates if the payment method is storing additional
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
            getBirthdate: function() {
                var day = this.hgwDobDay();
                var date = new Date(this.hgwDobYear(), this.hgwDobMonth(), day);

                // checks whether created date is same as input and return null if not.
                if(!(Boolean(+date) && date.getDate() == day)) {return null;}
                return moment(date).format('YYYY-MM-DD');
            },

            /**
             * Function to receive the customer's full name.
             */
            getFullName: function() {
                var billingAddress = quote.billingAddress();
                var name = this.getNameFromAddress(billingAddress);

                // fallback, if name isn't set yet.
                if (name === '') {
                    var tmpName = window.customerData;

                    if (tmpName !== null) {
                        if (typeof tmpName.firstname !== 'undefined' && tmpName.firstname !== null) {
                            name += tmpName.firstname;
                        }

                        if (typeof tmpName.middlename !== 'undefined' && tmpName.middlename !== null) {
                            name +=  ' ' + tmpName.middlename;
                        }

                        if (typeof tmpName.lastname !== 'undefined' && tmpName.lastname !== null) {
                            name += ' ' + tmpName.lastname;
                        }
                    }
                }

                return name;
            },

            getNameFromAddress: function(address) {
                var name = '';

                if (address !== null) {
                    if (typeof address.firstname !== 'undefined' && address.firstname !== null) {
                        name += address.firstname;
                    }

                    if (typeof address.middlename !== 'undefined' && address.middlename !== null) {
                        name += ' ' + address.middlename;
                    }

                    if (typeof address.lastname !== 'undefined' && address.lastname !== null) {
                        name += ' ' + address.lastname;
                    }
                }
                return name;
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

                if(this.useShippingAddressAsBillingAddress) {
                    selectBillingAddress(quote.shippingAddress());
                }

                return true;
            }
        });
    }
);
