<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Processor;

use Generated\Api\Storefront\QuoteRequestCancelStorefrontResource;

class QuoteRequestCancelStorefrontProcessor extends AbstractQuoteRequestStorefrontProcessor
{
    /**
     * @param \Generated\Api\Storefront\QuoteRequestCancelStorefrontResource $data
     */
    protected function processPost(mixed $data): mixed
    {
        $quoteRequestResponseTransfer = $this->quoteRequestClient->cancelQuoteRequest(
            $this->buildQuoteRequestActionFilter(),
        );

        $this->assertSuccessful($quoteRequestResponseTransfer);

        return $this->denormalizeQuoteRequestStatusResource(
            $quoteRequestResponseTransfer,
            QuoteRequestCancelStorefrontResource::class,
        );
    }
}
