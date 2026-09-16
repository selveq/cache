<?php

/**
 * @category    ScandiPWA
 * @package     ScandiPWA_Cache
 * @copyright   Copyright © 2019 Scandiweb, Ltd (https://scandiweb.com)
 * @copyright   Modifications © Selveq. All rights reserved.
 * @license     OSL-3.0 (Open Software License ("OSL") v. 3.0)
 * See LICENSE for license details.
 */

namespace ScandiPWA\Cache\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http as AppResponse;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\Interception\InterceptorInterface;
use Magento\GraphQlCache\Model\CacheableQuery;
use ScandiPWA\Cache\Model\CacheInterface;
use ScandiPWA\PersistedQuery\Plugin\PersistedQuery;

class AddTagsToResponsePlugin
{
    /**
     * @param CacheInterface $cache
     * @param CacheableQuery $cacheableQuery
     */
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly CacheableQuery $cacheableQuery
    ) {}

    /**
     * add tag headers to the response
     * @param InterceptorInterface $interceptor
     * @param HttpInterface $response
     * @param RequestInterface $request
     * @return HttpInterface
     */
    public function afterDispatch(
        InterceptorInterface $interceptor,
        HttpInterface $response,
        RequestInterface $request
    ) {
        if (!array_key_exists('hash', $request->getParams())) {
            return $response;
        }

        return $this->addTags($response);
    }

    /**
     * tag the response a persisted query produced, which afterDispatch() never sees.
     * @param PersistedQuery $subject
     * @param HttpInterface $response
     * @param RequestInterface $request
     * @return HttpInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterProcessRequest(
        PersistedQuery $subject,
        HttpInterface $response,
        RequestInterface $request
    ) {
        return $this->addTags($response);
    }

    /**
     * write the accumulated cache identities onto the response.
     * @param HttpInterface $response
     * @return HttpInterface
     */
    private function addTags(HttpInterface $response): HttpInterface
    {
        // a resolver declared cacheable: false, so the response must not be stored by anything
        if (!$this->cacheableQuery->isCacheable()) {
            return $response;
        }

        // core keeps collecting identities on a resolver-cache hit, where no entity loads and no observer runs
        $identities = array_unique(array_merge($this->cache->getIdentities(), $this->cacheableQuery->getCacheTags()));

        if (!$identities) {
            return $this->refusePool($response);
        }

        // replace, because core's GraphQlCache plugin writes this same header on the responses it caches
        $response->setHeader('X-Magento-Tags', implode(',', $identities), true);

        return $response;
    }

    /**
     * keep an answer that carries no identity out of the shared pool, as core's rule is.
     * @param HttpInterface $response
     * @return HttpInterface
     */
    private function refusePool(HttpInterface $response): HttpInterface
    {
        // CacheableQuery::shouldPopulateCacheHeadersWithTags() pools a GET only when the answer carries tags
        if ($response instanceof AppResponse) {
            $response->setNoCacheHeaders();
        }

        // the persisted-query plugin offered this body to the pool before the tags were known to be empty
        $response->clearHeader('X-Pool');

        return $response;
    }
}
