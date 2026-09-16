<?php

/**
 * @category    ScandiPWA
 * @package     ScandiPWA_Cache
 * @copyright   Copyright © 2019 Scandiweb, Ltd (https://scandiweb.com)
 * @copyright   Modifications © Selveq. All rights reserved.
 * @license     OSL-3.0 (Open Software License ("OSL") v. 3.0)
 * See LICENSE for license details.
 */

namespace ScandiPWA\Cache\Observer;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\PageCache\Model\Config;
use ScandiPWA\PersistedQuery\Model\PurgeCache;

class FlushVarnishObserver implements ObserverInterface
{
    // varnish matches this against the comma-joined tag list, so anchoring stops cat_p_1 purging cat_p_10
    private const TAG_PATTERN = '((^|,)%s(,|$))';

    /**
     * @param PurgeCache $purgeCache
     * @param Config $config
     */
    public function __construct(
        private readonly PurgeCache $purgeCache,
        private readonly Config $config
    ) {}

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->config->getType() !== Config::VARNISH || !$this->config->isEnabled()) {
            return;
        }

        $entity = $observer->getEntity() ?? $observer->getDataByKey('data_object');
        if (!($entity instanceof IdentityInterface)) {
            return;
        }

        $patterns = [];
        foreach ($entity->getIdentities() as $tag) {
            $patterns[] = sprintf(self::TAG_PATTERN, $tag);
        }

        if (!$patterns) {
            return;
        }

        $this->purgeCache->sendPurgeRequest(array_unique($patterns));
    }
}
