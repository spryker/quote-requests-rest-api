<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Processor;

use Generated\Api\Storefront\QuoteRequestReviseStorefrontResource;

class QuoteRequestReviseStorefrontProcessor extends AbstractQuoteRequestStorefrontProcessor
{
    /**
     * @param \Generated\Api\Storefront\QuoteRequestReviseStorefrontResource $data
     */
    protected function processPost(mixed $data): mixed
    {
        $quoteRequestResponseTransfer = $this->quoteRequestClient->reviseQuoteRequest(
            $this->buildQuoteRequestActionFilter(),
        );

        $this->assertSuccessful($quoteRequestResponseTransfer);

        return $this->denormalizeQuoteRequestStatusResource(
            $quoteRequestResponseTransfer,
            QuoteRequestReviseStorefrontResource::class,
        );
    }
}
