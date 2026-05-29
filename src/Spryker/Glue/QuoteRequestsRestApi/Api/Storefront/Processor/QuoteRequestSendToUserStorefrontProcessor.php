<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Processor;

use Spryker\Client\QuoteRequest\QuoteRequestClientInterface;
use Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Exception\QuoteRequestsExceptionFactory;
use Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Mapper\QuoteRequestResourceMapper;
use Spryker\Service\Serializer\SerializerServiceInterface;

class QuoteRequestSendToUserStorefrontProcessor extends AbstractQuoteRequestStorefrontProcessor
{
    public function __construct(
        QuoteRequestClientInterface $quoteRequestClient,
        SerializerServiceInterface $serializer,
        QuoteRequestsExceptionFactory $exceptionFactory,
        protected QuoteRequestResourceMapper $quoteRequestResourceMapper,
    ) {
        parent::__construct($quoteRequestClient, $serializer, $exceptionFactory);
    }

    /**
     * @param \Generated\Api\Storefront\QuoteRequestSendToUserStorefrontResource $data
     */
    protected function processPost(mixed $data): mixed
    {
        $quoteRequestResponseTransfer = $this->quoteRequestClient->sendQuoteRequestToUser(
            $this->buildQuoteRequestActionFilter(),
        );

        $this->assertSuccessful($quoteRequestResponseTransfer);

        return $this->quoteRequestResourceMapper->buildQuoteRequestSendToUserStorefrontResource(
            $quoteRequestResponseTransfer->getQuoteRequestOrFail(),
            $this->getLocale()->getLocaleNameOrFail(),
        );
    }
}
