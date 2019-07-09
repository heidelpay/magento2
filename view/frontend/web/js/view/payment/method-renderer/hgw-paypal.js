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
            defaults: {
                useShippingAddressAsBillingAddress: true
            }

        });
    }
);