<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Processor;

use DateTime;
use Generated\Api\Storefront\QuoteRequestsStorefrontResource;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\QuoteRequestFilterTransfer;
use Generated\Shared\Transfer\QuoteRequestTransfer;
use Generated\Shared\Transfer\QuoteRequestVersionTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Client\CompanyUser\CompanyUserClientInterface;
use Spryker\Client\QuoteRequest\QuoteRequestClientInterface;
use Spryker\Client\QuoteRequestsRestApi\QuoteRequestsRestApiClientInterface;
use Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Exception\QuoteRequestsExceptionFactory;
use Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Mapper\QuoteRequestResourceMapper;
use Spryker\Service\Serializer\SerializerServiceInterface;

class QuoteRequestsStorefrontProcessor extends AbstractQuoteRequestStorefrontProcessor
{
    protected const string KEY_DELIVERY_DATE = 'delivery_date';

    /**
     * `QuoteRequestsRestApiClient::createQuoteRequest`/`updateQuoteRequest` is the Glue-side wrapper
     * that delegates to the Zed-side `QuoteRequestsRestApiFacade`. The Zed facade resolves the
     * cart by UUID via the `CartsRestApiFacade` bridge before delegating to Spryker's
     * `QuoteRequestFacade`. So Glue passes only `{cartUuid, customer}` on the quote — no Glue-side
     * cart lookup needed (matches legacy controller behaviour).
     *
     * The generic `QuoteRequestClient` (in the parent abstract) is kept for read/cancel/revise/
     * sendToUser/convertToQuote flows that don't touch a cart.
     */
    public function __construct(
        QuoteRequestClientInterface $quoteRequestClient,
        SerializerServiceInterface $serializer,
        QuoteRequestsExceptionFactory $exceptionFactory,
        protected QuoteRequestResourceMapper $quoteRequestResourceMapper,
        protected QuoteRequestsRestApiClientInterface $quoteRequestsRestApiClient,
        protected CompanyUserClientInterface $companyUserClient,
    ) {
        parent::__construct($quoteRequestClient, $serializer, $exceptionFactory);
    }

    /**
     * @param \Generated\Api\Storefront\QuoteRequestsStorefrontResource $data
     */
    protected function processPost(mixed $data): mixed
    {
        $companyUserTransfer = $this->resolveFullCompanyUser($this->resolveCompanyUser());

        $resourceData = $this->serializer->normalize($data);
        assert(is_array($resourceData));
        $quoteRequestTransfer = $this->mapResourceDataToTransfer($resourceData, new QuoteRequestTransfer());

        $quoteRequestTransfer->setCompanyUser($companyUserTransfer);

        $this->validateDeliveryDate($quoteRequestTransfer);

        if ($data->cartUuid !== null) {
            $quoteRequestTransfer->getLatestVersionOrFail()->setQuote(
                $this->buildQuoteForCartLookup($data->cartUuid, $companyUserTransfer),
            );
        }

        $quoteRequestResponseTransfer = $this->quoteRequestsRestApiClient->createQuoteRequest($quoteRequestTransfer);

        $this->assertSuccessful($quoteRequestResponseTransfer);

        return $this->quoteRequestResourceMapper->denormalizeQuoteRequestResource(
            $this->reloadQuoteRequest($quoteRequestResponseTransfer->getQuoteRequestOrFail(), $companyUserTransfer),
            $this->getLocale()->getLocaleNameOrFail(),
            QuoteRequestsStorefrontResource::class,
        );
    }

    /**
     * @param \Generated\Api\Storefront\QuoteRequestsStorefrontResource $data
     *
     * @throws \Spryker\ApiPlatform\Exception\GlueApiException
     */
    protected function processPatch(mixed $data): mixed
    {
        $quoteRequestReference = $this->getUriVariables()['quoteRequestReference'] ?? null;
        $companyUserTransfer = $this->resolveFullCompanyUser($this->resolveCompanyUser());

        $quoteRequestFilterTransfer = (new QuoteRequestFilterTransfer())
            ->setQuoteRequestReference($quoteRequestReference)
            ->setCompanyUser($companyUserTransfer)
            ->setWithVersions(true);

        $quoteRequestResponseTransfer = $this->quoteRequestClient->getQuoteRequest($quoteRequestFilterTransfer);

        if (!$quoteRequestResponseTransfer->getIsSuccessful()) {
            throw $this->exceptionFactory->createQuoteRequestNotFoundException();
        }

        $resourceData = $this->serializer->normalize($data);
        assert(is_array($resourceData));
        $quoteRequestTransfer = $this->mapResourceDataToTransfer(
            $resourceData,
            $quoteRequestResponseTransfer->getQuoteRequestOrFail(),
        );

        $this->validateDeliveryDate($quoteRequestTransfer);

        if ($data->cartUuid !== null) {
            $quoteRequestTransfer->getLatestVersionOrFail()->setQuote(
                $this->buildQuoteForCartLookup($data->cartUuid, $companyUserTransfer),
            );
        }

        $quoteRequestResponseTransfer = $this->quoteRequestsRestApiClient->updateQuoteRequest($quoteRequestTransfer);

        $this->assertSuccessful($quoteRequestResponseTransfer);

        return $this->quoteRequestResourceMapper->denormalizeQuoteRequestResource(
            $this->reloadQuoteRequest($quoteRequestResponseTransfer->getQuoteRequestOrFail(), $companyUserTransfer),
            $this->getLocale()->getLocaleNameOrFail(),
            QuoteRequestsStorefrontResource::class,
        );
    }

    /**
     * Re-reads the just-created/updated quote request via `QuoteRequestClient::getQuoteRequest`
     * so the response carries the same enriched `companyUser` (with `uuid`, `companyBusinessUnit`,
     * `company`) that the read flow produces. Without this re-read the write response has only
     * `idCompanyUser` on the company user and the JSON:API `relationships` for `company-users`/
     * `company-business-units` resolve to nothing.
     *
     * Falls back to the original write-response transfer if the re-read fails for any reason,
     * so a successful create is never demoted to an error.
     */
    protected function reloadQuoteRequest(
        QuoteRequestTransfer $quoteRequestTransfer,
        ?CompanyUserTransfer $companyUserTransfer,
    ): QuoteRequestTransfer {
        $quoteRequestReference = $quoteRequestTransfer->getQuoteRequestReference();

        if ($quoteRequestReference === null) {
            return $quoteRequestTransfer;
        }

        $reloadResponseTransfer = $this->quoteRequestClient->getQuoteRequest(
            (new QuoteRequestFilterTransfer())
                ->setQuoteRequestReference($quoteRequestReference)
                ->setCompanyUser($companyUserTransfer)
                ->setWithVersions(true),
        );

        return $reloadResponseTransfer->getIsSuccessful() && $reloadResponseTransfer->getQuoteRequest() !== null
            ? $reloadResponseTransfer->getQuoteRequestOrFail()
            : $quoteRequestTransfer;
    }

    /**
     * @param array<string, mixed> $resourceData
     */
    protected function mapResourceDataToTransfer(array $resourceData, QuoteRequestTransfer $quoteRequestTransfer): QuoteRequestTransfer
    {
        $quoteRequestVersionTransfer = $quoteRequestTransfer->getLatestVersion() ?? new QuoteRequestVersionTransfer();

        if (($resourceData['metadata'] ?? []) !== []) {
            $quoteRequestVersionTransfer->setMetadata($resourceData['metadata']);
        }

        return $quoteRequestTransfer->setLatestVersion($quoteRequestVersionTransfer);
    }

    protected function resolveFullCompanyUser(?CompanyUserTransfer $companyUserTransfer): ?CompanyUserTransfer
    {
        if ($companyUserTransfer === null || $companyUserTransfer->getIdCompanyUser() !== null) {
            return $companyUserTransfer;
        }

        $customerTransfer = $companyUserTransfer->getCustomer() ?? new CustomerTransfer();
        $companyUserCollectionTransfer = $this->companyUserClient->getActiveCompanyUsersByCustomerReference($customerTransfer);

        foreach ($companyUserCollectionTransfer->getCompanyUsers() as $resolvedCompanyUserTransfer) {
            if ($resolvedCompanyUserTransfer->getUuid() === $companyUserTransfer->getUuid()) {
                return $resolvedCompanyUserTransfer;
            }
        }

        return $companyUserTransfer;
    }

    /**
     * Builds the minimal `QuoteTransfer` shape that Zed `QuoteRequestCreator` expects:
     * `uuid` + `customerReference` + `customer` (with the resolved `companyUserTransfer` set so
     * permission checks downstream can see who the company user is). Zed resolves the full cart
     * via `CartsRestApiFacade::findQuoteByUuid` from this.
     *
     * `CustomerTransfer` is taken from the request (populated by the storefront identity
     * subscriber chain) — `customerReference` is required by Zed and is NOT carried on
     * `CompanyUserTransfer.customer` (the company-user subscriber only sets the company-user
     * identifiers, not the nested customer).
     */
    protected function buildQuoteForCartLookup(string $cartUuid, ?CompanyUserTransfer $companyUserTransfer): QuoteTransfer
    {
        // Build a detached customer copy so we do not mutate the request-scoped CustomerTransfer
        // (which the API Platform relationship resolver re-reads after our processor returns;
        // overwriting `companyUserTransfer` here would strip its `uuid`/`fkCompany` and break
        // owner-checks on the `company-users` / `company-business-units` relationships).
        $requestCustomerTransfer = $this->getCustomer();
        $customerTransfer = (new CustomerTransfer())
            ->setIdCustomer($requestCustomerTransfer->getIdCustomer())
            ->setCustomerReference($requestCustomerTransfer->getCustomerReference());

        if ($companyUserTransfer !== null) {
            $customerTransfer->setCompanyUserTransfer(
                (new CompanyUserTransfer())->setIdCompanyUser($companyUserTransfer->getIdCompanyUser()),
            );
        }

        return (new QuoteTransfer())
            ->setUuid($cartUuid)
            ->setCustomerReference($customerTransfer->getCustomerReference())
            ->setCustomer($customerTransfer);
    }

    /**
     * @throws \Spryker\ApiPlatform\Exception\GlueApiException
     */
    protected function validateDeliveryDate(QuoteRequestTransfer $quoteRequestTransfer): void
    {
        $latestVersion = $quoteRequestTransfer->getLatestVersion();

        if ($latestVersion === null) {
            return;
        }

        $deliveryDate = $latestVersion->getMetadata()[static::KEY_DELIVERY_DATE] ?? null;

        if ($deliveryDate === null) {
            return;
        }

        if (strtotime((string)$deliveryDate) === false) {
            throw $this->exceptionFactory->createMetadataDeliveryDateInvalidException();
        }

        if ((new DateTime())->setTime(0, 0) > new DateTime((string)$deliveryDate)) {
            throw $this->exceptionFactory->createMetadataDeliveryDateInvalidException();
        }
    }
}
