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

namespace MP\MessageQueue\Model\Driver\Bulk;

/**
 * Class Exchange
 *
 * @package MP\MessageQueue\Model\Driver\Bulk
 */
class Exchange implements \Magento\Framework\MessageQueue\Bulk\ExchangeInterface
{
    /**
     * @var \Magento\Framework\MessageQueue\ConfigInterface
     */
    private $messageQueueConfig;

    /**
     * @var \MP\MessageQueue\Model\QueueManagement
     */
    private $queueManagement;

    /**
     * Exchange constructor
     *
     * @param \Magento\Framework\MessageQueue\ConfigInterface $messageQueueConfig
     * @param \MP\MessageQueue\Model\QueueManagement $queueManagement
     */
    public function __construct(
        \Magento\Framework\MessageQueue\ConfigInterface $messageQueueConfig,
        \MP\MessageQueue\Model\QueueManagement $queueManagement
    ) {
        $this->messageQueueConfig = $messageQueueConfig;
        $this->queueManagement    = $queueManagement;
    }

    /**
     * @param string $topic
     * @param array $envelopes
     * @return mixed|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function enqueue($topic, array $envelopes)
    {
        $queueNames = $this->messageQueueConfig->getQueuesByTopic($topic);
        $messages   = array_map(
            function ($envelope) {
                /** @var \MP\MessageQueue\Model\Message $envelope */
                return $envelope->getBody();
            },
            $envelopes
        );

        $this->queueManagement->addMessagesToQueues($topic, $messages, $queueNames);

        return null;
    }
}
