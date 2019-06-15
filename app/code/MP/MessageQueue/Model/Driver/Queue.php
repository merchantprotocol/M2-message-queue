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

namespace MP\MessageQueue\Model\Driver;

/**
 * Class Queue
 *
 * @package MP\MessageQueue\Model\Driver
 */
class Queue implements \Magento\Framework\MessageQueue\QueueInterface
{
    /**
     * @var \MP\MessageQueue\Model\QueueManagement
     */
    private $queueManagement;

    /**
     * @var \Magento\Framework\MessageQueue\EnvelopeFactory
     */
    private $envelopeFactory;

    /**
     * @var string
     */
    private $queueName;

    /**
     * @var int
     */
    private $interval;

    /**
     * @var int
     */
    private $maxNumberOfTrials;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Queue constructor
     *
     * @param \MP\MessageQueue\Model\QueueManagement $queueManagement
     * @param \Magento\Framework\MessageQueue\EnvelopeFactory $envelopeFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param $queueName
     * @param int $interval
     * @param int $maxNumberOfTrials
     */
    public function __construct(
        \MP\MessageQueue\Model\QueueManagement $queueManagement,
        \Magento\Framework\MessageQueue\EnvelopeFactory $envelopeFactory,
        \Psr\Log\LoggerInterface $logger,
        $queueName,
        $interval = 5,
        $maxNumberOfTrials = 3
    ) {
        $this->queueManagement   = $queueManagement;
        $this->envelopeFactory   = $envelopeFactory;
        $this->queueName         = $queueName;
        $this->interval          = $interval;
        $this->maxNumberOfTrials = $maxNumberOfTrials;
        $this->logger            = $logger;
    }

    /**
     * @return \Magento\Framework\MessageQueue\Envelope|\Magento\Framework\MessageQueue\EnvelopeInterface|null
     */
    public function dequeue()
    {
        $envelope = null;
        $messages = $this->queueManagement->readMessages($this->queueName, 1);

        if (isset($messages[0])) {
            $properties = $messages[0];

            $body = $properties[\MP\MessageQueue\Model\QueueManagement::MESSAGE_BODY];
            unset($properties[\MP\MessageQueue\Model\QueueManagement::MESSAGE_BODY]);

            $envelope = $this->envelopeFactory->create(['body' => $body, 'properties' => $properties]);
        }

        return $envelope;
    }

    /**
     * @param \Magento\Framework\MessageQueue\EnvelopeInterface $envelope
     * @return void
     */
    public function acknowledge(\Magento\Framework\MessageQueue\EnvelopeInterface $envelope)
    {
        $properties = $envelope->getProperties();
        $relationId = $properties[\MP\MessageQueue\Model\QueueManagement::MESSAGE_QUEUE_RELATION_ID];

        $this->queueManagement->changeStatus(
            $relationId,
            \MP\MessageQueue\Model\QueueManagement::MESSAGE_STATUS_COMPLETE
        );
    }

    /**
     * @param array|callable $callback
     * @return void
     */
    public function subscribe($callback)
    {
        while (true) {
            while ($envelope = $this->dequeue()) {
                try {
                    call_user_func($callback, $envelope);
                    $this->acknowledge($envelope);
                } catch (\Exception $e) {
                    $this->reject($envelope);
                }
            }

            sleep($this->interval);
        }
    }

    /**
     * @param \Magento\Framework\MessageQueue\EnvelopeInterface $envelope
     * @param bool $requeue
     * @param null $rejectionMessage
     * @return null
     */
    public function reject(
        \Magento\Framework\MessageQueue\EnvelopeInterface $envelope,
        $requeue = true,
        $rejectionMessage = null
    ) {
        $properties = $envelope->getProperties();
        $relationId = $properties[\MP\MessageQueue\Model\QueueManagement::MESSAGE_QUEUE_RELATION_ID];

        if ($properties[\MP\MessageQueue\Model\QueueManagement::MESSAGE_NUMBER_OF_TRIALS] < $this->maxNumberOfTrials
            && $requeue) {
            $this->queueManagement->pushToQueueForRetry($relationId);

            return;
        }

        $this->queueManagement->changeStatus(
            [$relationId],
            \MP\MessageQueue\Model\QueueManagement::MESSAGE_STATUS_ERROR
        );

        if ($rejectionMessage !== null) {
            $this->logger->critical(__('Message has been rejected: %1', $rejectionMessage));
        }
    }

    /**
     * @param \Magento\Framework\MessageQueue\EnvelopeInterface $envelope
     * @return void
     */
    public function push(\Magento\Framework\MessageQueue\EnvelopeInterface $envelope)
    {
        $properties = $envelope->getProperties();
        $this->queueManagement->addMessageToQueues(
            $properties[\MP\MessageQueue\Model\QueueManagement::MESSAGE_TOPIC],
            $envelope->getBody(),
            [$this->queueName]
        );
    }
}
