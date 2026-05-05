<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\CategoriesRestApi\Api\Storefront\Provider;

use ArrayObject;
use Generated\Api\Storefront\CategoryNodesStorefrontResource;
use Generated\Shared\Transfer\CategoryNodeStorageTransfer;
use Spryker\ApiPlatform\Exception\GlueApiException;
use Spryker\ApiPlatform\State\Provider\AbstractStorefrontProvider;
use Spryker\Client\CategoryStorage\CategoryStorageClientInterface;
use Symfony\Component\HttpFoundation\Response;

class CategoryNodesStorefrontProvider extends AbstractStorefrontProvider
{
    protected const string ERROR_CODE_INVALID_NODE_ID = '701';

    protected const string ERROR_MESSAGE_INVALID_NODE_ID = 'Category node id has not been specified or invalid.';

    protected const string ERROR_CODE_NODE_NOT_FOUND = '703';

    protected const string ERROR_MESSAGE_NODE_NOT_FOUND = '"Cant find category node with the given id."';

    public function __construct(
        protected CategoryStorageClientInterface $categoryStorageClient,
    ) {
    }

    /**
     * @throws \Spryker\ApiPlatform\Exception\GlueApiException
     */
    protected function provideCollection(): array|null
    {
        throw new GlueApiException(Response::HTTP_BAD_REQUEST, static::ERROR_CODE_INVALID_NODE_ID, static::ERROR_MESSAGE_INVALID_NODE_ID);
    }

    /**
     * @throws \Spryker\ApiPlatform\Exception\GlueApiException
     */
    protected function provideItem(): object|null
    {
        $nodeId = $this->getUriVariable('categoryNodeId');

        if ($nodeId === null || !$this->isValidNodeId((string)$nodeId)) {
            throw new GlueApiException(Response::HTTP_BAD_REQUEST, static::ERROR_CODE_INVALID_NODE_ID, static::ERROR_MESSAGE_INVALID_NODE_ID);
        }

        $locale = $this->getLocale()->getLocaleName() ?? '';
        $storeName = $this->getStore()->getNameOrFail();

        $categoryNodeStorageTransfer = $this->categoryStorageClient->getCategoryNodeById(
            (int)$nodeId,
            $locale,
            $storeName,
        );

        if (!$categoryNodeStorageTransfer->getIdCategory()) {
            throw new GlueApiException(Response::HTTP_NOT_FOUND, static::ERROR_CODE_NODE_NOT_FOUND, static::ERROR_MESSAGE_NODE_NOT_FOUND);
        }

        return CategoryNodesStorefrontResource::fromArray(
            $this->prepareNodeResourceData($categoryNodeStorageTransfer),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function prepareNodeResourceData(CategoryNodeStorageTransfer $categoryNodeStorageTransfer): array
    {
        $data = $categoryNodeStorageTransfer->toArray(false, true);
        $data['categoryNodeId'] = (string)$categoryNodeStorageTransfer->getNodeId();
        $data['children'] = $this->mapNodeCollection($categoryNodeStorageTransfer->getChildren());
        $data['parents'] = $this->mapNodeCollection($categoryNodeStorageTransfer->getParents());

        return $data;
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\CategoryNodeStorageTransfer> $nodes
     *
     * @return array<int, array<string, mixed>>
     */
    protected function mapNodeCollection(ArrayObject $nodes): array
    {
        $result = [];

        foreach ($nodes as $nodeTransfer) {
            $result[] = $nodeTransfer->toArray(true, true);
        }

        return $result;
    }

    protected function isValidNodeId(string $nodeId): bool
    {
        return ctype_digit($nodeId) && (int)$nodeId > 0;
    }
}
