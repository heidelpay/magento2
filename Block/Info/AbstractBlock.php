<?php

namespace Heidelpay\Gateway\Block\Info;

use Heidelpay\Gateway\Model\ResourceModel\Transaction\CollectionFactory as HeidelpayTransactionCollectionFactory;
use Heidelpay\Gateway\Model\Transaction;
use Magento\Framework\View\Element\Template;

/**
 * Heidelpay Info Block Class
 *
 * Abstract class for Info Blocks
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.com/magento2
 *
 * @author Stephano Vogel
 *
 * @package heidelpay\magento2\block\info\abstractblock
 */
class AbstractBlock extends \Magento\Payment\Block\Info
{
    /**
     * @var HeidelpayTransactionCollectionFactory
     */
    protected $transactionCollectionFactory;

    /**
     * @var Transaction
     */
    protected $transactionInfo;

    /**
     * @var \Heidelpay\Gateway\Helper\Payment
     */
    protected $paymentHelper = null;

    /**
     * Heidelpay Info Block constructor.
     *
     * @param Template\Context $context
     * @param HeidelpayTransactionCollectionFactory $collectionFactory
     * @param \Heidelpay\Gateway\Helper\Payment $paymentHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        HeidelpayTransactionCollectionFactory $collectionFactory,
        \Heidelpay\Gateway\Helper\Payment $paymentHelper,
        array $data = []
    ) {
        $this->transactionCollectionFactory = $collectionFactory;
        $this->paymentHelper = $paymentHelper;

        parent::__construct($context, $data);
    }

    /**
     * Returns the Connector Account Holder Name.
     *
     * @return string
     */
    public function getAccountHolder()
    {
        if ($this->transactionInfo === null) {
            $this->loadTransactionInfo();
        }

        if (isset($this->transactionInfo->getJsonResponse()['CONNECTOR_ACCOUNT_HOLDER'])) {
            return $this->transactionInfo->getJsonResponse()['CONNECTOR_ACCOUNT_HOLDER'];
        }

        return '-';
    }

    /**
     * Returns the Connector Account IBAN.
     *
     * @return string
     */
    public function getAccountIban()
    {
        if ($this->transactionInfo === null) {
            $this->loadTransactionInfo();
        }

        if (isset($this->transactionInfo->getJsonResponse()['CONNECTOR_ACCOUNT_IBAN'])) {
            return $this->transactionInfo->getJsonResponse()['CONNECTOR_ACCOUNT_IBAN'];
        }

        return '-';
    }

    /**
     * Returns the Connector Account BIC.
     *
     * @return string
     */
    public function getAccountBic()
    {
        if ($this->transactionInfo === null) {
            $this->loadTransactionInfo();
        }

        if (isset($this->transactionInfo->getJsonResponse()['CONNECTOR_ACCOUNT_BIC'])) {
            return $this->transactionInfo->getJsonResponse()['CONNECTOR_ACCOUNT_BIC'];
        }

        return '-';
    }

    /**
     * Returns the Customer Birthdate (provided in this order).
     *
     * @return string
     */
    public function getCustomerBirthdate()
    {
        if (!$this->getIsSecureMode()) {
            if ($this->transactionInfo === null) {
                $this->loadTransactionInfo();
            }

            if (isset($this->transactionInfo->getJsonResponse()['NAME_BIRTHDATE'])) {
                return $this->transactionInfo->getJsonResponse()['NAME_BIRTHDATE'];
            }
        }

        return '-';
    }
    /**
     * Returns the Customer Salutation (provided in this order).
     *
     * @return string
     */
    public function getCustomerSalutation()
    {
        if (!$this->getIsSecureMode()) {
            if ($this->transactionInfo === null) {
                $this->loadTransactionInfo();
            }

            if (isset($this->transactionInfo->getJsonResponse()['NAME_SALUTATION'])) {
                return $this->transactionInfo->getJsonResponse()['NAME_SALUTATION'];
            }
        }

        return '-';
    }

    /**
     * @return mixed
     */
    public function printAdditionalInformationHtml()
    {
        if ($this->transactionInfo === null) {
            $this->loadTransactionInfo();
        }

        if (! empty($this->transactionInfo->getJsonResponse())) {
            return $this->getMethod()->additionalPaymentInformation($this->transactionInfo->getJsonResponse());
        }

        return '';
    }

    /**
     * Returns the Short ID for this order/transaction.
     *
     * @return string
     */
    public function getIdentificationNumber()
    {
        if ($this->transactionInfo === null) {
            $this->loadTransactionInfo();
        }

        if (isset($this->transactionInfo->getJsonResponse()['IDENTIFICATION_SHORTID'])) {
            return $this->transactionInfo->getJsonResponse()['IDENTIFICATION_SHORTID'];
        }

        return '-';
    }

    /**
     * Returns the Amount to be paid.
     *
     * @return string
     */
    public function getPresentationAmount()
    {
        if ($this->transactionInfo === null) {
            $this->loadTransactionInfo();
        }

        if (isset($this->transactionInfo->getJsonResponse()['PRESENTATION_AMOUNT'])) {
            return $this->paymentHelper->format($this->transactionInfo->getJsonResponse()['PRESENTATION_AMOUNT']);
        }

        return '-';
    }

    /**
     * Returns the Currency of the Amount to be paid.
     *
     * @return string
     */
    public function getPresentationCurrency()
    {
        if ($this->transactionInfo === null) {
            $this->loadTransactionInfo();
        }

        if (isset($this->transactionInfo->getJsonResponse()['PRESENTATION_CURRENCY'])) {
            return $this->transactionInfo->getJsonResponse()['PRESENTATION_CURRENCY'];
        }

        return '-';
    }

    /**
     * Returns Transaction information, if a transaction ID is set.
     *
     * @return Transaction|null
     */
    public function getTransactionInfo()
    {
        if ($this->transactionInfo === null) {
            $this->loadTransactionInfo();
        }

        return $this->transactionInfo;
    }

    /**
     * Loads heidelpay transaction details by the last_trans_id of this order.
     */
    private function loadTransactionInfo()
    {
        if ($this->getInfo()->getLastTransId() !== null) {
            $factory = $this->transactionCollectionFactory->create();
            $this->transactionInfo = $factory->loadByTransactionId($this->getInfo()->getLastTransId());
        }
    }
}
