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

namespace MP\MessageQueue\Console\Command;

/**
 * Class Clean
 *
 * @package MP\MessageQueue\Console\Command
 */
class Clean extends \MP\MessageQueue\Console\AbstractCommand
{
    /**
     * @const string
     */
    const COMMAND            = 'mpdb:consumer:clean';
    const ARGUMENT_CONSUMER  = 'consumer';

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName(self::COMMAND);
        $this->setDescription('Clean MessageQueue consumer');

        $this->addArgument(
            self::ARGUMENT_CONSUMER,
            \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED,
            'The name of the consumer to be cleared.'
        );

        $this->setHelp(
            <<<HELP
This command clean MessageQueue consumer by its name.

To start consumer which will process all queued messages and terminate execution:

    <comment>%command.full_name% someConsumer</comment>
HELP
        );

        parent::configure();
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \LogicException
     */
    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    ) {
        $consumerName = $input->getArgument(self::ARGUMENT_CONSUMER);

        /** @var \Magento\Framework\MessageQueue\Consumer\Config\ConsumerConfigItemInterface $consumerConfigItem */
        $consumerConfigItem = $this->getConsumerConfig()->getConsumer($consumerName);

        $connectionName = $consumerConfigItem->getConnection();
        $connectionType = $this->getConnectionTypeResolver()->getConnectionType($connectionName);

        $this->setConnectionType($connectionType);

        $select = $this->getConnection()->select()
            ->from(
                ['qm' => $this->getQueueMessageTable()],
                ['id']
            )
            ->joinInner(
                ['qms' => $this->getQueueMessageStatusTable()],
                implode(' AND ', ['qms.message_id = qm.id']),
                ['status_id' => 'id']
            )
            ->where(
                'qms.status NOT IN (?)',
                [
                    \MP\MessageQueue\Model\QueueManagement::MESSAGE_STATUS_IN_PROGRESS
                ]
            );

        $messages = $this->getConnection()->fetchAll($select);

        foreach ($messages as $key => $message) {
            $messages[$key]['code'] = $this->getMessageCode($consumerName, $message['id']);
        }

        if (empty($messages)) {
            $this->writeln($output, __('We have no messages to clear.'));
        }

        $this->deleteFromQueueLockTable($messages);
        $this->deleteFromQueueMessageStatusTable($messages);
        $this->deleteFromQueueMessageTable($messages);

        $this->writeln($output, __('All messages have been cleared.'));

        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }

    /**
     * @param array $messages
     * @return void
     */
    protected function deleteFromQueueLockTable($messages)
    {
        $this->getConnection()
            ->delete($this->getQueueLockTable(), ['message_code IN (?)' => array_column($messages, 'code')]);
    }

    /**
     * @param array $messages
     * @return void
     */
    protected function deleteFromQueueMessageStatusTable($messages)
    {
        $this->getConnection()
            ->delete($this->getQueueMessageStatusTable(), ['id IN (?)' => array_column($messages, 'status_id')]);
    }

    /**
     * @param array $messages
     * @return void
     */
    protected function deleteFromQueueMessageTable($messages)
    {
        $this->getConnection()
            ->delete($this->getQueueMessageTable(), ['id IN (?)' => array_column($messages, 'id')]);
    }
}
