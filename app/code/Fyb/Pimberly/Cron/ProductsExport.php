<?php

namespace Fyb\Pimberly\Cron;

class ProductsExport
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Fyb\Pimberly\Model\QueueRepository
     */
    protected $queueRepository;

    /**
     * @var \Fyb\Pimberly\Model\Api\ExportService
     */
    protected $exportService;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Fyb\Pimberly\Model\Api\ExportService $export
     * @param \Fyb\Pimberly\Model\QueueRepository $queueRepository
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Fyb\Pimberly\Model\Api\ExportService $exportService,
        \Fyb\Pimberly\Model\QueueRepository $queueRepository,
    ) {
        $this->logger = $logger;
        $this->exportService = $exportService;
        $this->queueRepository = $queueRepository;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        $this->queueRepository->clearOldLog();

        $dateFrom = $this->getDateFrom();

        $this->exportService->execute($dateFrom);
    }

    protected function getDateFrom()
    {
        $dateFrom = $this->queueRepository->getLastExportDate();

        return $dateFrom ?: null;
    }
}
