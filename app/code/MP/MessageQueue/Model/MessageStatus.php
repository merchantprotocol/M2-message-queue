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
 * Class MessageStatus
 *
 * @package MP\MessageQueue\Model
 */
class MessageStatus
    extends \Magento\Framework\Model\AbstractModel
    implements \MP\MessageQueue\Api\Data\MessageStatusInterface, \Magento\Framework\DataObject\IdentityInterface
{
    /**
     * @const string
     */
    const CACHE_TAG = 'mpdb_queue_message_status';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\MP\MessageQueue\Model\ResourceModel\MessageStatus::class);
    }

    /**
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        return $this->setData(self::ENTITY_ID, $id);
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @param int $queueId
     * @return $this
     */
    public function setQueueId($queueId)
    {
        return $this->setData(self::QUEUE_ID, $queueId);
    }

    /**
     * @return int|null
     */
    public function getQueueId()
    {
        return $this->getData(self::QUEUE_ID);
    }

    /**
     * @param int $messageId
     * @return $this
     */
    public function setMessageId($messageId)
    {
        return $this->setData(self::MESSAGE_ID, $messageId);
    }

    /**
     * @return int|null
     */
    public function getMessageId()
    {
        return $this->getData(self::MESSAGE_ID);
    }

    /**
     * @param int $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * @return int|null
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @param int $status
     * @return $this
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @return int|null
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @param int $numberOfTrials
     * @return $this
     */
    public function setNumberOfTrials($numberOfTrials)
    {
        return $this->setData(self::NUMBER_OF_TRIALS, $numberOfTrials);
    }

    /**
     * @return int|null
     */
    public function getNumberOfTrials()
    {
        return $this->getData(self::NUMBER_OF_TRIALS);
    }
}
