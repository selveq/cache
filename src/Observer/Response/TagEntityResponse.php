<?php

/**
 * @category    ScandiPWA
 * @package     ScandiPWA_Cache
 * @copyright   Copyright © 2019 Scandiweb, Ltd (https://scandiweb.com)
 * @copyright   Modifications © Selveq. All rights reserved.
 * @license     OSL-3.0 (Open Software License ("OSL") v. 3.0)
 * See LICENSE for license details.
 */

namespace ScandiPWA\Cache\Observer\Response;

use Magento\Framework\Data\Collection;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Event\Observer;

class TagEntityResponse extends CacheableObserver
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        if (!$this->isTaggableRequest()) {
            return;
        }

        $entity = $observer->getEntity() ?? $observer->getDataByKey('object');

        if ($entity !== null) {
            $this->addEntityTags($entity);

            return;
        }

        $collection = $this->findCollection($observer->getData());

        if ($collection === null) {
            return;
        }

        foreach ($collection->getItems() as $item) {
            $this->addEntityTags($item);
        }
    }

    /**
     * @param mixed $entity
     * @return void
     */
    protected function addEntityTags($entity)
    {
        if ($entity instanceof IdentityInterface) {
            $this->cache->addIdentities($entity->getIdentities());
        }
    }

    /**
     * the ten bound events name the collection differently, so it is found by type rather than by key.
     * @param array $data
     * @return Collection|null
     */
    private function findCollection(array $data): ?Collection
    {
        foreach ($data as $value) {
            if ($value instanceof Collection) {
                return $value;
            }
        }

        return null;
    }
}
