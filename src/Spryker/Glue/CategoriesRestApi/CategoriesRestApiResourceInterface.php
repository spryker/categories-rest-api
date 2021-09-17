<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\CategoriesRestApi;

use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface;

interface CategoriesRestApiResourceInterface
{
    /**
     * Specification:
     * - Retrieves category node resource by node id.
     *
     * @api
     *
     * @param int $nodeId
     * @param string $localeName
     *
     * @return \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface|null
     */
    public function findCategoryNodeById(int $nodeId, string $localeName): ?RestResourceInterface;

    /**
     * Specification:
     * - Retrieves category node resource by array of node ids.
     *
     * @api
     *
     * @param array<int> $nodeIds
     * @param string $localeName
     *
     * @return array<\Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface>
     */
    public function findCategoryNodeByIds(array $nodeIds, string $localeName): array;
}
