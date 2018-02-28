<?php

namespace Heidelpay\Gateway\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Installation method
 *
 * This method will create two table in your magento database
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/magento
 * @author Jens Richter
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $installer, ModuleContextInterface $context)
    {
        $installer->startSetup();

        // create transactions table
        $tablerealname = 'heidelpay_transaction';
        $tablename = $installer->getTable($tablerealname);
        if (!$installer->getConnection()->isTableExists($tablename)) {
            $table = $installer->getConnection()->newTable($tablename)
                ->addColumn('id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, [
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true,
                    'identity' => true,
                    'auto_increment' => true
                ])
                ->addColumn('payment_methode', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 2, [
                    'nullable' => false
                ])
                ->addColumn('payment_type', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 2, [
                    'nullable' => false
                ])
                ->addColumn('transactionid', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 50, [
                    'nullable' => false,
                    'COMMENT' => "normaly the order or basketId"
                ])
                ->addColumn('uniqeid', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 32, [
                    'nullable' => false,
                    'COMMENT' => "heidelpay uniqe identification number"
                ])
                ->addColumn('shortid', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 14, [
                    'nullable' => false,
                    'COMMENT' => "heidelpay sort identification number"
                ])
                ->addColumn('result', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 3, [
                    'nullable' => false,
                    'COMMENT' => "heidelpay processing result"
                ])
                ->addColumn('statuscode', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, [
                    'unsigned' => true,
                    'nullable' => false,
                    'COMMENT' => "heidelpay processing status code"
                ])
                ->addColumn('return', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 100, [
                    'nullable' => false,
                    'COMMENT' => "heidelpay processing return message"
                ])
                ->addColumn('returncode', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 12, [
                    'nullable' => false,
                    'COMMENT' => "heidelpay processing return code"
                ])
                ->addColumn('jsonresponse', \Magento\Framework\DB\Ddl\Table::TYPE_BLOB, null, [
                    'nullable' => false,
                    'COMMENT' => "heidelpay response as json"
                ])
                ->addColumn('datetime', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, [
                    'nullable' => false,
                    'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE,
                    'COMMENT' => "create date"
                ])
                ->addColumn('source', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 100, [
                    'nullable' => false,
                    'COMMENT' => "heidelpay processing return message"
                ])->addIndex($installer->getIdxName($tablerealname, [
                    'uniqeid'
                ]), [
                    'uniqeid'
                ])->addIndex($installer->getIdxName($tablerealname, [
                    'transactionid'
                ]), [
                    'transactionid'
                ])->addIndex($installer->getIdxName($tablerealname, [
                    'returncode'
                ]), [
                    'returncode'
                ])->addIndex($installer->getIdxName($tablerealname, [
                    'source'
                ]), [
                    'source'
                ]);
            $installer->getConnection()->createTable($table);
        }

        // additional payment information table
        $tablerealname = 'heidelpay_payment_information';
        $tablename = $installer->getTable($tablerealname);

        if (!$installer->getConnection()->isTableExists($tablename)) {
            $table = $installer->getConnection()->newTable($tablename)
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
                    $installer->getIdxName($tablerealname, ['storeid']),
                    ['storeid']
                )
                ->addIndex(
                    $installer->getIdxName($tablerealname, ['customer_email']),
                    ['customer_email']
                )
                ->addIndex(
                    $installer->getIdxName($tablerealname, ['paymentmethod']),
                    ['paymentmethod']
                )
                ->addIndex(
                    $installer->getIdxName($tablerealname, ['heidelpay_payment_reference']),
                    ['heidelpay_payment_reference']
                );

            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}
