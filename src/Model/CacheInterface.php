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

interface CacheInterface
{
    /**
     * @param string[] $identities
     * @return string[]
     */
    public function addIdentities(array $identities): array;

    /**
     * @return string[]
     */
    public function getIdentities(): array;

    /**
     * @return bool
     */
    public function identitiesExist(): bool;
}
