<?php
/**
 * Mage Plugins, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mage Plugins Commercial License (MPCL 1.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://mageplugins.net/commercial-license/
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to mageplugins@gmail.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade to newer
 * versions in the future. If you wish to customize the extension for your
 * needs please refer to http://www.mageplugins.net for more information.
 *
 * @category   MP
 * @package    MP_MessageQueue
 * @copyright  Copyright (c) 2006-2019 Mage Plugins, Inc. and affiliates (https://mageplugins.net/)
 * @license    https://mageplugins.net/commercial-license/ Mage Plugins Commercial License (MPCL 1.0)
 */

namespace MP\MessageQueue\Setup;

/**
 * Class Recurring
 *
 * @package MP\MessageQueue\Setup
 */
class Recurring implements \Magento\Framework\Setup\InstallSchemaInterface
{
    /**
     * @var \Magento\Framework\MessageQueue\ConfigInterface
     */
    private $messageQueueConfig;

    /**
     * Recurring constructor
     *
     * @param \Magento\Framework\MessageQueue\ConfigInterface $messageQueueConfig
     */
    public function __construct(
        \Magento\Framework\MessageQueue\ConfigInterface $messageQueueConfig
    ) {
        $this->messageQueueConfig = $messageQueueConfig;
    }

    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     */
    public function install(
        \Magento\Framework\Setup\SchemaSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context
    ) {
        $setup->startSetup();

        $binds  = $this->messageQueueConfig->getBinds();
        $queues = [];

        foreach ($binds as $bind) {
            $queues[] = $bind[\Magento\Framework\MessageQueue\ConfigInterface::BIND_QUEUE];
        }

        $connection     = $setup->getConnection();
        $queueTableName = $setup->getTable(\MP\MessageQueue\Api\Data\QueueInterface::ENTITY);

        $existingQueues = $connection->fetchCol(
            $connection->select()->from($queueTableName, 'name')
        );

        $queues = array_unique(array_diff($queues, $existingQueues));

        /**
         * Populate 'queue' table
         */
        if (!empty($queues)) {
            $connection->insertArray($queueTableName, ['name'], $queues);
        }

        $setup->endSetup();
    }
}
