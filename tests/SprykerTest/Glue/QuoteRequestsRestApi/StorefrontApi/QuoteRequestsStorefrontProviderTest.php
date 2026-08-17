<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerTest\Glue\QuoteRequestsRestApi\StorefrontApi;

use ApiPlatform\Metadata\GetCollection;
use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Api\Storefront\QuoteRequestsStorefrontResource;
use Generated\Shared\Transfer\CompanyUserCollectionTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\QuoteRequestCollectionTransfer;
use Generated\Shared\Transfer\QuoteRequestFilterTransfer;
use Spryker\ApiPlatform\Exception\GlueApiException;
use Spryker\Client\CompanyUser\CompanyUserClientInterface;
use Spryker\Client\QuoteRequest\QuoteRequestClientInterface;
use Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Exception\QuoteRequestsExceptionFactory;
use Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Mapper\QuoteRequestResourceMapper;
use Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Provider\QuoteRequestsStorefrontProvider;
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
 * @group QuoteRequestsStorefrontProviderTest
 * Add your own group annotations below this line
 */
class QuoteRequestsStorefrontProviderTest extends Unit
{
    protected const string COMPANY_USER_UUID = '11111111-2222-3333-4444-555555555555';

    protected const string OTHER_COMPANY_USER_UUID = '99999999-8888-7777-6666-555555555555';

    protected const string CUSTOMER_REFERENCE = 'DE--1';

    protected const int ID_COMPANY_USER = 7;

    public function testGivenCompanyUserHasNoStorageRecordWhenProvideCollectionThenResolvesItFromTheDatabase(): void
    {
        // Arrange
        $capturedFilterTransfer = null;
        $companyUserCollectionTransfer = (new CompanyUserCollectionTransfer())
            ->addCompanyUser(
                (new CompanyUserTransfer())
                    ->setUuid(static::COMPANY_USER_UUID)
                    ->setIdCompanyUser(static::ID_COMPANY_USER),
            );

        $provider = $this->createProvider(
            $companyUserCollectionTransfer,
            function (QuoteRequestFilterTransfer $quoteRequestFilterTransfer) use (&$capturedFilterTransfer): QuoteRequestCollectionTransfer {
                $capturedFilterTransfer = $quoteRequestFilterTransfer;

                return new QuoteRequestCollectionTransfer();
            },
        );

        // Act
        $resources = $provider->provide(
            new GetCollection(class: QuoteRequestsStorefrontResource::class),
            [],
            ['request' => $this->createRequest()],
        );

        // Assert
        $this->assertSame([], $resources);
        $this->assertSame(
            static::ID_COMPANY_USER,
            $capturedFilterTransfer?->getCompanyUser()?->getIdCompanyUser(),
        );
    }

    public function testGivenCompanyUserCannotBeResolvedWhenProvideCollectionThenThrowsForbidden(): void
    {
        // Arrange
        $companyUserCollectionTransfer = (new CompanyUserCollectionTransfer())
            ->addCompanyUser(
                (new CompanyUserTransfer())
                    ->setUuid(static::OTHER_COMPANY_USER_UUID)
                    ->setIdCompanyUser(static::ID_COMPANY_USER),
            );

        $provider = $this->createProvider(
            $companyUserCollectionTransfer,
            fn (): QuoteRequestCollectionTransfer => new QuoteRequestCollectionTransfer(),
        );

        // Act
        try {
            $provider->provide(
                new GetCollection(class: QuoteRequestsStorefrontResource::class),
                [],
                ['request' => $this->createRequest()],
            );
            $this->fail('Expected a GlueApiException to be thrown.');
        } catch (GlueApiException $exception) {
            // Assert
            $this->assertSame(Response::HTTP_FORBIDDEN, $exception->getStatusCode());
            $this->assertSame(QuoteRequestsRestApiConfig::RESPONSE_CODE_COMPANY_USER_NOT_SELECTED, $exception->getErrorCode());
            $this->assertSame(QuoteRequestsRestApiConfig::RESPONSE_DETAIL_COMPANY_USER_NOT_SELECTED, $exception->getMessage());
        }
    }

    protected function createProvider(
        CompanyUserCollectionTransfer $companyUserCollectionTransfer,
        callable $getQuoteRequestCollectionByFilter,
    ): QuoteRequestsStorefrontProvider {
        return new QuoteRequestsStorefrontProvider(
            Stub::makeEmpty(QuoteRequestClientInterface::class, [
                'getQuoteRequestCollectionByFilter' => $getQuoteRequestCollectionByFilter,
            ]),
            Stub::makeEmpty(SerializerServiceInterface::class),
            $this->createCompanyUserClientStub($companyUserCollectionTransfer),
            new QuoteRequestsExceptionFactory(new QuoteRequestsRestApiConfig()),
            Stub::makeEmpty(QuoteRequestResourceMapper::class),
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
        $customerTransfer = (new CustomerTransfer())
            ->setCustomerReference(static::CUSTOMER_REFERENCE)
            ->setCompanyUserTransfer((new CompanyUserTransfer())->setUuid(static::COMPANY_USER_UUID));

        $request = new Request();
        $request->attributes->set('CustomerTransfer', $customerTransfer);

        return $request;
    }
}
