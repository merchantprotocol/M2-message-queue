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
 * Interface MessageInterface
 *
 * @package MP\MessageQueue\Api\Data
 */
interface MessageInterface
{
    /**
     * @const string
     */
    const ENTITY = 'mpdb_queue_message';

    /**
     * @const string
     */
    const ENTITY_ID  = 'id';
    const TOPIC_NAME = 'name';
    const BODY       = 'body';

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
     * @param string $topicName
     * @return $this
     */
    public function setTopicName($topicName);

    /**
     * @return string|null
     */
    public function getTopicName();

    /**
     * @param string $body
     * @return $this
     */
    public function setBody($body);

    /**
     * @return string|null
     */
    public function getBody();
}