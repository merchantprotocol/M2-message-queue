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

namespace MP\MessageQueue\Api\Data;

/**
 * Interface MessageStatusInterface
 *
 * @package MP\MessageQueue\Api\Data
 */
interface MessageStatusInterface
{
    /**
     * @const string
     */
    const ENTITY = 'mpdb_queue_message_status';

    /**
     * @const string
     */
    const ENTITY_ID        = 'id';
    const QUEUE_ID         = 'queue_id';
    const MESSAGE_ID       = 'message_id';
    const UPDATED_AT       = 'updated_at';
    const STATUS           = 'status';
    const NUMBER_OF_TRIALS = 'number_of_trials';

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * @return int|null
     */
    public function getId();

    /**
     * @param int $queueId
     * @return $this
     */
    public function setQueueId($queueId);

    /**
     * @return int|null
     */
    public function getQueueId();

    /**
     * @param int $messageId
     * @return $this
     */
    public function setMessageId($messageId);

    /**
     * @return int|null
     */
    public function getMessageId();

    /**
     * @param int $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);

    /**
     * @return int|null
     */
    public function getUpdatedAt();

    /**
     * @param int $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * @return int|null
     */
    public function getStatus();

    /**
     * @param int $numberOfTrials
     * @return $this
     */
    public function setNumberOfTrials($numberOfTrials);

    /**
     * @return int|null
     */
    public function getNumberOfTrials();
}
