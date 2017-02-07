define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
    	'use strict';
        rendererList.push(
            {
                type: 'hgwcc',
                component: 'Heidelpay_Gateway/js/view/payment/method-renderer/hgw-abstract'
            },
            {
                type: 'hgwdc',
                component: 'Heidelpay_Gateway/js/view/payment/method-renderer/hgw-abstract'
            },
            {
                type: 'hgwsue',
                component: 'Heidelpay_Gateway/js/view/payment/method-renderer/hgw-abstract'
            },
            {
                type: 'hgwpal',
                component: 'Heidelpay_Gateway/js/view/payment/method-renderer/hgw-abstract'
            },
            {
                type: 'hgwpp',
                component: 'Heidelpay_Gateway/js/view/payment/method-renderer/hgw-abstract'
            },
            {
                type: 'hgwgp',
                component: 'Heidelpay_Gateway/js/view/payment/method-renderer/hgw-abstract'
            }
            
        );
        return Component.extend({});
    }
);