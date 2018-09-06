<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\CategoriesRestApi\Processor\Category;

use Generated\Shared\Transfer\RestErrorMessageTransfer;
use Spryker\Glue\CategoriesRestApi\CategoriesRestApiConfig;
use Spryker\Glue\CategoriesRestApi\Dependency\Client\CategoriesRestApiToCategoryStorageClientInterface;
use Spryker\Glue\CategoriesRestApi\Dependency\Client\CategoriesRestApiToProductCategoryResourceAliasStorageClientInterface;
use Spryker\Glue\CategoriesRestApi\Processor\Mapper\CategoriesResourceMapperInterface;
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
    protected $restResourceBuilder;

    /**
     * @var \Spryker\Glue\CategoriesRestApi\Dependency\Client\CategoriesRestApiToCategoryStorageClientInterface
     */
    protected $categoryStorageClient;

    /**
     * @var \Spryker\Glue\CategoriesRestApi\Dependency\Client\CategoriesRestApiToProductCategoryResourceAliasStorageClientInterface
     */
    protected $productCategoryResourceAliasStorageClient;

    /**
     * @var \Spryker\Glue\CategoriesRestApi\Processor\Mapper\CategoriesResourceMapperInterface
     */
    protected $categoriesResourceMapper;

    /**
     * @var \Spryker\Glue\CategoriesRestApi\Processor\Mapper\CategoryMapperInterface
     */
    protected $categoryMapper;

    /**
     * @param \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceBuilderInterface $restResourceBuilder
     * @param \Spryker\Glue\CategoriesRestApi\Dependency\Client\CategoriesRestApiToCategoryStorageClientInterface $categoryStorageClient
     * @param \Spryker\Glue\CategoriesRestApi\Dependency\Client\CategoriesRestApiToProductCategoryResourceAliasStorageClientInterface $productCategoryResourceAliasStorageClient
     * @param \Spryker\Glue\CategoriesRestApi\Processor\Mapper\CategoriesResourceMapperInterface $categoriesResourceMapper
     * @param \Spryker\Glue\CategoriesRestApi\Processor\Mapper\CategoryMapperInterface $categoryMapper
     */
    public function __construct(
        RestResourceBuilderInterface $restResourceBuilder,
        CategoriesRestApiToCategoryStorageClientInterface $categoryStorageClient,
        CategoriesRestApiToProductCategoryResourceAliasStorageClientInterface $productCategoryResourceAliasStorageClient,
        CategoriesResourceMapperInterface $categoriesResourceMapper,
        CategoryMapperInterface $categoryMapper
    ) {
        $this->restResourceBuilder = $restResourceBuilder;
        $this->categoryStorageClient = $categoryStorageClient;
        $this->productCategoryResourceAliasStorageClient = $productCategoryResourceAliasStorageClient;
        $this->categoriesResourceMapper = $categoriesResourceMapper;
        $this->categoryMapper = $categoryMapper;
    }

    /**
     * @param string $locale
     *
     * @return \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface
     */
    public function getCategoryTree(string $locale): RestResponseInterface
    {
        $categoryTree = $this->categoryStorageClient->getCategories($locale);
        $restCategoriesTreeTransfer = $this->categoryMapper
            ->mapCategoryTreeToRestCategoryTreesTransfer((array)$categoryTree);

        $restResponse = $this->restResourceBuilder->createRestResponse();
        $restResource = $this
            ->restResourceBuilder
            ->createRestResource(
                CategoriesRestApiConfig::RESOURCE_CATEGORY_TREES,
                null,
                $restCategoriesTreeTransfer
            );

        return $restResponse->addResource($restResource);
    }

    /**
     * @param \Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface $restRequest
     *
     * @return \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface
     */
    public function getProductCategoriesResourceBySku(RestRequestInterface $restRequest): RestResourceInterface
    {
        /** @var string $abstractSku */
        $abstractSku = $restRequest->getResource()->getId();

        $productAbstractCategoryStorageTransfer = $this->productCategoryResourceAliasStorageClient
            ->findProductCategoryAbstractStorageTransfer(
                $abstractSku,
                $restRequest->getMetadata()->getLocale()
            );

        if (!$productAbstractCategoryStorageTransfer) {
            $restErrorTransfer = $this->createRestErrorTransfer();

            return $this->restResourceBuilder->createRestResource(
                CategoriesRestApiConfig::RESOURCE_PRODUCT_CATEGORIES,
                $abstractSku,
                $restErrorTransfer
            );
        }

        $categoriesTransfer = $this->categoriesResourceMapper
            ->mapProductCategoriesToRestProductCategoriesTransfer($productAbstractCategoryStorageTransfer);

        $restResource = $this->restResourceBuilder->createRestResource(
            CategoriesRestApiConfig::RESOURCE_PRODUCT_CATEGORIES,
            $abstractSku,
            $categoriesTransfer
        );

        return $restResource;
    }

    /**
     * @param string $nodeId
     * @param string $locale
     *
     * @return \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface
     */
    public function getCategoryNode(string $nodeId, string $locale): RestResponseInterface
    {
        $restResponse = $this->restResourceBuilder->createRestResponse();
        if (!$this->isNodeIdValid($nodeId)) {
            return $this->createInvalidNodeIdResponse($restResponse);
        }
        $categoryNodeStorageTransfer = $this->categoryStorageClient->getCategoryNodeById((int)$nodeId, $locale);

        if (!$categoryNodeStorageTransfer->getNodeId()) {
            return $this->createErrorResponse($restResponse);
        }

        $restCategoryNodesAttributesTransfer = $this->categoryMapper
            ->mapCategoryNodeToRestCategoryNodesTransfer($categoryNodeStorageTransfer);

        $restResource = $this
            ->restResourceBuilder
            ->createRestResource(
                CategoriesRestApiConfig::RESOURCE_CATEGORY_NODES,
                (string)$restCategoryNodesAttributesTransfer->getNodeId(),
                $restCategoryNodesAttributesTransfer
            );

        return $restResponse->addResource($restResource);
    }

    /**
     * @param \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface $restResponse
     *
     * @return \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface
     */
    protected function createErrorResponse(RestResponseInterface $restResponse): RestResponseInterface
    {
        $restErrorTransfer = (new RestErrorMessageTransfer())
            ->setCode(CategoriesRestApiConfig::RESPONSE_CODE_CATEGORY_NOT_FOUND)
            ->setStatus(Response::HTTP_NOT_FOUND)
            ->setDetail(CategoriesRestApiConfig::RESPONSE_DETAILS_CATEGORY_NOT_FOUND);

        return $restResponse->addError($restErrorTransfer);
    }

    /**
     * @param \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface $restResponse
     *
     * @return \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface
     */
    protected function createInvalidNodeIdResponse(RestResponseInterface $restResponse): RestResponseInterface
    {
        $restErrorTransfer = (new RestErrorMessageTransfer())
            ->setCode(CategoriesRestApiConfig::RESPONSE_CODE_INVALID_CATEGORY_ID)
            ->setStatus(Response::HTTP_BAD_REQUEST)
            ->setDetail(CategoriesRestApiConfig::RESPONSE_DETAILS_INVALID_CATEGORY_ID);

        return $restResponse->addError($restErrorTransfer);
    }

    /**
     * @param string $nodeId
     *
     * @return bool
     */
    protected function isNodeIdValid(string $nodeId): bool
    {
        $convertedToInt = (int)$nodeId;
        return $nodeId === (string)$convertedToInt;
    }

    /**
     * @return \Generated\Shared\Transfer\RestErrorMessageTransfer
     */
    protected function createRestErrorTransfer(): RestErrorMessageTransfer
    {
        return (new RestErrorMessageTransfer())
            ->setCode(CategoriesRestApiConfig::RESPONSE_CODE_ABSTRACT_PRODUCT_CATEGORIES_ARE_MISSING)
            ->setStatus(Response::HTTP_NOT_FOUND)
            ->setDetail(CategoriesRestApiConfig::RESPONSE_DETAIL_ABSTRACT_PRODUCT_CATEGORIES_ARE_MISSING);
    }
}
