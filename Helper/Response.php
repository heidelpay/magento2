<?php
namespace Heidelpay\Gateway\Helper;

use Heidelpay\MessageCodeMapper\Exceptions\MissingLocaleFileException;
use Heidelpay\MessageCodeMapper\MessageCodeMapper;
use Heidelpay\PhpPaymentApi\Exceptions\HashVerificationException;
use Heidelpay\PhpPaymentApi\Response as HeidelpayResponse;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Encryption\Encryptor;

/**
 * Heidelpay response helper
 *
 * The response helper is a collection of function to prepare an send
 *
 * @license    Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright  Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link       http://dev.heidelpay.com/magento2
 *
 * @author     David Owusu
 *
 * @package    heidelpay
 * @subpackage Magento2
 * @category   Magento2
 */
class Response extends AbstractHelper
{
    private $_encryptor;

    /**
     * @param Context $context
     * @param Encryptor $encryptor
     */
    public function __construct(Context $context, Encryptor $encryptor)
    {
        parent::__construct($context);
        $this->_encryptor = $encryptor;
    }

    /**
     * Validate Hash to prevent manipulation
     * @param HeidelpayResponse $response
     * @param $remoteAddress
     * @return bool
     */
    public function validateSecurityHash($response, $remoteAddress)
    {
        $secret = $this->_encryptor->exportKeys();
        $identificationTransactionId = $response->getIdentification()->getTransactionId();

        $this->_logger->debug('Heidelpay secret: ' . $secret);
        $this->_logger->debug('Heidelpay identificationTransactionId: ' . $identificationTransactionId);

        try {
            $response->verifySecurityHash($secret, $identificationTransactionId);
            return true;
        } catch (HashVerificationException $e) {
            $this->_logger->critical('Heidelpay Response - HashVerification Exception: ' . $e->getMessage());
            $this->_logger->critical(
                'Heidelpay Response - Received request form server ' . $remoteAddress
                . ' with an invalid hash. This could be some kind of manipulation.'
            );
            $this->_logger->critical(
                'Heidelpay Response - Reference secret hash: ' . $response->getCriterion()->getSecretHash()
            );
            return false;
        }
    }
}
