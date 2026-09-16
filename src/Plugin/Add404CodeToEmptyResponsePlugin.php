<?php

/**
 * @category    ScandiPWA
 * @package     ScandiPWA_Cache
 * @copyright   Copyright © 2021 Scandiweb, Ltd (https://scandiweb.com)
 * @copyright   Modifications © Selveq. All rights reserved.
 * @license     OSL-3.0 (Open Software License ("OSL") v. 3.0)
 * See LICENSE for license details.
 */

namespace ScandiPWA\Cache\Plugin;

use InvalidArgumentException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\HTTP\PhpEnvironment\Response as HttpResponse;
use Magento\Framework\Interception\InterceptorInterface;
use Magento\Framework\Serialize\Serializer\Json;
use ScandiPWA\PersistedQuery\Plugin\PersistedQuery;

class Add404CodeToEmptyResponsePlugin
{
    // the one error category that means the route is missing rather than the request being wrong
    private const CATEGORY_NOT_FOUND = 'graphql-no-such-entity';

    /**
     * @param Json $json
     */
    public function __construct(
        private readonly Json $json
    ) {}

    /**
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

        return $this->applyStatus($response);
    }

    /**
     * apply the same rule to a persisted query response, which afterDispatch() never sees.
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
        return $this->applyStatus($response);
    }

    /**
     * set 404 on a response that resolved nothing because the entity is missing.
     * @param HttpInterface $response
     * @return HttpInterface
     */
    private function applyStatus(HttpInterface $response): HttpInterface
    {
        // HttpInterface declares no body accessor; every response Magento hands this plugin extends HttpResponse
        if (!$response instanceof HttpResponse) {
            return $response;
        }

        try {
            $responseContent = $this->json->unserialize($response->getBody());
        } catch (InvalidArgumentException) {
            // body is not JSON; leave the response alone
            return $response;
        }

        // a body without a GraphQL data envelope is not this plugin's to rewrite
        if (!is_array($responseContent)
            || !array_key_exists('data', $responseContent)
            || !is_array($responseContent['data'])
        ) {
            return $response;
        }

        // a partial response still carries usable data, so only a total failure is a 404
        if ($this->hasUsableData($responseContent)) {
            return $response;
        }

        // a validation or authorisation error is a bad request, not a missing route, so core's status stands
        if (array_key_exists('errors', $responseContent) && !$this->isNotFound($responseContent)) {
            return $response;
        }

        // an empty answer carries no tags, so only this 404's max-age=0 keeps it out of the pool and Varnish
        return $this->set404Code($response);
    }

    /**
     * set 404 code and adjust cache-control header
     * @param HttpInterface $response
     * @return HttpInterface
     */
    public function set404Code($response)
    {
        // not setStatusHeader(): it re-applies a stale reason phrase and downgrades the protocol
        $response->setHttpResponseCode(404);
        $response->setHeader('cache-control', 'public, must-revalidate, proxy-revalidate, max-age=0', true);

        return $response;
    }

    /**
     * whether any top-level field resolved to something the client can use.
     * @param array $responseContent
     * @return bool
     */
    private function hasUsableData(array $responseContent): bool
    {
        if (!array_key_exists('data', $responseContent) || !is_array($responseContent['data'])) {
            return false;
        }

        foreach ($responseContent['data'] as $field) {
            if (!$this->isEmptyField($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * upstream's notion of empty: null, an empty array, or an array whose first element is either.
     * @param mixed $field
     * @return bool
     */
    private function isEmptyField($field): bool
    {
        if ($field === null) {
            return true;
        }

        if (!is_array($field)) {
            return false;
        }

        if (!$field) {
            return true;
        }

        $first = current($field);

        return $first === null || (is_array($first) && !$first);
    }

    /**
     * whether every error reports a missing entity, which is the only error shape that maps to 404.
     * @param array $responseContent
     * @return bool
     */
    private function isNotFound(array $responseContent): bool
    {
        $errors = $responseContent['errors'] ?? null;

        if (!is_array($errors) || !$errors) {
            return false;
        }

        foreach ($errors as $error) {
            if (!is_array($error) || ($error['extensions']['category'] ?? null) !== self::CATEGORY_NOT_FOUND) {
                return false;
            }
        }

        return true;
    }
}
