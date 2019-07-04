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
 * Class Start
 *
 * @package MP\MessageQueue\Console\Command
 */
class Start extends \MP\MessageQueue\Console\AbstractCommand
{
    /**
     * @const string
     */
    const COMMAND = 'mpdb:consumer:start';

    /**
     * @const string
     */
    const ARGUMENT_CONSUMER = 'consumer';

    /**
     * @var \Magento\Framework\App\DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @var \MP\MessageQueue\Helper\Data
     */
    private $helper;

    /**
     * Start constructor
     *
     * @param \Magento\Framework\MessageQueue\Consumer\ConfigInterface $consumerConfig
     * @param \Magento\Framework\MessageQueue\ConnectionTypeResolver $connectionTypeResolver
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Framework\Shell $shellBackground
     * @param \Symfony\Component\Process\PhpExecutableFinder $phpExecutableFinder
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     * @param \MP\MessageQueue\Helper\Data $helper
     * @param string|null $name
     */
    public function __construct(
        \Magento\Framework\MessageQueue\Consumer\ConfigInterface $consumerConfig,
        \Magento\Framework\MessageQueue\ConnectionTypeResolver $connectionTypeResolver,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Shell $shellBackground,
        \Symfony\Component\Process\PhpExecutableFinder $phpExecutableFinder,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \MP\MessageQueue\Helper\Data $helper,
        string $name = null
    ) {
        parent::__construct(
            $consumerConfig,
            $connectionTypeResolver,
            $resource,
            $shellBackground,
            $phpExecutableFinder,
            $name
        );

        $this->deploymentConfig = $deploymentConfig;
        $this->helper           = $helper;
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName(self::COMMAND);
        $this->setDescription('Start MessageQueue consumer');

        $this->addArgument(
            self::ARGUMENT_CONSUMER,
            \Symfony\Component\Console\Input\InputArgument::REQUIRED,
            'The name of the consumer to be started.'
        );

        $this->setHelp(
            <<<HELP
This command starts MessageQueue consumer by its name.

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
     */
    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    ) {
        $this->writeln($output, __('Starting MessageQueue consumer.'));

        $consumerName = $input->getArgument(self::ARGUMENT_CONSUMER);
        $maxMessages  = (int) $this->deploymentConfig->get('cron_consumers_runner/max_messages', 10000);

        $arguments = [
            $consumerName,
            '--pid-file-path=' . $this->helper->getPidFilePath($consumerName),
        ];

        if ($maxMessages) {
            $arguments[] = '--max-messages=' . $maxMessages;
        }

        $php     = $this->getPhpExecutablePath();
        $command = $php . ' ' . BP . '/bin/magento queue:consumers:start %s %s' . ($maxMessages ? ' %s' : '');

        $this->cmd($command, $arguments);

        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }
}
