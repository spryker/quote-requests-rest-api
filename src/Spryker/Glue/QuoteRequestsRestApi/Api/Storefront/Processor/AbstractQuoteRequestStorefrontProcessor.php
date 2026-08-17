<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Processor;

use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\QuoteRequestFilterTransfer;
use Generated\Shared\Transfer\QuoteRequestResponseTransfer;
use Spryker\ApiPlatform\State\Processor\AbstractStorefrontProcessor;
use Spryker\Client\CompanyUser\CompanyUserClientInterface;
use Spryker\Client\QuoteRequest\QuoteRequestClientInterface;
use Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Exception\QuoteRequestsExceptionFactory;
use Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Resolver\CompanyUserResolverTrait;
use Spryker\Service\Serializer\SerializerServiceInterface;

/**
 * Shared scaffold for the 5 QuoteRequests Storefront processors
 * (`QuoteRequestsStorefrontProcessor` for POST/PATCH on the main resource plus the four
 * action processors — Cancel, Revise, SendToUser, ConvertToQuote). Carries the common
 * dependencies (quote request client / serializer / exception factory / company user client), the
 * owner-scoping `CompanyUserTransfer` resolution, the shared `QuoteRequestFilterTransfer` builder,
 * the status-only resource denormalizer, and the standard error-response path:
 * a `RestErrorCollectionTransfer` arriving inside an unsuccessful `QuoteRequestResponseTransfer`
 * is mapped to a `GlueApiException` through `QuoteRequestsExceptionFactory::createExceptionFromErrorIdentifier()`.
 */
abstract class AbstractQuoteRequestStorefrontProcessor extends AbstractStorefrontProcessor
{
    use CompanyUserResolverTrait;

    public function __construct(
        protected QuoteRequestClientInterface $quoteRequestClient,
        protected SerializerServiceInterface $serializer,
        protected QuoteRequestsExceptionFactory $exceptionFactory,
        protected CompanyUserClientInterface $companyUserClient,
    ) {
    }

    /**
     * The {@see \Spryker\Glue\CompanyUsersRestApi\Api\Storefront\EventSubscriber\CompanyUserIdentityRequestSubscriber}
     * resolves the company user from the OAuth claims and attaches it to the request-scoped
     * {@see \Generated\Shared\Transfer\CustomerTransfer} (not as a separate request attribute).
     */
    protected function resolveCompanyUser(): ?CompanyUserTransfer
    {
        if (!$this->hasCustomer()) {
            return null;
        }

        return $this->getCustomer()->getCompanyUserTransfer();
    }

    /**
     * Builds the standard filter used by every action endpoint: the `quoteRequestReference`
     * from the URI, the resolved `CompanyUserTransfer` plus its `idCompanyUser`, and
     * `withVersions: true`. The `idCompanyUser` is what scopes the read to its owner, so it is
     * resolved through {@see resolveFullCompanyUser()} rather than taken as it arrives.
     */
    protected function buildQuoteRequestActionFilter(): QuoteRequestFilterTransfer
    {
        $quoteRequestReference = $this->getUriVariables()['quoteRequestReference'] ?? null;
        $companyUserTransfer = $this->resolveFullCompanyUser($this->resolveCompanyUser());

        return (new QuoteRequestFilterTransfer())
            ->setQuoteRequestReference($quoteRequestReference)
            ->setCompanyUser($companyUserTransfer)
            ->setIdCompanyUser($companyUserTransfer->getIdCompanyUser())
            ->setWithVersions(true);
    }

    /**
     * Builds the status-only response — used by action endpoints that surface
     * only the updated `{quoteRequestReference, status}` pair (Cancel, Revise).
     *
     * @template TResource of object
     *
     * @param class-string<TResource> $resourceClass
     *
     * @return TResource
     */
    protected function denormalizeQuoteRequestStatusResource(
        QuoteRequestResponseTransfer $quoteRequestResponseTransfer,
        string $resourceClass,
    ): object {
        $quoteRequestTransfer = $quoteRequestResponseTransfer->getQuoteRequestOrFail();

        return $this->serializer->denormalize([
            'quoteRequestReference' => $quoteRequestTransfer->getQuoteRequestReference(),
            'status' => $quoteRequestTransfer->getStatus(),
        ], $resourceClass);
    }

    /**
     * Translates the first error in an unsuccessful `QuoteRequestResponseTransfer` into a
     * `GlueApiException`. Falls back to a generic validation error when no message matches
     * the `errorIdentifier → REST error` mapping in `QuoteRequestsRestApiConfig`.
     *
     * @throws \Spryker\ApiPlatform\Exception\GlueApiException
     */
    protected function assertSuccessful(QuoteRequestResponseTransfer $quoteRequestResponseTransfer): void
    {
        if ($quoteRequestResponseTransfer->getIsSuccessful()) {
            return;
        }

        foreach ($quoteRequestResponseTransfer->getMessages() as $messageTransfer) {
            $messageValue = $messageTransfer->getValue();

            if ($messageValue !== null) {
                throw $this->exceptionFactory->createExceptionFromErrorIdentifier($messageValue);
            }
        }

        throw $this->exceptionFactory->createQuoteRequestValidationException();
    }
}
