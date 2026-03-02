<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\CategoriesRestApi\Processor\UrlResolver;

use Generated\Shared\Transfer\RestUrlResolverAttributesTransfer;
use Generated\Shared\Transfer\UrlStorageTransfer;
use Spryker\Glue\CategoriesRestApi\Dependency\Client\CategoriesRestApiToCategoryStorageClientInterface;
use Spryker\Glue\CategoriesRestApi\Dependency\Client\CategoriesRestApiToStoreClientInterface;
use Spryker\Glue\CategoriesRestApi\Processor\Mapper\RestUrlResolverAttributesMapperInterface;

class CategoryNodeUrlResolver implements CategoryNodeUrlResolverInterface
{
    /**
     * @var \Spryker\Glue\CategoriesRestApi\Dependency\Client\CategoriesRestApiToCategoryStorageClientInterface
     */
    protected $categoryStorageClient;

    /**
     * @var \Spryker\Glue\CategoriesRestApi\Dependency\Client\CategoriesRestApiToStoreClientInterface
     */
    protected $storeClient;

    /**
     * @var \Spryker\Glue\CategoriesRestApi\Processor\Mapper\RestUrlResolverAttributesMapperInterface
     */
    protected $restUrlResolverAttributesMapper;

    public function __construct(
        CategoriesRestApiToCategoryStorageClientInterface $categoryStorageClient,
        CategoriesRestApiToStoreClientInterface $storeClient,
        RestUrlResolverAttributesMapperInterface $restUrlResolverAttributesMapper
    ) {
        $this->categoryStorageClient = $categoryStorageClient;
        $this->storeClient = $storeClient;
        $this->restUrlResolverAttributesMapper = $restUrlResolverAttributesMapper;
    }

    public function resolveCategoryNodeUrl(UrlStorageTransfer $urlStorageTransfer): ?RestUrlResolverAttributesTransfer
    {
        $localeName = $this->findLocaleName($urlStorageTransfer);
        if (!$localeName) {
            return null;
        }

        $categoryNodeStorageTransfer = $this->categoryStorageClient->getCategoryNodeById(
            $urlStorageTransfer->getFkResourceCategorynodeOrFail(),
            $localeName,
            $this->storeClient->getCurrentStore()->getName(),
        );

        if (!$categoryNodeStorageTransfer->getIdCategory()) {
            return null;
        }

        return $this->restUrlResolverAttributesMapper->mapUrlStorageTransferToRestUrlResolverAttributesTransfer(
            $urlStorageTransfer,
            new RestUrlResolverAttributesTransfer(),
        );
    }

    protected function findLocaleName(UrlStorageTransfer $urlStorageTransfer): ?string
    {
        if ($urlStorageTransfer->getLocaleName()) {
            return $urlStorageTransfer->getLocaleName();
        }

        foreach ($urlStorageTransfer->getLocaleUrls() as $localeUrlStorageTransfer) {
            if ($localeUrlStorageTransfer->getFkLocale() === $urlStorageTransfer->getFkLocale()) {
                return $localeUrlStorageTransfer->getLocaleName();
            }
        }

        return null;
    }
}
