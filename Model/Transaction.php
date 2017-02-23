<?php
namespace Heidelpay\Gateway\Model;

/**
 * Transaction resource model
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link  https://dev.heidelpay.de/magento
 *
 * @author  Jens Richter
 *
 * @package  Heidelpay
 * @subpackage Magento2
 * @category Magento2
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
        $this->_paymentData = $paymentData;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    public function _construct()
    {
        $this->_init('Heidelpay\Gateway\Model\ResourceModel\Transaction');
    }
    
    public function loadLastTransactionByQuoteId($modelId, $field = null)
    {
        $this->_beforeLoad($modelId, $field);
        $this->_getResource()->loadLastTransactionByQuoteId($this, $modelId, $field);
        $this->_afterLoad();
        $this->setOrigData();
        $this->_hasDataChanges = false;
        //$this->updateStoredData();
        return $this;
    }
}
