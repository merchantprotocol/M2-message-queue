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
 * Class Restart
 *
 * @package MP\MessageQueue\Console\Command
 */
class Restart extends \MP\MessageQueue\Console\AbstractCommand
{
    /**
     * @const string
     */
    const COMMAND = 'mpdb:consumer:restart';

    /**
     * @const string
     */
    const ARGUMENT_CONSUMER = 'consumer';

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName(self::COMMAND);
        $this->setDescription('Restart MessageQueue consumer');

        $this->addArgument(
            self::ARGUMENT_CONSUMER,
            \Symfony\Component\Console\Input\InputArgument::REQUIRED,
            'The name of the consumer to be restarted.'
        );

        $this->setHelp(
            <<<HELP
This command restarts MessageQueue consumer by its name.

To restart consumer which will process all queued messages and terminate execution:

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
        $consumerName = $input->getArgument(self::ARGUMENT_CONSUMER);
        $arguments    = [$consumerName];

        $php     = $this->getPhpExecutablePath();
        $command = $php . ' ' . BP . '/bin/magento mpdb:consumer:%s %s';

        $this->writeln($output, __('Stopping MessageQueue consumer.'));
        $this->cmd($command, array_merge(['stop'], $arguments));

        $this->writeln($output, __('Starting MessageQueue consumer.'));
        $this->cmd($command, array_merge(['start'], $arguments));

        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }
}
