<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Provider;

use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\QuoteRequestFilterTransfer;
use Spryker\ApiPlatform\State\Provider\AbstractStorefrontProvider;
use Spryker\Client\CompanyUser\CompanyUserClientInterface;
use Spryker\Client\QuoteRequest\QuoteRequestClientInterface;
use Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Exception\QuoteRequestsExceptionFactory;
use Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Mapper\QuoteRequestResourceMapper;
use Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Resolver\CompanyUserResolverTrait;
use Spryker\Service\Serializer\SerializerServiceInterface;

class QuoteRequestsStorefrontProvider extends AbstractStorefrontProvider
{
    use CompanyUserResolverTrait;

    public function __construct(
        protected QuoteRequestClientInterface $quoteRequestClient,
        protected SerializerServiceInterface $serializer,
        protected CompanyUserClientInterface $companyUserClient,
        protected QuoteRequestsExceptionFactory $exceptionFactory,
        protected QuoteRequestResourceMapper $quoteRequestResourceMapper,
    ) {
    }

    /**
     * @return array<\Generated\Api\Storefront\QuoteRequestsStorefrontResource>
     */
    protected function provideCollection(): array
    {
        $companyUserTransfer = $this->resolveFullCompanyUser($this->resolveCompanyUser());

        $quoteRequestFilterTransfer = (new QuoteRequestFilterTransfer())
            ->setCompanyUser($companyUserTransfer)
            ->setWithVersions(true)
            ->setPagination($this->buildPaginationTransfer());

        $quoteRequestCollectionTransfer = $this->quoteRequestClient
            ->getQuoteRequestCollectionByFilter($quoteRequestFilterTransfer);

        $resources = [];

        foreach ($quoteRequestCollectionTransfer->getQuoteRequests() as $quoteRequestTransfer) {
            $resources[] = $this->quoteRequestResourceMapper->buildQuoteRequestsStorefrontResource(
                $quoteRequestTransfer,
                $this->getLocale()->getLocaleNameOrFail(),
            );
        }

        return $resources;
    }

    /**
     * @throws \Spryker\ApiPlatform\Exception\GlueApiException
     */
    protected function provideItem(): ?object
    {
        $quoteRequestReference = $this->getUriVariables()['quoteRequestReference'] ?? null;

        if ($quoteRequestReference === null) {
            return null;
        }

        $companyUserTransfer = $this->resolveFullCompanyUser($this->resolveCompanyUser());

        $quoteRequestFilterTransfer = (new QuoteRequestFilterTransfer())
            ->setQuoteRequestReference($quoteRequestReference)
            ->setCompanyUser($companyUserTransfer)
            ->setWithVersions(true);

        $quoteRequestResponseTransfer = $this->quoteRequestClient->getQuoteRequest($quoteRequestFilterTransfer);

        if (!$quoteRequestResponseTransfer->getIsSuccessful()) {
            throw $this->exceptionFactory->createQuoteRequestNotFoundException();
        }

        return $this->quoteRequestResourceMapper->buildQuoteRequestsStorefrontResource(
            $quoteRequestResponseTransfer->getQuoteRequestOrFail(),
            $this->getLocale()->getLocaleNameOrFail(),
        );
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
}
