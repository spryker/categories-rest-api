<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\CategoriesRestApi\Processor\Mapper;

use Generated\Shared\Transfer\RestUrlResolverAttributesTransfer;
use Generated\Shared\Transfer\UrlStorageTransfer;
use Spryker\Glue\CategoriesRestApi\CategoriesRestApiConfig;

class RestUrlResolverAttributesMapper implements RestUrlResolverAttributesMapperInterface
{
    public function mapUrlStorageTransferToRestUrlResolverAttributesTransfer(
        UrlStorageTransfer $urlStorageTransfer,
        RestUrlResolverAttributesTransfer $restUrlResolverAttributesTransfer
    ): RestUrlResolverAttributesTransfer {
        return $restUrlResolverAttributesTransfer->setEntityType(CategoriesRestApiConfig::RESOURCE_CATEGORY_NODES)
            ->setEntityId((string)$urlStorageTransfer->getFkResourceCategorynode());
    }
}
