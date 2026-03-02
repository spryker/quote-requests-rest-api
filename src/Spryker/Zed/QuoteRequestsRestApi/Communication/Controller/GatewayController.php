<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\QuoteRequestsRestApi\Communication\Controller;

use Generated\Shared\Transfer\QuoteRequestResponseTransfer;
use Generated\Shared\Transfer\QuoteRequestTransfer;
use Spryker\Zed\Kernel\Communication\Controller\AbstractGatewayController;

/**
 * @method \Spryker\Zed\QuoteRequestsRestApi\Business\QuoteRequestsRestApiFacadeInterface getFacade()
 */
class GatewayController extends AbstractGatewayController
{
    public function createQuoteRequestAction(
        QuoteRequestTransfer $quoteRequestTransfer
    ): QuoteRequestResponseTransfer {
        return $this->getFacade()->createQuoteRequest($quoteRequestTransfer);
    }

    public function updateQuoteRequestAction(
        QuoteRequestTransfer $quoteRequestTransfer
    ): QuoteRequestResponseTransfer {
        return $this->getFacade()->updateQuoteRequest($quoteRequestTransfer);
    }
}
