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

namespace MP\MessageQueue\Console;

/**
 * Class AbstractCommand
 *
 * @package MP\MessageQueue\Console
 */
class AbstractCommand extends \Symfony\Component\Console\Command\Command
{
    /**
     * @var \Symfony\Component\Console\Helper\ProgressBar
     */
    private $progressBar;

    /**
     * @var int
     */
    private $startTime;

    /**
     * @var \Magento\Framework\MessageQueue\Consumer\ConfigInterface
     */
    private $consumerConfig;

    /**
     * @var \Magento\Framework\MessageQueue\ConnectionTypeResolver
     */
    private $connectionTypeResolver;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    public function __construct(
        \Magento\Framework\MessageQueue\Consumer\ConfigInterface $consumerConfig,
        \Magento\Framework\MessageQueue\ConnectionTypeResolver $connectionTypeResolver,
        \Magento\Framework\App\ResourceConnection $resource,
        string $name = null
    ) {
        parent::__construct($name);

        $this->consumerConfig         = $consumerConfig;
        $this->connectionTypeResolver = $connectionTypeResolver;
        $this->resource               = $resource;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return true;
    }

    /**
     * @return \Magento\Framework\MessageQueue\Consumer\ConfigInterface
     */
    protected function getConsumerConfig()
    {
        return $this->consumerConfig;
    }

    /**
     * @return \Magento\Framework\MessageQueue\ConnectionTypeResolver
     */
    protected function getConnectionTypeResolver()
    {
        return $this->connectionTypeResolver;
    }

    /**
     * @return \Magento\Framework\App\ResourceConnection
     */
    protected function getResource()
    {
        return $this->resource;
    }

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected function getConnection()
    {
        return $this->resource->getConnection();
    }

    /**
     * @param string $modelEntity
     * @param null|string $connectionType
     * @return string
     */
    protected function getTableName($modelEntity, $connectionType = null)
    {
        switch ($connectionType) {
            case 'mpdb':
                $prefix = 'mpdb_';
                break;
            case 'db':
            default:
                $prefix = '';
        }

        return $this->resource->getTableName($prefix . $modelEntity);
    }

    /**
     * @param string $consumerName
     * @param int $messageId
     * @return string
     */
    protected function getMessageCode($consumerName, $messageId)
    {
        $code = $consumerName . '-' . $messageId;
        $code = md5($code);

        return $code;
    }

    /**
     * @return string
     */
    protected function getQueueLockTable()
    {
        return $this->getTableName('queue_lock');
    }

    /**
     * @param null|string $connectionType
     * @return string
     */
    protected function getQueueTable($connectionType = null)
    {
        return $this->getTableName('queue', $connectionType);
    }

    /**
     * @param null|string $connectionType
     * @return string
     */
    protected function getQueueMessageTable($connectionType = null)
    {
        return $this->getTableName('queue_message', $connectionType);
    }

    /**
     * @param null|string $connectionType
     * @return string
     */
    protected function getQueueMessageStatusTable($connectionType = null)
    {
        return $this->getTableName('queue_message_status', $connectionType);
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string|array $messages
     * @param int $options
     * @return void
     */
    protected function writeln($output, $messages, $options = 0)
    {
        if ($messages instanceof \Magento\Framework\Phrase) {
            $messages = (string) $messages;
        }

        $output->writeln($messages, $options);
    }

    /**
     * @return void
     */
    protected function startTime()
    {
        $this->startTime = microtime(true);
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    protected function stopTime($output)
    {
        $this->writeln($output, __('Total runtime: %1 sec.', microtime(true) - $this->startTime));
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param int $count
     */
    protected function startProgress($output, $count)
    {
        $this->progressBar = new \Symfony\Component\Console\Helper\ProgressBar($output, $count);
    }

    /**
     * @param int $step
     * @return void
     */
    protected function advanceProgress($step = 1)
    {
        $this->progressBar->advance($step);
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    protected function stopProgress($output)
    {
        $this->progressBar->finish();
        $this->writeln($output, '');
    }
}
