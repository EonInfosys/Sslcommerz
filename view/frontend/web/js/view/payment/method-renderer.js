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
                type: 'sslcommerz_pay',
                component: 'Sslcommerz_Payment/js/view/payment/method-renderer/sslcommerz'
            }
        );
        return Component.extend({});
    }
);
