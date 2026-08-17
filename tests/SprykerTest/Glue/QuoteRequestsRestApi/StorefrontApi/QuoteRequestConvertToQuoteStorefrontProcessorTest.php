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
use Generated\Api\Storefront\QuoteRequestConvertToQuoteStorefrontResource;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\QuoteErrorTransfer;
use Generated\Shared\Transfer\QuoteRequestFilterTransfer;
use Generated\Shared\Transfer\QuoteRequestResponseTransfer;
use Generated\Shared\Transfer\QuoteRequestTransfer;
use Generated\Shared\Transfer\QuoteResponseTransfer;
use Spryker\ApiPlatform\Exception\GlueApiException;
use Spryker\Client\CompanyUser\CompanyUserClientInterface;
use Spryker\Client\QuoteRequest\QuoteRequestClientInterface;
use Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Exception\QuoteRequestsExceptionFactory;
use Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Processor\QuoteRequestConvertToQuoteStorefrontProcessor;
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
 * @group QuoteRequestConvertToQuoteStorefrontProcessorTest
 * Add your own group annotations below this line
 */
class QuoteRequestConvertToQuoteStorefrontProcessorTest extends Unit
{
    /**
     * @uses \Spryker\Client\QuoteRequest\Converter\QuoteRequestConverter::GLOSSARY_KEY_WRONG_QUOTE_REQUEST_STATUS
     */
    protected const string GLOSSARY_KEY_WRONG_QUOTE_REQUEST_STATUS = 'quote_request.checkout.validation.error.wrong_status';

    /**
     * @uses \Spryker\Client\QuoteRequest\Converter\QuoteRequestConverter::GLOSSARY_KEY_WRONG_CONVERT_QUOTE_REQUEST_VALID_UNTIL
     */
    protected const string GLOSSARY_KEY_WRONG_CONVERT_QUOTE_REQUEST_VALID_UNTIL = 'quote_request.checkout.convert.error.wrong_valid_until';

    protected const string QUOTE_REQUEST_REFERENCE = 'DE--21-123';

    protected const string CUSTOMER_REFERENCE = 'DE--1';

    protected const int ID_COMPANY_USER = 7;

    protected ?QuoteRequestFilterTransfer $capturedFilterTransfer = null;

    public function testGivenConverterReportsWrongStatusInMessageWhenProcessPostThenThrowsWrongStatusError(): void
    {
        // Arrange
        $processor = $this->createProcessor(
            (new QuoteErrorTransfer())->setMessage(static::GLOSSARY_KEY_WRONG_QUOTE_REQUEST_STATUS),
        );

        // Act
        $exception = $this->processAndCatch($processor);

        // Assert
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getStatusCode());
        $this->assertSame(QuoteRequestsRestApiConfig::RESPONSE_CODE_QUOTE_REQUEST_WRONG_STATUS, $exception->getErrorCode());
        $this->assertSame(QuoteRequestsRestApiConfig::RESPONSE_DETAIL_QUOTE_REQUEST_WRONG_STATUS, $exception->getMessage());
    }

    public function testGivenConverterReportsExpiredValidUntilInMessageWhenProcessPostThenThrowsExpiredError(): void
    {
        // Arrange
        $processor = $this->createProcessor(
            (new QuoteErrorTransfer())->setMessage(static::GLOSSARY_KEY_WRONG_CONVERT_QUOTE_REQUEST_VALID_UNTIL),
        );

        // Act
        $exception = $this->processAndCatch($processor);

        // Assert
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getStatusCode());
        $this->assertSame(QuoteRequestsRestApiConfig::RESPONSE_CODE_QUOTE_REQUEST_VALIDATION, $exception->getErrorCode());
        $this->assertSame(QuoteRequestsRestApiConfig::RESPONSE_DETAIL_QUOTE_REQUEST_VALID_UNTIL_EXPIRED, $exception->getMessage());
    }

    public function testGivenConverterReportsWrongStatusInErrorIdentifierWhenProcessPostThenThrowsWrongStatusError(): void
    {
        // Arrange
        $processor = $this->createProcessor(
            (new QuoteErrorTransfer())->setErrorIdentifier(static::GLOSSARY_KEY_WRONG_QUOTE_REQUEST_STATUS),
        );

        // Act
        $exception = $this->processAndCatch($processor);

        // Assert
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getStatusCode());
        $this->assertSame(QuoteRequestsRestApiConfig::RESPONSE_CODE_QUOTE_REQUEST_WRONG_STATUS, $exception->getErrorCode());
    }

    public function testGivenCompanyUserOnTheRequestWhenProcessPostThenScopesTheQuoteRequestReadToIt(): void
    {
        // Arrange
        $processor = $this->createProcessor(
            (new QuoteErrorTransfer())->setMessage(static::GLOSSARY_KEY_WRONG_QUOTE_REQUEST_STATUS),
        );

        // Act
        $this->processAndCatch($processor);

        // Assert
        $this->assertNotNull($this->capturedFilterTransfer);
        $this->assertSame(static::ID_COMPANY_USER, $this->capturedFilterTransfer->getIdCompanyUser());
        $this->assertSame(static::ID_COMPANY_USER, $this->capturedFilterTransfer->getCompanyUser()?->getIdCompanyUser());
    }

    protected function createProcessor(QuoteErrorTransfer $quoteErrorTransfer): QuoteRequestConvertToQuoteStorefrontProcessor
    {
        $quoteRequestResponseTransfer = (new QuoteRequestResponseTransfer())
            ->setIsSuccessful(true)
            ->setQuoteRequest(
                (new QuoteRequestTransfer())->setQuoteRequestReference(static::QUOTE_REQUEST_REFERENCE),
            );

        $quoteResponseTransfer = (new QuoteResponseTransfer())
            ->setIsSuccessful(false)
            ->addError($quoteErrorTransfer);

        return new QuoteRequestConvertToQuoteStorefrontProcessor(
            Stub::makeEmpty(QuoteRequestClientInterface::class, [
                'getQuoteRequest' => function (
                    QuoteRequestFilterTransfer $quoteRequestFilterTransfer
                ) use ($quoteRequestResponseTransfer): QuoteRequestResponseTransfer {
                    $this->capturedFilterTransfer = $quoteRequestFilterTransfer;

                    return $quoteRequestResponseTransfer;
                },
                'convertQuoteRequestToLockedQuote' => $quoteResponseTransfer,
            ]),
            Stub::makeEmpty(SerializerServiceInterface::class),
            new QuoteRequestsExceptionFactory(new QuoteRequestsRestApiConfig()),
            Stub::makeEmpty(CompanyUserClientInterface::class),
        );
    }

    protected function processAndCatch(QuoteRequestConvertToQuoteStorefrontProcessor $processor): GlueApiException
    {
        try {
            $processor->process(
                new QuoteRequestConvertToQuoteStorefrontResource(),
                new Post(class: QuoteRequestConvertToQuoteStorefrontResource::class),
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
