<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerTest\Glue\QuoteRequestsRestApi\StorefrontApi;

use ApiPlatform\Metadata\Post;
use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Api\Storefront\QuoteRequestsStorefrontResource;
use Generated\Shared\Transfer\CompanyUserCollectionTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Spryker\ApiPlatform\Exception\GlueApiException;
use Spryker\Client\CompanyUser\CompanyUserClientInterface;
use Spryker\Client\QuoteRequest\QuoteRequestClientInterface;
use Spryker\Client\QuoteRequestsRestApi\QuoteRequestsRestApiClientInterface;
use Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Exception\QuoteRequestsExceptionFactory;
use Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Mapper\QuoteRequestResourceMapper;
use Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Processor\QuoteRequestsStorefrontProcessor;
use Spryker\Glue\QuoteRequestsRestApi\QuoteRequestsRestApiConfig;
use Spryker\Service\Serializer\SerializerServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Glue
 * @group QuoteRequestsRestApi
 * @group StorefrontApi
 * @group QuoteRequestsStorefrontProcessorTest
 * Add your own group annotations below this line
 */
class QuoteRequestsStorefrontProcessorTest extends Unit
{
    protected const string COMPANY_USER_UUID = '11111111-2222-3333-4444-555555555555';

    protected const string OTHER_COMPANY_USER_UUID = '99999999-8888-7777-6666-555555555555';

    protected const string CUSTOMER_REFERENCE = 'DE--1';

    protected const int ID_COMPANY_USER = 7;

    public function testGivenCompanyUserCannotBeResolvedWhenProcessPostThenThrowsForbidden(): void
    {
        // Arrange
        $companyUserCollectionTransfer = (new CompanyUserCollectionTransfer())
            ->addCompanyUser(
                (new CompanyUserTransfer())
                    ->setUuid(static::OTHER_COMPANY_USER_UUID)
                    ->setIdCompanyUser(static::ID_COMPANY_USER),
            );

        $processor = $this->createProcessor($companyUserCollectionTransfer);

        // Act
        $exception = $this->processAndCatch($processor, $this->createRequest());

        // Assert
        $this->assertSame(Response::HTTP_FORBIDDEN, $exception->getStatusCode());
        $this->assertSame(QuoteRequestsRestApiConfig::RESPONSE_CODE_COMPANY_USER_NOT_SELECTED, $exception->getErrorCode());
        $this->assertSame(QuoteRequestsRestApiConfig::RESPONSE_DETAIL_COMPANY_USER_NOT_SELECTED, $exception->getMessage());
    }

    public function testGivenCustomerIsNotACompanyUserWhenProcessPostThenThrowsForbidden(): void
    {
        // Arrange
        $processor = $this->createProcessor(new CompanyUserCollectionTransfer());
        $request = $this->createRequestForCustomer(
            (new CustomerTransfer())->setCustomerReference(static::CUSTOMER_REFERENCE),
        );

        // Act
        $exception = $this->processAndCatch($processor, $request);

        // Assert
        $this->assertSame(Response::HTTP_FORBIDDEN, $exception->getStatusCode());
        $this->assertSame(QuoteRequestsRestApiConfig::RESPONSE_CODE_COMPANY_USER_NOT_SELECTED, $exception->getErrorCode());
        $this->assertSame(QuoteRequestsRestApiConfig::RESPONSE_DETAIL_COMPANY_USER_NOT_SELECTED, $exception->getMessage());
    }

    public function testGivenCustomerReferenceIsMissingWhenProcessPostThenThrowsForbidden(): void
    {
        // Arrange
        $processor = $this->createProcessor(new CompanyUserCollectionTransfer());
        $request = $this->createRequestForCustomer(
            (new CustomerTransfer())->setCompanyUserTransfer(
                (new CompanyUserTransfer())->setUuid(static::COMPANY_USER_UUID),
            ),
        );

        // Act
        $exception = $this->processAndCatch($processor, $request);

        // Assert
        $this->assertSame(Response::HTTP_FORBIDDEN, $exception->getStatusCode());
        $this->assertSame(QuoteRequestsRestApiConfig::RESPONSE_CODE_COMPANY_USER_NOT_SELECTED, $exception->getErrorCode());
        $this->assertSame(QuoteRequestsRestApiConfig::RESPONSE_DETAIL_COMPANY_USER_NOT_SELECTED, $exception->getMessage());
    }

    protected function processAndCatch(QuoteRequestsStorefrontProcessor $processor, Request $request): GlueApiException
    {
        try {
            $processor->process(
                new QuoteRequestsStorefrontResource(),
                new Post(class: QuoteRequestsStorefrontResource::class),
                [],
                ['request' => $request],
            );
        } catch (GlueApiException $exception) {
            return $exception;
        }

        $this->fail('Expected a GlueApiException to be thrown.');
    }

    protected function createProcessor(CompanyUserCollectionTransfer $companyUserCollectionTransfer): QuoteRequestsStorefrontProcessor
    {
        return new QuoteRequestsStorefrontProcessor(
            Stub::makeEmpty(QuoteRequestClientInterface::class),
            Stub::makeEmpty(SerializerServiceInterface::class),
            new QuoteRequestsExceptionFactory(new QuoteRequestsRestApiConfig()),
            Stub::makeEmpty(QuoteRequestResourceMapper::class),
            Stub::makeEmpty(QuoteRequestsRestApiClientInterface::class),
            $this->createCompanyUserClientStub($companyUserCollectionTransfer),
        );
    }

    /**
     * The real client calls `requireCustomerReference()` first, so a reference-less transfer throws.
     */
    protected function createCompanyUserClientStub(
        CompanyUserCollectionTransfer $companyUserCollectionTransfer,
    ): CompanyUserClientInterface {
        return Stub::makeEmpty(CompanyUserClientInterface::class, [
            'getActiveCompanyUsersByCustomerReference' => function (
                CustomerTransfer $customerTransfer
            ) use ($companyUserCollectionTransfer): CompanyUserCollectionTransfer {
                $customerTransfer->requireCustomerReference();

                return $companyUserCollectionTransfer;
            },
        ]);
    }

    protected function createRequest(): Request
    {
        return $this->createRequestForCustomer(
            (new CustomerTransfer())
                ->setCustomerReference(static::CUSTOMER_REFERENCE)
                ->setCompanyUserTransfer((new CompanyUserTransfer())->setUuid(static::COMPANY_USER_UUID)),
        );
    }

    protected function createRequestForCustomer(CustomerTransfer $customerTransfer): Request
    {
        $request = new Request();
        $request->attributes->set('CustomerTransfer', $customerTransfer);

        return $request;
    }
}
