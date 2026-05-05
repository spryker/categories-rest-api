<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\CategoriesRestApi\Api\Storefront\Relationship;

use ArrayObject;
use Generated\Api\Storefront\CategoryNodesStorefrontResource;
use Generated\Shared\Transfer\CategoryNodeStorageTransfer;
use Spryker\ApiPlatform\Relationship\AbstractRelationshipResolver;
use Spryker\Client\CategoryStorage\CategoryStorageClientInterface;

/**
 * Resolves category nodes for navigation resources by walking the navigation
 * node tree recursively and fetching category nodes by their resource IDs.
 */
class NavigationCategoryNodesRelationshipResolver extends AbstractRelationshipResolver
{
    protected const string NODE_KEY_RESOURCE_ID = 'resourceId';

    protected const string NODE_KEY_CHILDREN = 'children';

    public function __construct(
        protected CategoryStorageClientInterface $categoryStorageClient,
    ) {
    }

    /**
     * @return array<\Generated\Api\Storefront\CategoryNodesStorefrontResource>
     */
    protected function resolveRelationship(): array
    {
        $nodeIds = $this->extractCategoryNodeIds($this->getParentResources());

        if ($nodeIds === []) {
            return [];
        }

        $locale = $this->getLocale()->getLocaleName() ?? '';
        $storeName = $this->getStore()->getNameOrFail();

        $categoryNodeStorageTransfers = $this->categoryStorageClient->getCategoryNodeByIds($nodeIds, $locale, $storeName);

        $resources = [];

        foreach ($categoryNodeStorageTransfers as $categoryNodeStorageTransfer) {
            if (!$categoryNodeStorageTransfer->getIdCategory()) {
                continue;
            }

            $resources[] = CategoryNodesStorefrontResource::fromArray(
                $this->prepareNodeResourceData($categoryNodeStorageTransfer),
            );
        }

        return $resources;
    }

    /**
     * @param array<object> $parentResources
     *
     * @return array<int>
     */
    protected function extractCategoryNodeIds(array $parentResources): array
    {
        $nodeIds = [];

        foreach ($parentResources as $navigationResource) {
            $nodes = $navigationResource->nodes ?? [];

            if (!is_array($nodes)) {
                continue;
            }

            $this->collectNodeIdsRecursively($nodes, $nodeIds);
        }

        return array_values($nodeIds);
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     * @param array<int, int> $nodeIds
     *
     * @return void
     */
    protected function collectNodeIdsRecursively(array $nodes, array &$nodeIds): void
    {
        foreach ($nodes as $node) {
            $resourceId = $node[static::NODE_KEY_RESOURCE_ID] ?? null;

            if (is_int($resourceId) && !isset($nodeIds[$resourceId])) {
                $nodeIds[$resourceId] = $resourceId;
            }

            $children = $node[static::NODE_KEY_CHILDREN] ?? [];

            if (is_array($children) && $children !== []) {
                $this->collectNodeIdsRecursively($children, $nodeIds);
            }
        }
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
}
