<?php

/**
 * @category    ScandiPWA
 * @package     ScandiPWA_Cache
 * @copyright   Copyright © 2019 Scandiweb, Ltd (https://scandiweb.com)
 * @copyright   Modifications © Selveq. All rights reserved.
 * @license     OSL-3.0 (Open Software License ("OSL") v. 3.0)
 * See LICENSE for license details.
 */

namespace ScandiPWA\Cache\Model;

use Magento\Framework\ObjectManager\ResetAfterRequestInterface;

class Cache implements CacheInterface, ResetAfterRequestInterface
{
    /**
     * @var string[]
     */
    private $identities = [];

    /**
     * {@inheritdoc}
     */
    public function addIdentities(array $identities): array
    {
        $merged = array_merge($this->identities, $identities);

        // array_unique keeps the original keys, and the tag header is the only consumer of the order
        return $this->identities = array_values(array_unique($merged));
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentities(): array
    {
        return $this->identities;
    }

    /**
     * {@inheritdoc}
     */
    public function identitiesExist(): bool
    {
        return (bool)count($this->identities);
    }

    /**
     * {@inheritdoc}
     */
    public function _resetState(): void
    {
        $this->identities = [];
    }
}
