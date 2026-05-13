<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Processor;

use Generated\Api\Storefront\QuoteRequestConvertToQuoteStorefrontResource;
use Generated\Shared\Transfer\QuoteRequestFilterTransfer;
use Generated\Shared\Transfer\QuoteResponseTransfer;

class QuoteRequestConvertToQuoteStorefrontProcessor extends AbstractQuoteRequestStorefrontProcessor
{
    /**
     * @param \Generated\Api\Storefront\QuoteRequestConvertToQuoteStorefrontResource $data
     *
     * @throws \Spryker\ApiPlatform\Exception\GlueApiException
     */
    protected function processPost(mixed $data): mixed
    {
        $quoteRequestReference = $this->getUriVariables()['quoteRequestReference'] ?? null;
        $companyUserTransfer = $this->resolveCompanyUser();

        $quoteRequestFilterTransfer = (new QuoteRequestFilterTransfer())
            ->setQuoteRequestReference($quoteRequestReference)
            ->setCompanyUser($companyUserTransfer)
            ->setWithVersions(true);

        $quoteRequestResponseTransfer = $this->quoteRequestClient->getQuoteRequest($quoteRequestFilterTransfer);

        if (!$quoteRequestResponseTransfer->getIsSuccessful()) {
            throw $this->exceptionFactory->createQuoteRequestNotFoundException();
        }

        $quoteRequestTransfer = $quoteRequestResponseTransfer->getQuoteRequestOrFail();

        $quoteResponseTransfer = $this->quoteRequestClient->convertQuoteRequestToLockedQuote($quoteRequestTransfer);

        $this->assertQuoteResponseSuccessful($quoteResponseTransfer);

        // Re-fetch to get the updated status after conversion
        $quoteRequestResponseTransfer = $this->quoteRequestClient->getQuoteRequest($quoteRequestFilterTransfer);
        $updatedQuoteRequest = $quoteRequestResponseTransfer->getQuoteRequest() ?? $quoteRequestTransfer;

        return $this->serializer->denormalize([
            'quoteRequestReference' => $updatedQuoteRequest->getQuoteRequestReference(),
            'status' => $updatedQuoteRequest->getStatus(),
            'isLocked' => $quoteResponseTransfer->getQuoteTransfer()?->getIsLocked() ?? false,
        ], QuoteRequestConvertToQuoteStorefrontResource::class);
    }

    /**
     * @throws \Spryker\ApiPlatform\Exception\GlueApiException
     */
    protected function assertQuoteResponseSuccessful(QuoteResponseTransfer $quoteResponseTransfer): void
    {
        if ($quoteResponseTransfer->getIsSuccessful()) {
            return;
        }

        foreach ($quoteResponseTransfer->getErrors() as $quoteErrorTransfer) {
            $errorIdentifier = $quoteErrorTransfer->getErrorIdentifier();

            if ($errorIdentifier !== null) {
                throw $this->exceptionFactory->createExceptionFromErrorIdentifier($errorIdentifier);
            }
        }

        throw $this->exceptionFactory->createQuoteRequestValidationException();
    }
}
