<?php
/**
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2019-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.de>
 *
 * @package  heidelpay/magento2
 */
namespace Heidelpay\Gateway\Api\Data;

interface TransactionInterface
{
    /**
     * @return string
     */
    public function getPaymentMethod();

    /**
     * @param string $paymentMethod
     * @return $this
     */
    public function setPaymentMethod($paymentMethod);

    /**
     * @return string
     */
    public function getPaymentType();

    /**
     * @param string $paymentType
     * @return $this
     */
    public function setPaymentType($paymentType);

    /**
     * @return string
     */
    public function getTransactionId();

    /**
     * @param string $transactionId
     * @return $this
     */
    public function setTransactionId($transactionId);

    /**
     * @return string
     */
    public function getUniqueId();

    /**
     * @param string $uniqueId
     * @return $this
     */
    public function setUniqueId($uniqueId);

    /**
     * @return string
     */
    public function getShortId();

    /**
     * @param string $shortId
     * @return $this
     */
    public function setShortId($shortId);

    /**
     * @return string
     */
    public function getResult();

    /**
     * @param string $result
     * @return $this
     */
    public function setResult($result);

    /**
     * @return integer
     */
    public function getStatusCode();

    /**
     * @param integer $statusCode
     * @return $this
     */
    public function setStatusCode($statusCode);

    /**
     * @return string
     */
    public function getReturnMessage();

    /**
     * @param string $returnMessage
     * @return $this
     */
    public function setReturnMessage($returnMessage);

    /**
     * @return string
     */
    public function getReturnCode();

    /**
     * @param string $returnCode
     * @return $this
     */
    public function setReturnCode($returnCode);

    /**
     * @return array
     */
    public function getJsonResponse();

    /**
     * @param string $jsonResponse
     * @return $this
     */
    public function setJsonResponse($jsonResponse);

    /**
     * @return string
     */
    public function getDatetime();

    /**
     * @return string
     */
    public function getSource();

    /**
     * @param string $source
     * @return $this
     */
    public function setSource($source);
}
