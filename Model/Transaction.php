<?php

namespace Heidelpay\Gateway\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data;

/**
 * Transaction resource model
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 * @link  https://dev.heidelpay.de/magento
 * @author  Jens Richter
 *
 * @package  Heidelpay
 * @subpackage Magento2
 * @category Magento2
 */
class Transaction extends AbstractModel
{
    /**
     * @var Data
     */
    protected $paymentData;

    /**
     * Transaction constructor.
     * @param Context $context
     * @param Registry $registry
     * @param Data $paymentData
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Data $paymentData,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->paymentData = $paymentData;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @return void
     */
    public function _construct()
    {
        $this->_init('Heidelpay\Gateway\Model\ResourceModel\Transaction');
    }

    /**
     * @param $modelId
     * @param null $field
     * @return $this
     */
    public function loadLastTransactionByQuoteId($modelId, $field = null)
    {
        $this->_beforeLoad($modelId, $field);
        $abstractDb = $this->_getResource();
        $abstractDb->loadLastTransactionByQuoteId($this, $modelId, $field);
        $this->_afterLoad();
        $this->setOrigData();
        $this->_hasDataChanges = false;

        return $this;
    }
}