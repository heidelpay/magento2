<?php
namespace Heidelpay\Gateway\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

/**
 * Heidelpay_Gateway Upgrade Schema
 *
 * This class provides upgrade schema for table additions and/or changes.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 * @link  https://dev.heidelpay.de/magento
 * @author Stephano Vogel
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $upgrade, ModuleContextInterface $module)
    {
        $upgrade->startSetup();
        
        // Version 17.3.10 Upgrade
        if (version_compare($module->getVersion(), '17.3.10') < 0) {
            $this->run170310Upgrade($upgrade);
        }

        $upgrade->endSetup();
    }

    /**
     * Performs the upgrade schema for the 17.3.1 update.
     * Changelog:
     *  - additional payment information table
     *
     * @param SchemaSetupInterface $upgrade
     */
    private function run170310Upgrade(SchemaSetupInterface $upgrade)
    {
        $tablerealname = 'heidelpay_payment_information';
        $tablename = $upgrade->getTable($tablerealname);

        if ($upgrade->getConnection()->isTableExists($tablename) != true) {
            $table = $upgrade->getConnection()->newTable($tablename)
                ->addColumn('id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, [
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true,
                    'identity' => true,
                    'auto_increment' => true
                ])
                ->addColumn('storeid', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, [
                    'unsigned' => true,
                    'nullable' => false,
                ])
                ->addColumn('customer_email', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 128, [
                    'nullable' => false,
                ])
                ->addColumn('paymentmethod', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 10, [
                    'nullable' => false
                ])
                ->addColumn('shipping_hash', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, [
                    'nullable' => false,
                ])
                ->addColumn('additional_data', \Magento\Framework\DB\Ddl\Table::TYPE_BLOB, null, [
                    'nullable' => false,
                ])
                ->addColumn('heidelpay_payment_reference', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 32, [
                    'nullable' => true,
                    'COMMENT' => "heidelpay transaction identifier"
                ])
                ->addColumn('create_date', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, [
                    'nullable' => false,
                    'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE,
                ])
                ->addIndex(
                    $upgrade->getIdxName($tablerealname, ['storeid']),
                    ['storeid']
                )
                ->addIndex(
                    $upgrade->getIdxName($tablerealname, ['customer_email']),
                    ['customer_email']
                )
                ->addIndex(
                    $upgrade->getIdxName($tablerealname, ['paymentmethod']),
                    ['paymentmethod']
                )
                ->addIndex(
                    $upgrade->getIdxName($tablerealname, ['heidelpay_payment_reference']),
                    ['heidelpay_payment_reference']
                );

            $upgrade->getConnection()->createTable($table);
        }
    }
}
