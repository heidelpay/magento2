<?php

namespace Heidelpay\Gateway\PaymentMethodes;

/**
 * heidelpay giropay Payment Method
 *
 * heidelpay Payment Method for giropay.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/magento2
 * @author Stephano Vogel
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */

use \Heidelpay\PhpApi\PaymentMethodes\GiropayPaymentMethod as HeidelpayPhpApiGiropay;

class HeidelpayGiropayPaymentMethod extends HeidelpayAbstractPaymentMethod
{
    /** @var string heidelpay Gateway Paymentcode */
    protected $_code = 'hgwgp';

    /**
     * Returns the redirect url to the giropay site.
     *
     * @param $quote
     * @return array $response An array with heidelpay processing results
     */
    public function getHeidelpayUrl($quote)
    {
        $this->_heidelpayPaymentMethod = new HeidelpayPhpApiGiropay();

        parent::getHeidelpayUrl($quote);

        /** Force PhpApi to just generate the request instead of sending it directly */
        $this->_heidelpayPaymentMethod->_dryRun = true;

        /** Set payment type to debit */
        $this->_heidelpayPaymentMethod->authorize();

        /** Prepare and send request to heidelpay */
        $request = $this->_heidelpayPaymentMethod->getRequest()->prepareRequest();
        $response = $this->_heidelpayPaymentMethod
            ->getRequest()
            ->send($this->_heidelpayPaymentMethod->getPaymentUrl(), $request);

        return $response[0];
    }
}
