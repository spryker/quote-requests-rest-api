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
use Generated\Api\Storefront\QuoteRequestCancelStorefrontResource;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use Generated\Shared\Transfer\QuoteRequestResponseTransfer;
use Spryker\ApiPlatform\Exception\GlueApiException;
use Spryker\Client\CompanyUser\CompanyUserClientInterface;
use Spryker\Client\QuoteRequest\QuoteRequestClientInterface;
use Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Exception\QuoteRequestsExceptionFactory;
use Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Processor\QuoteRequestCancelStorefrontProcessor;
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
 * @group QuoteRequestCancelStorefrontProcessorTest
 * Add your own group annotations below this line
 */
class QuoteRequestCancelStorefrontProcessorTest extends Unit
{
    protected const string GLOSSARY_KEY_QUOTE_REQUEST_NOT_EXISTS = 'quote_request.validation.error.not_exists';

    protected const string GLOSSARY_KEY_QUOTE_REQUEST_WRONG_STATUS = 'quote_request.validation.error.wrong_status';

    protected const string QUOTE_REQUEST_REFERENCE = 'DE--21-123';

    protected const string CUSTOMER_REFERENCE = 'DE--1';

    protected const int ID_COMPANY_USER = 7;

    public function testGivenQuoteRequestDoesNotExistWhenProcessPostThenThrowsNotFoundWithQuoteRequestNotFoundCode(): void
    {
        // Arrange
        $processor = $this->createProcessor(static::GLOSSARY_KEY_QUOTE_REQUEST_NOT_EXISTS);

        // Act
        $exception = $this->processAndCatch($processor);

        // Assert
        $this->assertSame(Response::HTTP_NOT_FOUND, $exception->getStatusCode());
        $this->assertSame(QuoteRequestsRestApiConfig::RESPONSE_CODE_QUOTE_REQUEST_NOT_FOUND, $exception->getErrorCode());
        $this->assertSame(QuoteRequestsRestApiConfig::RESPONSE_DETAIL_QUOTE_REQUEST_NOT_FOUND, $exception->getMessage());
    }

    public function testGivenQuoteRequestHasWrongStatusWhenProcessPostThenStillThrowsUnprocessableEntity(): void
    {
        // Arrange
        $processor = $this->createProcessor(static::GLOSSARY_KEY_QUOTE_REQUEST_WRONG_STATUS);

        // Act
        $exception = $this->processAndCatch($processor);

        // Assert
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getStatusCode());
        $this->assertSame(QuoteRequestsRestApiConfig::RESPONSE_CODE_QUOTE_REQUEST_WRONG_STATUS, $exception->getErrorCode());
    }

    protected function createProcessor(string $errorIdentifier): QuoteRequestCancelStorefrontProcessor
    {
        $quoteRequestResponseTransfer = (new QuoteRequestResponseTransfer())
            ->setIsSuccessful(false)
            ->addMessage((new MessageTransfer())->setValue($errorIdentifier));

        return new QuoteRequestCancelStorefrontProcessor(
            Stub::makeEmpty(QuoteRequestClientInterface::class, [
                'cancelQuoteRequest' => $quoteRequestResponseTransfer,
            ]),
            Stub::makeEmpty(SerializerServiceInterface::class),
            new QuoteRequestsExceptionFactory(new QuoteRequestsRestApiConfig()),
            Stub::makeEmpty(CompanyUserClientInterface::class),
        );
    }

    protected function processAndCatch(QuoteRequestCancelStorefrontProcessor $processor): GlueApiException
    {
        try {
            $processor->process(
                new QuoteRequestCancelStorefrontResource(),
                new Post(class: QuoteRequestCancelStorefrontResource::class),
                ['quoteRequestReference' => static::QUOTE_REQUEST_REFERENCE],
                ['request' => $this->createRequest()],
            );
        } catch (GlueApiException $exception) {
            return $exception;
        }

        $this->fail('Expected a GlueApiException to be thrown.');
    }

    protected function createRequest(): Request
    {
        $customerTransfer = (new CustomerTransfer())
            ->setCustomerReference(static::CUSTOMER_REFERENCE)
            ->setCompanyUserTransfer((new CompanyUserTransfer())->setIdCompanyUser(static::ID_COMPANY_USER));

        $request = new Request();
        $request->attributes->set('CustomerTransfer', $customerTransfer);

        return $request;
    }
}
