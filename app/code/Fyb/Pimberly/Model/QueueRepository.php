<?php

namespace Fyb\Pimberly\Model;

use Magento\Framework\Exception\CouldNotSaveException;

class QueueRepository
{
    /**
     * @var \Fyb\Pimberly\Model\QueueFactory
     */
    protected $queueFactory;

    /**
     * @var \Fyb\Pimberly\Model\ResourceModel\Queue
     */
    protected $queueResource;

    /**
     * @var \Fyb\Pimberly\Model\ResourceModel\Queue\CollectionFactory
     */
    protected $queueCollectionFactory;

    public function __construct(
        \Fyb\Pimberly\Model\QueueFactory $queueFactory,
        \Fyb\Pimberly\Model\ResourceModel\Queue $queueResource,
        \Fyb\Pimberly\Model\ResourceModel\Queue\CollectionFactory $queueCollectionFactory,
    ) {
        $this->queueFactory = $queueFactory;
        $this->queueResource = $queueResource;
        $this->queueCollectionFactory = $queueCollectionFactory;
    }

    /**
     * @param array $data
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addToQueue($data)
    {
        $data['imported_at'] = date('Y-m-d H:i:s');
        $data['error'] = null;
        $data['is_synced'] = 0;
        unset($data['synced_at']);

        $this->queueResource->getConnection()->insertOnDuplicate(
          $this->queueResource->getMainTable(),
          $data
        );
    }

    /**
     * @param $queue
     *
     * @return \Fyb\Pimberly\Model\Queue
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save($queue)
    {
        $existingQueue = $this->getBySku($queue->getSku());
        $existingQueue->addData($queue->getData());

        try {
            $this->queueResource->save($existingQueue);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __(
                    'Could not save category: %1',
                    $e->getMessage()
                ),
                $e
            );
        }

        return $queue;
    }

    public function getActiveItems()
    {
        $collection = $this->queueCollectionFactory->create();
        $collection->addFieldToFilter('is_synced', 0);

        return $collection;
    }

    /**
     * @param string $info
     * @param bool $status
     * @param string $syncDate
     *
     * @return void
     */
    public function saveLog($info, $status, $syncDate)
    {
        $this->queueResource->getConnection()->insert('fyb_pimberly_log', [
            'info' => $info,
            'status' => $status,
            'sync_date' => $syncDate,
        ]);
    }

    public function clearOldLog()
    {
        $this->queueResource->getConnection()->delete(
            'fyb_pimberly_log',
            ['sync_date < ?' => date('Y-m-d H:i:s', time() - 86400 * 30)]
        );
    }

    public function getLastExportDate()
    {
        $select = $this->queueResource->getConnection()->select()
            ->from('fyb_pimberly_log')
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns([
                'sync_date' => new \Zend_Db_Expr('MAX(sync_date)'),
            ])
            ->where('status != 0');

        $result = $this->queueResource->getConnection()->fetchRow($select);

        return $result['sync_date'] ?? null;
    }

    /**
     * @param string $sku
     *
     * @return \Fyb\Pimberly\Model\Queue
     */
    public function getBySku($sku)
    {
        $collection = $this->queueCollectionFactory->create();
        $collection->addFieldToFilter('sku', $sku);

        $queue = $collection->getFirstItem();
        if ($queue->getId()) {
            return $queue;
        }

        return $this->queueFactory->create();
    }
}
