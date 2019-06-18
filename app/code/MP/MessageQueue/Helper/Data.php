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

namespace MP\MessageQueue\Helper;

/**
 * Class Data
 *
 * @package MP\Recurring\Helper
 */
class Data extends \MP\MessageQueue\Helper\AbstractHelper
{
    /**
     * @const string
     */
    const PID_FILE_EXT = \Magento\MessageQueue\Model\Cron\ConsumersRunner::PID_FILE_EXT;

    /**
     * @var \Magento\Framework\App\DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @var \Magento\Framework\MessageQueue\ConnectionTypeResolver
     */
    private $connectionTypeResolver;

    /**
     * @var \Magento\Framework\MessageQueue\Consumer\ConfigInterface
     */
    private $consumerConfig;

    /**
     * @var \Magento\MessageQueue\Model\Cron\ConsumersRunner\PidConsumerManager
     */
    private $pidConsumerManager;

    /**
     * Data constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     * @param \Magento\Framework\MessageQueue\ConnectionTypeResolver $connectionTypeResolver
     * @param \Magento\Framework\MessageQueue\Consumer\ConfigInterface $consumerConfig
     * @param \Magento\MessageQueue\Model\Cron\ConsumersRunner\PidConsumerManager $pidConsumerManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Magento\Framework\MessageQueue\ConnectionTypeResolver $connectionTypeResolver,
        \Magento\Framework\MessageQueue\Consumer\ConfigInterface $consumerConfig,
        \Magento\MessageQueue\Model\Cron\ConsumersRunner\PidConsumerManager $pidConsumerManager
    ) {
        parent::__construct($context);

        $this->deploymentConfig       = $deploymentConfig;
        $this->connectionTypeResolver = $connectionTypeResolver;
        $this->pidConsumerManager     = $pidConsumerManager;
        $this->consumerConfig         = $consumerConfig;
    }

    /**
     * @param string $consumerName
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isRunning($consumerName)
    {
        $consumerConfig    = $this->consumerConfig->getConsumer($consumerName);
        $consumerIsRunning = $this->canBeRun($consumerConfig);

        return $consumerIsRunning;
    }

    /**
     * @param \Magento\Framework\MessageQueue\Consumer\Config\ConsumerConfigItemInterface $consumerConfig
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function canBeRun(
        \Magento\Framework\MessageQueue\Consumer\Config\ConsumerConfigItemInterface $consumerConfig
    ) {
        $runByCron = $this->deploymentConfig->get('cron_consumers_runner/cron_run', true);

        if (!$runByCron) {
            return false;
        }

        $connectionName = $consumerConfig->getConnection();

        try {
            $this->connectionTypeResolver->getConnectionType($connectionName);
        } catch (\LogicException $e) {
            return false;
        }

        $consumerName = $consumerConfig->getName();

        if (!$this->pidConsumerManager->isRun($this->getPidFilePath($consumerName))) {
            return false;
        }

        return true;
    }

    /**
     * @param string $consumerName
     * @return string
     */
    public function getPidFilePath($consumerName)
    {
        $sanitizedHostname = preg_replace('/[^a-z0-9]/i', '', gethostname());

        return $consumerName . '-' . $sanitizedHostname . self::PID_FILE_EXT;
    }
}
