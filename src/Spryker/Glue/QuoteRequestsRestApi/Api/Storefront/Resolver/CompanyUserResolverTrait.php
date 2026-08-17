<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Resolver;

use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CustomerTransfer;

/**
 * Turns the request-scoped company user into one carrying `idCompanyUser`.
 *
 * Shared by the QuoteRequests storefront processor and provider so both answer the declared 403
 * identically. The host class provides `$companyUserClient`, `$exceptionFactory` and `getCustomer()`.
 */
trait CompanyUserResolverTrait
{
    /**
     * The identity subscriber leaves `idCompanyUser` empty when the claim uuid has no storage record,
     * so the database is the only remaining source.
     *
     * @throws \Spryker\ApiPlatform\Exception\GlueApiException
     */
    protected function resolveFullCompanyUser(?CompanyUserTransfer $companyUserTransfer): CompanyUserTransfer
    {
        // Without a company user nothing scopes the quote-request read to an owner, so carrying on
        // would answer with every quote request in the store instead of the declared 403.
        if ($companyUserTransfer === null) {
            throw $this->exceptionFactory->createCompanyUserNotSelectedException();
        }

        if ($companyUserTransfer->getIdCompanyUser() !== null) {
            return $companyUserTransfer;
        }

        // Not the inherited getCustomerReference(): it fails with a 500 where a 403 is documented.
        $customerReference = $this->getCustomer()->getCustomerReference();

        if ($customerReference === null) {
            throw $this->exceptionFactory->createCompanyUserNotSelectedException();
        }

        $companyUserCollectionTransfer = $this->companyUserClient->getActiveCompanyUsersByCustomerReference(
            (new CustomerTransfer())->setCustomerReference($customerReference),
        );

        foreach ($companyUserCollectionTransfer->getCompanyUsers() as $resolvedCompanyUserTransfer) {
            if ($resolvedCompanyUserTransfer->getUuid() === $companyUserTransfer->getUuid()) {
                return $resolvedCompanyUserTransfer;
            }
        }

        throw $this->exceptionFactory->createCompanyUserNotSelectedException();
    }
}
