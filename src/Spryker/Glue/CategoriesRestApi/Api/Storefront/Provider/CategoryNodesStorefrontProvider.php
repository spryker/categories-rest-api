<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\CategoriesRestApi\Api\Storefront\Provider;

use Generated\Api\Storefront\CategoryNodesStorefrontResource;
use Generated\Shared\Transfer\CategoryNodeStorageTransfer;
use Spryker\ApiPlatform\State\Provider\AbstractStorefrontProvider;
use Spryker\Client\CategoryStorage\CategoryStorageClientInterface;

class CategoryNodesStorefrontProvider extends AbstractStorefrontProvider
{
    protected const string URI_VAR_NODE_ID = 'nodeId';

    public function __construct(
        protected CategoryStorageClientInterface $categoryStorageClient,
    ) {
    }

    protected function provideItem(): ?object
    {
        if (!$this->hasUriVariable(static::URI_VAR_NODE_ID)) {
            return null;
        }

        $nodeId = $this->getUriVariable(static::URI_VAR_NODE_ID);

        if (!is_numeric($nodeId)) {
            return null;
        }

        $localeName = $this->getLocale()->getLocaleNameOrFail();
        $storeName = $this->getStore()->getNameOrFail();

        $categoryNodeStorageTransfer = $this->categoryStorageClient->getCategoryNodeById(
            (int)$nodeId,
            $localeName,
            $storeName,
        );

        if ($categoryNodeStorageTransfer->getIdCategory() === null) {
            return null;
        }

        return $this->mapToResource($categoryNodeStorageTransfer);
    }

    protected function mapToResource(CategoryNodeStorageTransfer $node): CategoryNodesStorefrontResource
    {
        $resource = new CategoryNodesStorefrontResource();
        $resource->nodeId = (string)$node->getNodeId();
        $resource->name = $node->getName();
        $resource->metaTitle = $node->getMetaTitle();
        $resource->metaKeywords = $node->getMetaKeywords();
        $resource->metaDescription = $node->getMetaDescription();
        $resource->isActive = $node->getIsActive();
        $resource->order = $node->getOrder();
        $resource->url = $node->getUrl();
        $resource->children = $this->toArray($node->getChildren());
        $resource->parents = $this->toArray($node->getParents());

        return $resource;
    }

    /**
     * @param iterable<\Generated\Shared\Transfer\CategoryNodeStorageTransfer> $nodes
     *
     * @return array<int, array<string, mixed>>
     */
    protected function toArray(iterable $nodes): array
    {
        $result = [];
        foreach ($nodes as $node) {
            $result[] = $node->toArray();
        }

        return $result;
    }
}
