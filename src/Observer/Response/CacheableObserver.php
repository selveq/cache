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

use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\ObserverInterface;
use ScandiPWA\Cache\Model\CacheInterface;

abstract class CacheableObserver implements ObserverInterface
{
    /**
     * @param CacheInterface $cache
     * @param Http $request
     */
    public function __construct(
        protected readonly CacheInterface $cache,
        protected readonly Http $request
    ) {}

    /**
     * @return bool
     */
    protected function isTaggableRequest(): bool
    {
        return $this->request->getFrontName() === 'graphql'
            && array_key_exists('hash', $this->request->getParams());
    }
}
