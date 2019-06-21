<?php

namespace Heidelpay\Gateway\Model;

use Heidelpay\Gateway\Api\Data\TransactionInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Transaction resource model
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/magento2
 *
 * @author  Jens Richter
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
class Transaction extends AbstractModel implements TransactionInterface
{
    const ID = 'id';
    const PAYMENT_METHOD = 'payment_methode';
    const PAYMENT_TYPE = 'payment_type';
    const TRANSACTION_ID = 'transactionid';
    const QUOTE_ID = self::TRANSACTION_ID;
    const UNIQUE_ID = 'uniqeid';
    const SHORT_ID = 'shortid';
    const RESULT = 'result';
    const STATUS_CODE = 'statuscode';
    const RETURN_MSG = 'return';
    const RETURN_CODE = 'returncode';
    const JSON_RESPONSE = 'jsonresponse';
    const DATE_TIME = 'datatime';
    const SOURCE = 'source';

    /** @noinspection MagicMethodsValidityInspection */
    public function _construct()
    {
        $this->_init(ResourceModel\Transaction::class);
    }

    /**
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->getData(self::PAYMENT_METHOD);
    }

    /**
     * @param string $paymentMethod
     * @return $this
     */
    public function setPaymentMethod($paymentMethod)
    {
        $this->setData(self::PAYMENT_METHOD, $paymentMethod);
        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentType()
    {
        return $this->getData(self::PAYMENT_TYPE);
    }

    /**
     * @param string $paymentType
     * @return $this
     */
    public function setPaymentType($paymentType)
    {
        $this->setData(self::PAYMENT_TYPE, $paymentType);
        return $this;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->getData(self::TRANSACTION_ID);
    }

    /**
     * @param string $transactionId
     * @return $this
     */
    public function setTransactionId($transactionId)
    {
        $this->setData(self::TRANSACTION_ID, $transactionId);
        return $this;
    }

    /**
     * @return string
     */
    public function getUniqueId()
    {
        return $this->getData(self::UNIQUE_ID);
    }

    /**
     * @param string $uniqueId
     * @return $this
     */
    public function setUniqueId($uniqueId)
    {
        $this->setData(self::UNIQUE_ID, $uniqueId);
        return $this;
    }

    /**
     * @return string
     */
    public function getShortId()
    {
        return $this->getData(self::SHORT_ID);
    }

    /**
     * @param string $shortId
     * @return $this
     */
    public function setShortId($shortId)
    {
        $this->setData(self::SHORT_ID, $shortId);
        return $this;
    }

    /**
     * @return string
     */
    public function getResult()
    {
        return $this->getData(self::RESULT);
    }

    /**
     * @param string $result
     * @return $this
     */
    public function setResult($result)
    {
        $this->setData(self::RESULT, $result);
        return $this;
    }

    /**
     * @return integer
     */
    public function getStatusCode()
    {
        return $this->getData(self::STATUS_CODE);
    }

    /**
     * @param integer $statusCode
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        $this->setData(self::STATUS_CODE, $statusCode);
        return $this;
    }

    /**
     * @return string
     */
    public function getReturnMessage()
    {
        return $this->getData(self::RETURN_MSG);
    }

    /**
     * @param string $returnMessage
     * @return $this
     */
    public function setReturnMessage($returnMessage)
    {
        $this->setData(self::RETURN_MSG, $returnMessage);
        return $this;
    }

    /**
     * @return string
     */
    public function getReturnCode()
    {
        return $this->getData(self::RETURN_CODE);
    }

    /**
     * @param string $returnCode
     * @return $this
     */
    public function setReturnCode($returnCode)
    {
        $this->setData(self::RETURN_CODE, $returnCode);
        return $this;
    }

    /**
     * @return array
     */
    public function getJsonResponse()
    {
        return json_decode($this->getData(self::JSON_RESPONSE), true);
    }

    /**
     * @param string $jsonResponse
     * @return $this
     */
    public function setJsonResponse($jsonResponse)
    {
        $this->setData(self::JSON_RESPONSE, $jsonResponse);
        return $this;
    }

    /**
     * @return string
     */
    public function getDatetime()
    {
        return $this->getData(self::DATE_TIME);
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->getData(self::SOURCE);
    }

    /**
     * @param string $source
     * @return $this
     */
    public function setSource($source)
    {
        $this->setData(self::SOURCE, $source);
        return $this;
    }
}
