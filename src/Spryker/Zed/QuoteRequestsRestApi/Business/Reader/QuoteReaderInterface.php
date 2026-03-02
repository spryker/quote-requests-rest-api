<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\QuoteRequestsRestApi\Business\Reader;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\QuoteResponseTransfer;

interface QuoteReaderInterface
{
    public function findQuoteByUuidForCustomer(CustomerTransfer $customerTransfer, string $uuid): QuoteResponseTransfer;
}
