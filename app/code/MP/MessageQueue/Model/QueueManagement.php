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

namespace MP\MessageQueue\Model;

/**
 * Class QueueManagement
 *
 * @package MP\MessageQueue\Model
 */
class QueueManagement
{
    /**
     * @const string
     */
    const MESSAGE_TOPIC             = 'topic_name';
    const MESSAGE_BODY              = 'body';
    const MESSAGE_ID                = 'message_id';
    const MESSAGE_STATUS            = 'status';
    const MESSAGE_UPDATED_AT        = 'updated_at';
    const MESSAGE_QUEUE_ID          = 'queue_id';
    const MESSAGE_QUEUE_NAME        = 'queue_name';
    const MESSAGE_QUEUE_RELATION_ID = 'relation_id';
    const MESSAGE_NUMBER_OF_TRIALS  = 'retries';

    /**
     * @const int
     */
    const MESSAGE_STATUS_NEW            = 2;
    const MESSAGE_STATUS_IN_PROGRESS    = 3;
    const MESSAGE_STATUS_COMPLETE       = 4;
    const MESSAGE_STATUS_RETRY_REQUIRED = 5;
    const MESSAGE_STATUS_ERROR          = 6;
    const MESSAGE_STATUS_TO_BE_DELETED  = 7;

    /**
     * @const string
     */
    const XML_PATH_SUCCESSFUL_MESSAGES_LIFETIME = 'message_queue/mysqlmq/successful_messages_lifetime';
    const XML_PATH_FAILED_MESSAGES_LIFETIME     = 'message_queue/mysqlmq/failed_messages_lifetime';
    const XML_PATH_RETRY_IN_PROGRESS_AFTER      = 'message_queue/mysqlmq/retry_inprogress_after';
    const XML_PATH_NEW_MESSAGES_LIFETIME        = 'message_queue/mysqlmq/new_messages_lifetime';

    /**
     * @var \MP\MessageQueue\Model\ResourceModel\Queue
     */
    private $messageResource;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;

    /**
     * @var \MP\MessageQueue\Model\ResourceModel\MessageStatus\CollectionFactory
     */
    private $messageStatusCollectionFactory;

    /**
     * @param \MP\MessageQueue\Model\ResourceModel\Queue $messageResource
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \MP\MessageQueue\Model\ResourceModel\MessageStatus\CollectionFactory $messageStatusCollectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     */
    public function __construct(
        \MP\MessageQueue\Model\ResourceModel\Queue $messageResource,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \MP\MessageQueue\Model\ResourceModel\MessageStatus\CollectionFactory $messageStatusCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
    ) {
        $this->messageResource                = $messageResource;
        $this->scopeConfig                    = $scopeConfig;
        $this->dateTime                       = $dateTime;
        $this->messageStatusCollectionFactory = $messageStatusCollectionFactory;
    }

    /**
     * @param string $topic
     * @param string $message
     * @param string[] $queueNames
     * @return $this
     */
    public function addMessageToQueues($topic, $message, $queueNames)
    {
        $messageId = $this->messageResource->saveMessage($topic, $message);
        $this->messageResource->linkQueues($messageId, $queueNames);

        return $this;
    }

    /**
     * @param string $topic
     * @param array $messages
     * @param string[] $queueNames
     * @return $this
     */
    public function addMessagesToQueues($topic, $messages, $queueNames)
    {
        $messageIds = $this->messageResource->saveMessages($topic, $messages);
        $this->messageResource->linkMessagesWithQueues($messageIds, $queueNames);

        return $this;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function markMessagesForDelete()
    {
        $collection = $this->messageStatusCollectionFactory->create()
            ->addFieldToFilter(
                'status',
                ['in' => $this->getStatusesToClear()]
            );

        /**
         * Update messages if lifetime is expired
         */
        foreach ($collection as $messageStatus) {
            $this->processMessagePerStatus($messageStatus);
        }

        /**
         * Delete all messages which has To BE DELETED status in all the queues
         */
        $this->messageResource->deleteMarkedMessages();
    }

    /**
     * @param \MP\MessageQueue\Model\MessageStatus $messageStatus
     * @return void
     * @throws \Exception
     */
    private function processMessagePerStatus($messageStatus)
    {
        $now = $this->dateTime->gmtTimestamp();

        if ($messageStatus->getStatus() == self::MESSAGE_STATUS_COMPLETE
            && strtotime($messageStatus->getUpdatedAt()) < ($now - $this->getCompletedMessageLifetime())) {
            $messageStatus->setStatus(self::MESSAGE_STATUS_TO_BE_DELETED)->save();
        } elseif ($messageStatus->getStatus() == self::MESSAGE_STATUS_ERROR
            && strtotime($messageStatus->getUpdatedAt()) < ($now - $this->getErrorMessageLifetime())) {
            $messageStatus->setStatus(self::MESSAGE_STATUS_TO_BE_DELETED)->save();
        } elseif ($messageStatus->getStatus() == self::MESSAGE_STATUS_IN_PROGRESS
            && strtotime($messageStatus->getUpdatedAt()) < ($now - $this->getInProgressRetryAfter())
        ) {
            $this->pushToQueueForRetry($messageStatus->getId());
        } elseif ($messageStatus->getStatus() == self::MESSAGE_STATUS_NEW
            && strtotime($messageStatus->getUpdatedAt()) < ($now - $this->getNewMessageLifetime())
        ) {
            $messageStatus->setStatus(self::MESSAGE_STATUS_TO_BE_DELETED)->save();
        }
    }

    /**
     * @return array
     */
    private function getStatusesToClear()
    {
        /**
         * Do not mark messages for deletion if configuration has 0 lifetime configured
         */
        $statusesToDelete = [];

        if ($this->getCompletedMessageLifetime() > 0) {
            $statusesToDelete[] = self::MESSAGE_STATUS_COMPLETE;
        }

        if ($this->getErrorMessageLifetime() > 0) {
            $statusesToDelete[] = self::MESSAGE_STATUS_ERROR;
        }

        if ($this->getNewMessageLifetime() > 0) {
            $statusesToDelete[] = self::MESSAGE_STATUS_NEW;
        }

        if ($this->getInProgressRetryAfter() > 0) {
            $statusesToDelete[] = self::MESSAGE_STATUS_IN_PROGRESS;
        }

        return $statusesToDelete;
    }

    /**
     * @return int
     */
    private function getCompletedMessageLifetime()
    {
        return 60 * (int) $this->scopeConfig->getValue(
            self::XML_PATH_SUCCESSFUL_MESSAGES_LIFETIME,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return int
     */
    private function getErrorMessageLifetime()
    {
        return 60 * (int) $this->scopeConfig->getValue(
            self::XML_PATH_FAILED_MESSAGES_LIFETIME,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return int
     */
    private function getInProgressRetryAfter()
    {
        return 60 * (int) $this->scopeConfig->getValue(
            self::XML_PATH_RETRY_IN_PROGRESS_AFTER,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return int
     */
    private function getNewMessageLifetime()
    {
        return 60 * (int) $this->scopeConfig->getValue(
            self::XML_PATH_NEW_MESSAGES_LIFETIME,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param string $queue
     * @param int|null $maxMessagesNumber
     * @return array
     */
    public function readMessages($queue, $maxMessagesNumber = null)
    {
        $selectedMessages = $this->messageResource->getMessages($queue, $maxMessagesNumber);

        /**
         * The logic below allows to prevent the same message being processed by several consumers in parallel
         */
        $selectedMessagesRelatedIds = [];

        foreach ($selectedMessages as &$message) {
            /**
             * Set message status here to avoid extra reading from DB after it is updated
             */
            $message[self::MESSAGE_STATUS] = self::MESSAGE_STATUS_IN_PROGRESS;
            $selectedMessagesRelatedIds[] = $message[self::MESSAGE_QUEUE_RELATION_ID];
        }

        $takenMessagesRelationIds = $this->messageResource->takeMessagesInProgress($selectedMessagesRelatedIds);

        if (count($selectedMessages) == count($takenMessagesRelationIds)) {
            return $selectedMessages;
        }

        $selectedMessages = array_combine($selectedMessagesRelatedIds, array_values($selectedMessages));

        return array_intersect_key($selectedMessages, array_flip($takenMessagesRelationIds));
    }

    /**
     * @param int $messageRelationId
     * @return void
     */
    public function pushToQueueForRetry($messageRelationId)
    {
        $this->messageResource->pushBackForRetry($messageRelationId);
    }

    /**
     * @param int[] $messageRelationIds
     * @param int $status
     * @return void
     */
    public function changeStatus($messageRelationIds, $status)
    {
        $this->messageResource->changeStatus($messageRelationIds, $status);
    }
}
