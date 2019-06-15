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

namespace MP\MessageQueue\Model\ResourceModel;

/**
 * Class Queue
 *
 * @package MP\MessageQueue\Model\ResourceModel
 */
class Queue extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \MP\MessageQueue\Api\Data\QueueInterface::ENTITY,
            \MP\MessageQueue\Api\Data\QueueInterface::ENTITY_ID
        );
    }

    /**
     * @param string $messageTopic
     * @param string $messageBody
     * @return int ID of the inserted record
     */
    public function saveMessage($messageTopic, $messageBody)
    {
        $this->getConnection()->insert(
            $this->getMessageTable(),
            ['topic_name' => $messageTopic, 'body' => $messageBody]
        );

        return $this->getConnection()->lastInsertId($this->getMessageTable());
    }

    /**
     * @param string $messageTopic
     * @param array $messages
     * @return array List of IDs of inserted records
     */
    public function saveMessages($messageTopic, array $messages)
    {
        $data = [];

        foreach ($messages as $message) {
            $data[] = ['topic_name' => $messageTopic, 'body' => $message];
        }

        $rowCount = $this->getConnection()->insertMultiple($this->getMessageTable(), $data);
        $firstId  = $this->getConnection()->lastInsertId($this->getMessageTable());
        $select   = $this->getConnection()->select()
            ->from(['qm' => $this->getMessageTable()], ['id'])
            ->where('qm.id >= ?', $firstId)
            ->limit($rowCount);

        return $this->getConnection()->fetchCol($select);
    }

    /**
     * @param int $messageId
     * @param string[] $queueNames
     * @return \MP\MessageQueue\Model\ResourceModel\Queue
     */
    public function linkQueues($messageId, $queueNames)
    {
        return $this->linkMessagesWithQueues([$messageId], $queueNames);
    }

    /**
     * @param array $messageIds
     * @param string[] $queueNames
     * @return $this
     */
    public function linkMessagesWithQueues(array $messageIds, array $queueNames)
    {
        $connection = $this->getConnection();
        $queueIds   = $this->getQueueIdsByNames($queueNames);
        $data       = [];

        foreach ($messageIds as $messageId) {
            foreach ($queueIds as $queueId) {
                $data[] = [
                    $queueId,
                    $messageId,
                    \MP\MessageQueue\Model\QueueManagement::MESSAGE_STATUS_NEW
                ];
            }
        }

        if (!empty($data)) {
            $connection->insertArray(
                $this->getMessageStatusTable(),
                ['queue_id', 'message_id', 'status'],
                $data
            );
        }

        return $this;
    }

    /**
     * @param string[] $queueNames
     * @return int[]
     */
    protected function getQueueIdsByNames($queueNames)
    {
        $selectObject = $this->getConnection()->select();
        $selectObject->from(['queue' => $this->getQueueTable()])
            ->columns(['id'])
            ->where('queue.name IN (?)', $queueNames);

        return $this->getConnection()->fetchCol($selectObject);
    }

    /**
     * @param string $queueName
     * @param int|null $limit
     * @return array
     */
    public function getMessages($queueName, $limit = null)
    {
        $connection = $this->getConnection();
        $select     = $connection->select()
            ->from(
                ['queue_message' => $this->getMessageTable()],
                [
                    \MP\MessageQueue\Model\QueueManagement::MESSAGE_TOPIC => 'topic_name',
                    \MP\MessageQueue\Model\QueueManagement::MESSAGE_BODY  => 'body'
                ]
            )->join(
                ['queue_message_status' => $this->getMessageStatusTable()],
                'queue_message.id = queue_message_status.message_id',
                [
                    \MP\MessageQueue\Model\QueueManagement::MESSAGE_QUEUE_RELATION_ID => 'id',
                    \MP\MessageQueue\Model\QueueManagement::MESSAGE_QUEUE_ID          => 'queue_id',
                    \MP\MessageQueue\Model\QueueManagement::MESSAGE_ID                => 'message_id',
                    \MP\MessageQueue\Model\QueueManagement::MESSAGE_STATUS            => 'status',
                    \MP\MessageQueue\Model\QueueManagement::MESSAGE_UPDATED_AT        => 'updated_at',
                    \MP\MessageQueue\Model\QueueManagement::MESSAGE_NUMBER_OF_TRIALS  => 'number_of_trials'
                ]
            )->join(
                ['queue' => $this->getQueueTable()],
                'queue.id = queue_message_status.queue_id',
                [
                    \MP\MessageQueue\Model\QueueManagement::MESSAGE_QUEUE_NAME => 'name'
                ]
            )
            ->where(
                'queue_message_status.status IN (?)',
                [
                    \MP\MessageQueue\Model\QueueManagement::MESSAGE_STATUS_NEW,
                    \MP\MessageQueue\Model\QueueManagement::MESSAGE_STATUS_RETRY_REQUIRED
                ]
            )
            ->where('queue.name = ?', $queueName)
            ->order('queue_message_status.updated_at ASC');

        if ($limit) {
            $select->limit($limit);
        }

        return $connection->fetchAll($select);
    }

    /**
     * Delete messages if there is no queue whrere the message is not in status TO BE DELETED
     *
     * @return void
     */
    public function deleteMarkedMessages()
    {
        $connection = $this->getConnection();
        $select     = $connection->select()
            ->from(['queue_message_status' => $this->getMessageStatusTable()], ['message_id'])
            ->where('status <> ?', \MP\MessageQueue\Model\QueueManagement::MESSAGE_STATUS_TO_BE_DELETED)
            ->distinct();

        $messageIds = $connection->fetchCol($select);
        $condition  = count($messageIds) > 0 ? ['id NOT IN (?)' => $messageIds] : null;

        $connection->delete($this->getMessageTable(), $condition);
    }

    /**
     * @param int[] $relationIds
     * @return int[]
     */
    public function takeMessagesInProgress($relationIds)
    {
        $takenMessagesRelationIds = [];

        foreach ($relationIds as $relationId) {
            $affectedRows = $this->getConnection()->update(
                $this->getMessageStatusTable(),
                ['status' => \MP\MessageQueue\Model\QueueManagement::MESSAGE_STATUS_IN_PROGRESS],
                ['id = ?' => $relationId]
            );

            if ($affectedRows) {
                /**
                 * If status was set to 'in progress' by some other process (due to race conditions),
                 * current process should not process the same message.
                 * So message will be processed only if current process was able to change its status.
                 */
                $takenMessagesRelationIds[] = $relationId;
            }
        }
        return $takenMessagesRelationIds;
    }

    /**
     * @param int $relationId
     * @return void
     */
    public function pushBackForRetry($relationId)
    {
        $this->getConnection()->update(
            $this->getMessageStatusTable(),
            [
                'status' => \MP\MessageQueue\Model\QueueManagement::MESSAGE_STATUS_RETRY_REQUIRED,
                'number_of_trials' => new \Zend_Db_Expr('number_of_trials+1')
            ],
            ['id = ?' => $relationId]
        );
    }

    /**
     * @param int[] $relationIds
     * @param int $status
     * @return void
     */
    public function changeStatus($relationIds, $status)
    {
        $this->getConnection()->update(
            $this->getMessageStatusTable(),
            ['status' => $status],
            ['id IN (?)' => $relationIds]
        );
    }

    /**
     * @return string
     */
    protected function getQueueTable()
    {
        return $this->getTable(\MP\MessageQueue\Api\Data\QueueInterface::ENTITY);
    }

    /**
     * @return string
     */
    protected function getMessageTable()
    {
        return $this->getTable(\MP\MessageQueue\Api\Data\MessageInterface::ENTITY);
    }

    /**
     * @return string
     */
    protected function getMessageStatusTable()
    {
        return $this->getTable(\MP\MessageQueue\Api\Data\MessageStatusInterface::ENTITY);
    }
}
