<?php

namespace Fyb\Pimberly\Cron;

class ProductSync
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Fyb\Pimberly\Model\Sync
     */
    protected $sync;

    /**
     * @var \Fyb\Pimberly\Model\ShadeGenerator
     */
    protected $shadeGenerator;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Fyb\Pimberly\Model\Sync $sync
     * @param \Fyb\Pimberly\Model\ShadeGenerator $shadeGenerator
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Fyb\Pimberly\Model\Sync $sync,
        \Fyb\Pimberly\Model\ShadeGenerator $shadeGenerator
    ) {
        $this->logger = $logger;
        $this->sync = $sync;
        $this->shadeGenerator = $shadeGenerator;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        $needShadeRegenerate = $this->sync->execute();
        if ($needShadeRegenerate) {
            $this->shadeGenerator->execute();
        }
    }
}
