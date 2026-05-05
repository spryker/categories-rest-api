<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\CategoriesRestApi\Api\Storefront\Provider;

use Generated\Api\Storefront\CategoryTreesStorefrontResource;
use Spryker\ApiPlatform\State\Provider\AbstractStorefrontProvider;
use Spryker\Client\CategoryStorage\CategoryStorageClientInterface;

class CategoryTreesStorefrontProvider extends AbstractStorefrontProvider
{
    public function __construct(
        protected CategoryStorageClientInterface $categoryStorageClient,
    ) {
    }

    /**
     * @return array<object>|null
     */
    protected function provideCollection(): array|null
    {
        $locale = $this->getLocale()->getLocaleName() ?? '';
        $storeName = $this->getStore()->getNameOrFail();

        $categoryTree = $this->categoryStorageClient->getCategories($locale, $storeName);

        $categoryNodes = [];

        foreach ($categoryTree as $categoryNodeStorageTransfer) {
            $categoryNodes[] = $categoryNodeStorageTransfer->toArray(true, true);
        }

        return [CategoryTreesStorefrontResource::fromArray([
            'categoryTreeId' => 'category-trees',
            'categoryNodesStorage' => $categoryNodes,
        ])];
    }
}
