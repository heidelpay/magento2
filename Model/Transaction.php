<?php

namespace Heidelpay\Gateway\Model;

/**
 * Transaction resource model
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  https://dev.heidelpay.de/magento
 *
 * @author  Jens Richter
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
class Transaction extends \Magento\Framework\Model\AbstractModel
{
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    public function _construct()
    {
        $this->_init('Heidelpay\Gateway\Model\ResourceModel\Transaction');
    }

    /**
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->getData('payment_methode');
    }

    /**
     * @param string $paymentMethod
     * @return $this
     */
    public function setPaymentMethod($paymentMethod)
    {
        $this->setData('payment_methode', $paymentMethod);
        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentType()
    {
        return $this->getData('payment_type');
    }

    /**
     * @param string $paymentType
     * @return $this
     */
    public function setPaymentType($paymentType)
    {
        $this->setData('payment_type', $paymentType);
        return $this;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->getData('transactionid');
    }

    /**
     * @param string $transactionId
     * @return $this
     */
    public function setTransactionId($transactionId)
    {
        $this->setData('transactionid', $transactionId);
        return $this;
    }

    /**
     * @return string
     */
    public function getUniqueId()
    {
        return $this->getData('uniqeid');
    }

    /**
     * @param string $uniqueId
     * @return $this
     */
    public function setUniqueId($uniqueId)
    {
        $this->setData('uniqeid', $uniqueId);
        return $this;
    }

    /**
     * @return string
     */
    public function getShortId()
    {
        return $this->getData('shortid');
    }

    /**
     * @param string $shortId
     * @return $this
     */
    public function setShortId($shortId)
    {
        $this->setData('shortid', $shortId);
        return $this;
    }

    /**
     * @return string
     */
    public function getResult()
    {
        return $this->getData('result');
    }

    /**
     * @param string $result
     * @return $this
     */
    public function setResult($result)
    {
        $this->setData('result', $result);
        return $this;
    }

    /**
     * @return integer
     */
    public function getStatusCode()
    {
        return $this->getData('statuscode');
    }

    /**
     * @param integer $statusCode
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        $this->setData('statuscode', $statusCode);
        return $this;
    }

    /**
     * @return string
     */
    public function getReturnMessage()
    {
        return $this->getData('return');
    }

    /**
     * @param string $returnMessage
     * @return $this
     */
    public function setReturnMessage($returnMessage)
    {
        $this->setData('return', $returnMessage);
        return $this;
    }

    /**
     * @return string
     */
    public function getReturnCode()
    {
        return $this->getData('returncode');
    }

    /**
     * @param string $returnCode
     * @return $this
     */
    public function setReturnCode($returnCode)
    {
        $this->setData('returncode', $returnCode);
        return $this;
    }

    /**
     * @return array
     */
    public function getJsonResponse()
    {
        return json_decode($this->getData('jsonresponse'), true);
    }

    /**
     * @param string $jsonResponse
     * @return $this
     */
    public function setJsonResponse($jsonResponse)
    {
        $this->setData('jsonresponse', $jsonResponse);
        return $this;
    }

    /**
     * @return string
     */
    public function getDatetime()
    {
        return $this->getData('datatime');
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->getData('source');
    }

    /**
     * @param string $source
     * @return $this
     */
    public function setSource($source)
    {
        $this->setData('source', $source);
        return $this;
    }
}
