<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\QuoteRequestsRestApi\Zed;

use Generated\Shared\Transfer\QuoteRequestResponseTransfer;
use Generated\Shared\Transfer\QuoteRequestsRequestTransfer;
use Spryker\Client\QuoteRequestsRestApi\Dependency\Client\QuoteRequestsRestApiToZedRequestClientInterface;

class QuoteRequestsRestApiZedStub implements QuoteRequestsRestApiZedStubInterface
{
    /**
     * @var \Spryker\Client\QuoteRequestsRestApi\Dependency\Client\QuoteRequestsRestApiToZedRequestClientInterface
     */
    protected $zedRequestClient;

    /**
     * @param \Spryker\Client\QuoteRequestsRestApi\Dependency\Client\QuoteRequestsRestApiToZedRequestClientInterface $zedRequestClient
     */
    public function __construct(QuoteRequestsRestApiToZedRequestClientInterface $zedRequestClient)
    {
        $this->zedRequestClient = $zedRequestClient;
    }

    /**
     * @uses \Spryker\Zed\QuoteRequestsRestApi\Communication\Controller\GatewayController::createQuoteRequestAction()
     *
     * @param \Generated\Shared\Transfer\QuoteRequestsRequestTransfer $quoteRequestsRequestTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteRequestResponseTransfer
     */
    public function createQuoteRequest(
        QuoteRequestsRequestTransfer $quoteRequestsRequestTransfer
    ): QuoteRequestResponseTransfer {
        /** @var \Generated\Shared\Transfer\QuoteRequestResponseTransfer $quoteRequestResponseTransfer */
        $quoteRequestResponseTransfer = $this->zedRequestClient->call('/quote-requests-rest-api/gateway/create-quote-request', $quoteRequestsRequestTransfer);

        return $quoteRequestResponseTransfer;
    }
}
