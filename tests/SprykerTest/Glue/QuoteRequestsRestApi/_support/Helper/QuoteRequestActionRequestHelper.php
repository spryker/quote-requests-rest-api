<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerTest\Glue\QuoteRequestsRestApi\Helper;

use Codeception\Module;
use Generated\Shared\Transfer\QuoteRequestTransfer;
use SprykerTest\ApiPlatform\Helper\ApiRequestHelperTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every quote-request action resource shares one request shape: a POST to a sub-path of a single
 * quote request carrying an empty attribute object, with the resource type spelled like the path
 * segment. The agent side sends the same request under its own collection, see
 * {@see \SprykerTest\Glue\QuoteRequestAgentsRestApi\Helper\AgentQuoteRequestHelper}.
 *
 * Each action gets its own method so a test names the action it performs rather than a constant
 * spelling the path segment.
 */
class QuoteRequestActionRequestHelper extends Module
{
    use ApiRequestHelperTrait;

    protected const string ACTION_CANCEL = 'quote-request-cancel';

    protected const string ACTION_SEND_TO_USER = 'quote-request-send-to-user';

    protected const string ACTION_REVISE = 'quote-request-revise';

    protected const string ACTION_CONVERT_TO_QUOTE = 'quote-request-convert-to-quote';

    protected const string COLLECTION_QUOTE_REQUESTS = 'quote-requests';

    public function cancelQuoteRequest(QuoteRequestTransfer $quoteRequestTransfer): Response
    {
        return $this->cancelQuoteRequestByReference($this->getReference($quoteRequestTransfer));
    }

    public function cancelQuoteRequestByReference(string $quoteRequestReference): Response
    {
        return $this->postQuoteRequestAction($quoteRequestReference, static::ACTION_CANCEL);
    }

    public function sendQuoteRequestToUser(QuoteRequestTransfer $quoteRequestTransfer): Response
    {
        return $this->sendQuoteRequestToUserByReference($this->getReference($quoteRequestTransfer));
    }

    public function sendQuoteRequestToUserByReference(string $quoteRequestReference): Response
    {
        return $this->postQuoteRequestAction($quoteRequestReference, static::ACTION_SEND_TO_USER);
    }

    public function reviseQuoteRequest(QuoteRequestTransfer $quoteRequestTransfer): Response
    {
        return $this->reviseQuoteRequestByReference($this->getReference($quoteRequestTransfer));
    }

    public function reviseQuoteRequestByReference(string $quoteRequestReference): Response
    {
        return $this->postQuoteRequestAction($quoteRequestReference, static::ACTION_REVISE);
    }

    public function convertQuoteRequestToQuote(QuoteRequestTransfer $quoteRequestTransfer): Response
    {
        return $this->convertQuoteRequestToQuoteByReference($this->getReference($quoteRequestTransfer));
    }

    public function convertQuoteRequestToQuoteByReference(string $quoteRequestReference): Response
    {
        return $this->postQuoteRequestAction($quoteRequestReference, static::ACTION_CONVERT_TO_QUOTE);
    }

    protected function postQuoteRequestAction(string $quoteRequestReference, string $action): Response
    {
        return $this->handleApiRequest(
            'POST',
            sprintf('/%s/%s/%s', static::COLLECTION_QUOTE_REQUESTS, $quoteRequestReference, $action),
            $this->encodeJsonApiBody($action, []),
        );
    }

    protected function getReference(QuoteRequestTransfer $quoteRequestTransfer): string
    {
        return (string)$quoteRequestTransfer->getQuoteRequestReference();
    }
}
