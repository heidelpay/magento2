<?php
/**
 * Created by PhpStorm.
 * User: David.Owusu
 * Date: 12.09.2018
 * Time: 11:01
 */

namespace Heidelpay\Gateway\Model\Config;

use Heidelpay\Gateway\Gateway\Config\HgwIDealPaymentConfigInterface;
use Heidelpay\Gateway\PaymentMethods\HeidelpayIDealPaymentMethod;
use Magento\Checkout\Model\ConfigProviderInterface;

class IDealConfigProvider implements ConfigProviderInterface
{
    private $config;

    /**
     * PayPalConfigProvider constructor.
     * @param HgwEasycreditPaymentConfigInterface $config
     */
    public function __construct(
        HgwIDealPaymentConfigInterface $config
    )
    {
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfig()
    {
        //TODO david: ConfigProvider is not yet available in frontend.
        $config = [
            'payment' => [
                HeidelpayIDealPaymentMethod::CODE => [
                    'needs_external_info_in_checkout' => $this->config->getNeedsExternalInfoInCheckout()
                ],
            ]
        ];

        return $config;
    }
}