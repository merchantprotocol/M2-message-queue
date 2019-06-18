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

namespace MP\MessageQueue\Model\ResourceModel\Message\Grid;

/**
 * Class Collection
 *
 * @package MP\Recurring\Model\ResourceModel\Queue\Grid
 */
class Collection
    extends \MP\MessageQueue\Model\ResourceModel\Message\Collection
    implements \Magento\Framework\Api\Search\SearchResultInterface
{
    /**
     * @var \Magento\Framework\Api\Search\AggregationInterface
     */
    private $aggregations;

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Magento\Framework\View\Element\UiComponent\DataProvider\Document::class,
            \MP\MessageQueue\Model\ResourceModel\Message::class
        );
    }

    /**
     * @return $this
     */
    protected function _initSelect()
    {
        $queueTable              = $this->getTable(\MP\MessageQueue\Api\Data\QueueInterface::ENTITY);
        $queueMessageTable       = $this->getMainTable();
        $queueMessageStatusTable = $this->getTable(\MP\MessageQueue\Api\Data\MessageStatusInterface::ENTITY);

        $this->getSelect()
            ->from(
                ['main_table' => $queueMessageTable],
                ['id', 'topic_name', 'body']
            )
            ->joinInner(
                ['qms' => $queueMessageStatusTable],
                implode(' AND ', ['qms.message_id = main_table.id']),
                ['queue_id', 'message_id', 'updated_at', 'status', 'number_of_trials']
            )
            ->joinInner(
                ['q' => $queueTable],
                implode(' AND ', ['q.id = qms.queue_id']),
                ['name']
            );

        return $this;
    }

    /**
     * @return \Magento\Framework\Api\Search\AggregationInterface
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * @param \Magento\Framework\Api\Search\AggregationInterface $aggregations
     * @return $this
     */
    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;

        return $this;
    }

    /**
     * @return \Magento\Framework\Api\SearchCriteriaInterface|null
     */
    public function getSearchCriteria()
    {
        return null;
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return $this
     */
    public function setSearchCriteria(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null)
    {
        return $this;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        return $this->getSize();
    }

    /**
     * @param int $totalCount
     * @return $this
     */
    public function setTotalCount($totalCount)
    {
        return $this;
    }

    /**
     * @param \Magento\Framework\Api\ExtensibleDataInterface[] $items
     * @return $this
     */
    public function setItems(array $items = null)
    {
        return $this;
    }
}
