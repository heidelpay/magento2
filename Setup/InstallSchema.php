<?php
/**
 * Installation method
 *
 * This method will create two table in your magento database
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
namespace Heidelpay\Gateway\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $installer, ModuleContextInterface $context)
    {
        $installer->startSetup();
        
        /**
         * create transactions table
         */
        $tablerealname = 'heidelpay_transaction';
        $tablename = $installer->getTable($tablerealname);
        if ($installer->getConnection()->isTableExists($tablename) != true) {
            $table = $installer->getConnection()->newTable($tablename)
            ->addColumn('id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true,
                    'identity' => true,
                    'auto_increment' => true
            ))
            ->addColumn('payment_methode', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 2, array(
                    'nullable' => false
            ))
            ->addColumn('payment_type', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 2, array(
                    'nullable' => false
            ))
            ->addColumn('transactionid', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 50, array(
                    'nullable' => false,
                    'COMMENT' => "normaly the order or basketId"
            ))
            ->addColumn('uniqeid', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 32, array(
                    'nullable' => false,
                    'COMMENT' => "heidelpay uniqe identification number"
            ))
            ->addColumn('shortid', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 14, array(
                    'nullable' => false,
                    'COMMENT' => "heidelpay sort identification number"
            ))
            ->addColumn('result', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 3, array(
                    'nullable' => false,
                    'COMMENT' => "heidelpay processing result"
            ))
            ->addColumn('statuscode', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, array(
                    'unsigned' => true,
                    'nullable' => false,
                    'COMMENT' => "heidelpay processing status code"
            ))
            ->addColumn('return', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 100, array(
                    'nullable' => false,
                    'COMMENT' => "heidelpay processing return message"
            ))
            ->addColumn('returncode', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 12, array(
                    'nullable' => false,
                    'COMMENT' => "heidelpay processing return code"
            ))
            ->addColumn('jsonresponse', \Magento\Framework\DB\Ddl\Table::TYPE_BLOB, null, array(
                    'nullable' => false,
                    'COMMENT' => "heidelpay response as json"
            ))
            ->addColumn('datetime', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, array(
                    'nullable' => false,
                    'default'  => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE,
                    'COMMENT' => "create date"
            ))
            ->addColumn('source', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 100, array(
                    'nullable' => false,
                    'COMMENT' => "heidelpay processing return message"
            ))->addIndex($installer->getIdxName($tablerealname, array(
                    'uniqeid'
            )), array(
                    'uniqeid'
            ))->addIndex($installer->getIdxName($tablerealname, array(
                    'transactionid'
            )), array(
                    'transactionid'
            ))->addIndex($installer->getIdxName($tablerealname, array(
                    'returncode'
            )), array(
                    'returncode'
            ))->addIndex($installer->getIdxName($tablerealname, array(
                    'source'
            )), array(
                    'source'
            ));
            $installer->getConnection()->createTable($table);
        }
        
        /**
         * create customer data table
         */
        $tablerealname = 'heidelpay_customer';
        $tablename = $installer->getTable($tablerealname);
        if ($installer->getConnection()->isTableExists($tablename) != true) {
            $table = $installer->getConnection()->newTable($tablename)
            ->addColumn('id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true,
                    'identity' => true,
                    'auto_increment' => true
            ))
            ->addColumn('paymentmethode', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 10, array(
                    'nullable' => false
            ))
            ->addColumn('uniqeid', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 50, array(
                    'nullable' => false,
                    'COMMENT' => "Heidelpay transaction identifier"
            ))
            ->addColumn('customerid', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
                    'unsigned' => true,
                    'nullable' => false,
                    'COMMENT' => "magento customer id"
            ))
            ->addColumn('storeid', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, array(
                    'unsigned' => true,
                    'nullable' => false,
                    'COMMENT' => "magento store id"
            ))
            ->addColumn('payment_data', \Magento\Framework\DB\Ddl\Table::TYPE_BLOB, null, array(
                    'nullable' => false,
                    'COMMENT' => "custumer payment data"
            ))
            ->addIndex($installer->getIdxName($tablerealname, array(
                    'uniqeid'
            )), array(
                    'uniqeid'
            ))
            ->addIndex($installer->getIdxName($tablerealname, array(
                    'customerid'
            )), array(
                    'customerid'
            ))
            ->addIndex($installer->getIdxName($tablerealname, array(
                    'storeid'
            )), array(
                    'storeid'
            ))
            ->addIndex($installer->getIdxName($tablerealname, array(
                    'paymentmethode'
            )), array(
                    'paymentmethode'
            ));
            $installer->getConnection()->createTable($table);
        }
        
        $installer->endSetup();
    }
}
