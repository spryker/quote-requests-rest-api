<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Exception;

use Generated\Shared\Transfer\RestErrorMessageTransfer;
use Spryker\ApiPlatform\Exception\GlueApiException;
use Spryker\Glue\QuoteRequestsRestApi\QuoteRequestsRestApiConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * Builds pre-configured `GlueApiException` instances for every quote-request error scenario.
 *
 * Uses {@see QuoteRequestsRestApiConfig::getErrorIdentifierToRestErrorMapping()} as the source of
 * truth for glossary-key → REST error translation, keeping JSON:API responses byte-equivalent
 * to the legacy stack.
 */
class QuoteRequestsExceptionFactory
{
    public function __construct(
        protected QuoteRequestsRestApiConfig $quoteRequestsRestApiConfig,
    ) {
    }

    public function createQuoteRequestNotFoundException(): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_NOT_FOUND,
            QuoteRequestsRestApiConfig::RESPONSE_CODE_QUOTE_REQUEST_NOT_FOUND,
            QuoteRequestsRestApiConfig::RESPONSE_DETAIL_QUOTE_REQUEST_NOT_FOUND,
        );
    }

    public function createQuoteRequestReferenceMissingException(): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_BAD_REQUEST,
            QuoteRequestsRestApiConfig::RESPONSE_CODE_QUOTE_REQUEST_REFERENCE_MISSING,
            QuoteRequestsRestApiConfig::RESPONSE_DETAIL_QUOTE_REQUEST_REFERENCE_MISSING,
        );
    }

    public function createCartNotFoundException(): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_NOT_FOUND,
            QuoteRequestsRestApiConfig::RESPONSE_CODE_CART_NOT_FOUND,
            QuoteRequestsRestApiConfig::EXCEPTION_MESSAGE_CART_WITH_ID_NOT_FOUND,
        );
    }

    public function createCartIsEmptyException(): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            QuoteRequestsRestApiConfig::RESPONSE_CODE_CART_IS_EMPTY,
            QuoteRequestsRestApiConfig::RESPONSE_DETAIL_CART_IS_EMPTY,
        );
    }

    public function createCompanyUserNotFoundException(): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_NOT_FOUND,
            QuoteRequestsRestApiConfig::RESPONSE_CODE_COMPANY_USER_NOT_FOUND,
            QuoteRequestsRestApiConfig::RESPONSE_DETAIL_COMPANY_USER_NOT_FOUND,
        );
    }

    /**
     * Emitted when an `id_company_user` claim resolves to no active company user — the 403 every
     * quote-request resource declares.
     */
    public function createCompanyUserNotSelectedException(): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_FORBIDDEN,
            QuoteRequestsRestApiConfig::RESPONSE_CODE_COMPANY_USER_NOT_SELECTED,
            QuoteRequestsRestApiConfig::RESPONSE_DETAIL_COMPANY_USER_NOT_SELECTED,
        );
    }

    public function createQuoteRequestWrongStatusException(): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            QuoteRequestsRestApiConfig::RESPONSE_CODE_QUOTE_REQUEST_WRONG_STATUS,
            QuoteRequestsRestApiConfig::RESPONSE_DETAIL_QUOTE_REQUEST_WRONG_STATUS,
        );
    }

    public function createConcurrentCustomersException(): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            QuoteRequestsRestApiConfig::RESPONSE_CODE_QUOTE_REQUEST_CONCURRENT_CUSTOMERS,
            QuoteRequestsRestApiConfig::RESPONSE_DETAIL_QUOTE_REQUEST_CONCURRENT_CUSTOMERS,
        );
    }

    public function createQuoteRequestValidationException(): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            QuoteRequestsRestApiConfig::RESPONSE_CODE_QUOTE_REQUEST_VALIDATION,
            QuoteRequestsRestApiConfig::RESPONSE_DETAIL_QUOTE_REQUEST_VALIDATION,
        );
    }

    public function createMetadataDeliveryDateInvalidException(): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            QuoteRequestsRestApiConfig::RESPONSE_CODE_METADATA_DELIVERY_DATE_IS_INVALID,
            QuoteRequestsRestApiConfig::RESPONSE_DETAILS_METADATA_DELIVERY_DATE_IS_INVALID,
        );
    }

    /**
     * Builds a `GlueApiException` from a glossary-key carried in a `MessageTransfer.value`.
     * Looks the key up in {@see QuoteRequestsRestApiConfig::getErrorIdentifierToRestErrorMapping()}
     * and falls back to the supplied default `(status, code, detail)` triple when no mapping matches.
     */
    public function createExceptionFromErrorIdentifier(
        ?string $errorIdentifier,
        int $fallbackStatus = Response::HTTP_UNPROCESSABLE_ENTITY,
        string $fallbackCode = QuoteRequestsRestApiConfig::RESPONSE_CODE_QUOTE_REQUEST_VALIDATION,
        string $fallbackDetail = QuoteRequestsRestApiConfig::RESPONSE_DETAIL_QUOTE_REQUEST_VALIDATION,
    ): GlueApiException {
        if ($errorIdentifier === null) {
            return new GlueApiException($fallbackStatus, $fallbackCode, $fallbackDetail);
        }

        $mapping = $this->quoteRequestsRestApiConfig->getErrorIdentifierToRestErrorMapping();

        if (!isset($mapping[$errorIdentifier])) {
            return new GlueApiException($fallbackStatus, $fallbackCode, $fallbackDetail);
        }

        $entry = $mapping[$errorIdentifier];

        return new GlueApiException(
            (int)$entry[RestErrorMessageTransfer::STATUS],
            (string)$entry[RestErrorMessageTransfer::CODE],
            (string)$entry[RestErrorMessageTransfer::DETAIL],
        );
    }
}
