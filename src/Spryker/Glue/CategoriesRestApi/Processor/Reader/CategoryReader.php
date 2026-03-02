<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\CategoriesRestApi\Processor\Reader;

use Generated\Shared\Transfer\CategoryNodeStorageTransfer;
use Generated\Shared\Transfer\RestErrorMessageTransfer;
use Spryker\Glue\CategoriesRestApi\CategoriesRestApiConfig;
use Spryker\Glue\CategoriesRestApi\Dependency\Client\CategoriesRestApiToCategoryStorageClientInterface;
use Spryker\Glue\CategoriesRestApi\Dependency\Client\CategoriesRestApiToStoreClientInterface;
use Spryker\Glue\CategoriesRestApi\Processor\Mapper\CategoryMapperInterface;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceBuilderInterface;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface;
use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;
use Symfony\Component\HttpFoundation\Response;

class CategoryReader implements CategoryReaderInterface
{
    /**
     * @var \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceBuilderInterface
     */
    protected RestResourceBuilderInterface $restResourceBuilder;

    /**
     * @var \Spryker\Glue\CategoriesRestApi\Dependency\Client\CategoriesRestApiToCategoryStorageClientInterface
     */
    protected CategoriesRestApiToCategoryStorageClientInterface $categoryStorageClient;

    /**
     * @var \Spryker\Glue\CategoriesRestApi\Processor\Mapper\CategoryMapperInterface
     */
    protected CategoryMapperInterface $categoryMapper;

    /**
     * @var \Spryker\Glue\CategoriesRestApi\Dependency\Client\CategoriesRestApiToStoreClientInterface
     */
    protected CategoriesRestApiToStoreClientInterface $storeClient;

    public function __construct(
        RestResourceBuilderInterface $restResourceBuilder,
        CategoriesRestApiToCategoryStorageClientInterface $categoryStorageClient,
        CategoryMapperInterface $categoryMapper,
        CategoriesRestApiToStoreClientInterface $storeClient
    ) {
        $this->restResourceBuilder = $restResourceBuilder;
        $this->categoryStorageClient = $categoryStorageClient;
        $this->categoryMapper = $categoryMapper;
        $this->storeClient = $storeClient;
    }

    public function getCategoryTree(string $locale): RestResponseInterface
    {
        $categoryTree = $this->categoryStorageClient->getCategories(
            $locale,
            $this->storeClient->getCurrentStore()->getName(),
        );
        $restCategoryTreesTransfer = $this->categoryMapper
            ->mapCategoryTreeToRestCategoryTreesTransfer((array)$categoryTree);

        $restResponse = $this->restResourceBuilder->createRestResponse();
        $restResource = $this
            ->restResourceBuilder
            ->createRestResource(
                CategoriesRestApiConfig::RESOURCE_CATEGORY_TREES,
                null,
                $restCategoryTreesTransfer,
            );

        return $restResponse->addResource($restResource);
    }

    public function getCategoryNode(string $nodeId, string $locale): RestResponseInterface
    {
        $restResponse = $this->restResourceBuilder->createRestResponse();

        $restResource = $this->findCategoryNodeById((int)$nodeId, $locale);
        if (!$restResource) {
            return $this->createErrorResponse($restResponse);
        }

        return $restResponse->addResource($restResource);
    }

    public function readCategoryNode(RestRequestInterface $restRequest): RestResponseInterface
    {
        $nodeId = $restRequest->getResource()->getId();
        if (!$this->isNodeIdValid($nodeId)) {
            return $this->createInvalidNodeIdResponse($this->restResourceBuilder->createRestResponse());
        }

        return $this->getCategoryNode($nodeId, $restRequest->getMetadata()->getLocale());
    }

    public function findCategoryNodeById(int $nodeId, string $locale): ?RestResourceInterface
    {
        $storeName = $this->storeClient->getCurrentStore()->getName();
        $categoryNodeStorageTransfer = $this->categoryStorageClient->getCategoryNodeById($nodeId, $locale, $storeName);
        if (!$categoryNodeStorageTransfer->getIdCategory()) {
            return null;
        }

        return $this->buildProductCategoryResource($categoryNodeStorageTransfer);
    }

    /**
     * @param array<int> $nodeIds
     * @param string $localeName
     *
     * @return array<\Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface>
     */
    public function findCategoryNodeByIds(array $nodeIds, string $localeName): array
    {
        $storeName = $this->storeClient->getCurrentStore()->getName();
        $categoryNodeStorageTransfers = $this->categoryStorageClient->getCategoryNodeByIds($nodeIds, $localeName, $storeName);
        if (count($categoryNodeStorageTransfers) === 0) {
            return [];
        }

        $restResources = [];

        foreach ($categoryNodeStorageTransfers as $categoryNodeStorageTransfer) {
            $restResources[$categoryNodeStorageTransfer->getNodeId()] = $this->buildProductCategoryResource($categoryNodeStorageTransfer);
        }

        return $restResources;
    }

    protected function createErrorResponse(RestResponseInterface $restResponse): RestResponseInterface
    {
        $restErrorTransfer = (new RestErrorMessageTransfer())
            ->setCode(CategoriesRestApiConfig::RESPONSE_CODE_CATEGORY_NOT_FOUND)
            ->setStatus(Response::HTTP_NOT_FOUND)
            ->setDetail(CategoriesRestApiConfig::RESPONSE_DETAILS_CATEGORY_NOT_FOUND);

        return $restResponse->addError($restErrorTransfer);
    }

    protected function createInvalidNodeIdResponse(RestResponseInterface $restResponse): RestResponseInterface
    {
        $restErrorTransfer = (new RestErrorMessageTransfer())
            ->setCode(CategoriesRestApiConfig::RESPONSE_CODE_INVALID_CATEGORY_ID)
            ->setStatus(Response::HTTP_BAD_REQUEST)
            ->setDetail(CategoriesRestApiConfig::RESPONSE_DETAILS_INVALID_CATEGORY_ID);

        return $restResponse->addError($restErrorTransfer);
    }

    protected function isNodeIdValid(?string $nodeId): bool
    {
        if (!$nodeId) {
            return false;
        }

        $convertedToInt = (int)$nodeId;

        return $nodeId === (string)$convertedToInt;
    }

    protected function buildProductCategoryResource(CategoryNodeStorageTransfer $categoryNodeStorageTransfer): RestResourceInterface
    {
        $restCategoryNodesAttributesTransfer = $this->categoryMapper
            ->mapCategoryNodeToRestCategoryNodesTransfer($categoryNodeStorageTransfer);

        return $this
            ->restResourceBuilder
            ->createRestResource(
                CategoriesRestApiConfig::RESOURCE_CATEGORY_NODES,
                (string)$restCategoryNodesAttributesTransfer->getNodeId(),
                $restCategoryNodesAttributesTransfer,
            );
    }
}
