<?php
/**
 * Installation method
 *
 * This method will create two table in your magento database
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
namespace Heidelpay\Gateway\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $installer
     * @param ModuleContextInterface $context
     */
    public function install(SchemaSetupInterface $installer, ModuleContextInterface $context)
    {

        $installer->startSetup();

        /**
         * create transactions table
         */
        $tableRealName = 'heidelpay_transaction';
        $tableName = $installer->getTable($tableRealName);
        if ($installer->getConnection()->isTableExists($tableName) != true) {

            $table = $installer->getConnection()->newTable($tableName)
                               ->addColumn('id', Table::TYPE_INTEGER, null, [
                                   'unsigned'       => true,
                                   'nullable'       => false,
                                   'primary'        => true,
                                   'identity'       => true,
                                   'auto_increment' => true,
                               ])
                               ->addColumn('payment_methode', Table::TYPE_TEXT, 2, [
                                   'nullable' => false,
                               ])
                               ->addColumn('payment_type', Table::TYPE_TEXT, 2, [
                                   'nullable' => false,
                               ])
                               ->addColumn('transactionid', Table::TYPE_TEXT, 50, [
                                   'nullable' => false,
                                   'COMMENT'  => "normaly the order or basketId",
                               ])
                               ->addColumn('uniqeid', Table::TYPE_TEXT, 32, [
                                   'nullable' => false,
                                   'COMMENT'  => "heidelpay uniqe identification number",
                               ])
                               ->addColumn('shortid', Table::TYPE_TEXT, 14, [
                                   'nullable' => false,
                                   'COMMENT'  => "heidelpay sort identification number",
                               ])
                               ->addColumn('result', Table::TYPE_TEXT, 3, [
                                   'nullable' => false,
                                   'COMMENT'  => "heidelpay processing result",
                               ])
                               ->addColumn('statuscode', Table::TYPE_SMALLINT, null, [
                                   'unsigned' => true,
                                   'nullable' => false,
                                   'COMMENT'  => "heidelpay processing status code",
                               ])
                               ->addColumn('return', Table::TYPE_TEXT, 100, [
                                   'nullable' => false,
                                   'COMMENT'  => "heidelpay processing return message",
                               ])
                               ->addColumn('returncode', Table::TYPE_TEXT, 12, [
                                   'nullable' => false,
                                   'COMMENT'  => "heidelpay processing return code",
                               ])
                               ->addColumn('jsonresponse', Table::TYPE_BLOB, null, [
                                   'nullable' => false,
                                   'COMMENT'  => "heidelpay response as json",
                               ])
                               ->addColumn('datetime', Table::TYPE_TIMESTAMP, null, [
                                   'nullable' => false,
                                   'default'  => Table::TIMESTAMP_INIT_UPDATE,
                                   'COMMENT'  => "create date",
                               ])
                               ->addColumn('source', Table::TYPE_TEXT, 100, [
                                   'nullable' => false,
                                   'COMMENT'  => "heidelpay processing return message",
                               ])->addIndex($installer->getIdxName($tableRealName, [
                    'uniqeid',
                ]), [
                    'uniqeid',
                ])->addIndex($installer->getIdxName($tableRealName, [
                    'transactionid',
                ]), [
                    'transactionid',
                ])->addIndex($installer->getIdxName($tableRealName, [
                    'returncode',
                ]), [
                    'returncode',
                ])->addIndex($installer->getIdxName($tableRealName, [
                    'source',
                ]), [
                    'source',
                ]);
            $installer->getConnection()->createTable($table);
        }

        /**
         * create customer data table
         */
        $tableRealName = 'heidelpay_customer';
        $tableName = $installer->getTable($tableRealName);
        if ($installer->getConnection()->isTableExists($tableName) != true) {

            $table = $installer->getConnection()->newTable($tableName)
                               ->addColumn('id', Table::TYPE_INTEGER, null, [
                                   'unsigned'       => true,
                                   'nullable'       => false,
                                   'primary'        => true,
                                   'identity'       => true,
                                   'auto_increment' => true,
                               ])
                               ->addColumn('paymentmethode', Table::TYPE_TEXT, 10, [
                                   'nullable' => false,
                               ])
                               ->addColumn('uniqeid', Table::TYPE_TEXT, 50, [
                                   'nullable' => false,
                                   'COMMENT'  => "Heidelpay transaction identifier",
                               ])
                               ->addColumn('customerid', Table::TYPE_INTEGER, null, [
                                   'unsigned' => true,
                                   'nullable' => false,
                                   'COMMENT'  => "magento customer id",
                               ])
                               ->addColumn('storeid', Table::TYPE_INTEGER, null, [
                                   'unsigned' => true,
                                   'nullable' => false,
                                   'COMMENT'  => "magento store id",
                               ])
                               ->addColumn('payment_data', Table::TYPE_BLOB, null, [
                                   'nullable' => false,
                                   'COMMENT'  => "custumer payment data",
                               ])
                               ->addIndex($installer->getIdxName($tableRealName, [
                                   'uniqeid',
                               ]), [
                                   'uniqeid',
                               ])
                               ->addIndex($installer->getIdxName($tableRealName, [
                                   'customerid',
                               ]), [
                                   'customerid',
                               ])
                               ->addIndex($installer->getIdxName($tableRealName, [
                                   'storeid',
                               ]), [
                                   'storeid',
                               ])
                               ->addIndex($installer->getIdxName($tableRealName, [
                                   'paymentmethode',
                               ]), [
                                   'paymentmethode',
                               ]);
            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}
