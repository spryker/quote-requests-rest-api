<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\QuoteRequestsRestApi\Processor\RestResponseBuilder;

use Generated\Shared\Transfer\QuoteRequestCollectionTransfer;
use Generated\Shared\Transfer\QuoteRequestResponseTransfer;
use Generated\Shared\Transfer\QuoteResponseTransfer;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface;
use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;

interface QuoteRequestRestResponseBuilderInterface
{
    public function createFailedErrorResponse(QuoteRequestResponseTransfer $quoteRequestResponseTransfer): RestResponseInterface;

    public function createFailedQuoteErrorResponse(QuoteResponseTransfer $quoteResponseTransfer): RestResponseInterface;

    public function createQuoteRequestRestResponse(
        QuoteRequestResponseTransfer $quoteRequestResponseTransfer,
        RestRequestInterface $restRequest,
        bool $isLatestVersionVisible = true
    ): RestResponseInterface;

    public function createQuoteRequestCollectionRestResponse(
        QuoteRequestCollectionTransfer $quoteRequestCollectionTransfer,
        RestRequestInterface $restRequest,
        bool $isLatestVersionVisible = true
    ): RestResponseInterface;

    public function createQuoteRequestNotFoundErrorResponse(): RestResponseInterface;

    public function createDeliveryDateIsNotValidErrorResponse(): RestResponseInterface;

    public function createQuoteRequestReferenceMissingErrorResponse(): RestResponseInterface;
}
