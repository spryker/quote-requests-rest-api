<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\QuoteRequestsRestApi\Business;

use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\QuoteRequestsRestApi\Business\Creator\QuoteRequestCreator;
use Spryker\Zed\QuoteRequestsRestApi\Business\Creator\QuoteRequestCreatorInterface;
use Spryker\Zed\QuoteRequestsRestApi\Business\Mapper\QuoteRequestResponseMapper;
use Spryker\Zed\QuoteRequestsRestApi\Business\Mapper\QuoteRequestResponseMapperInterface;
use Spryker\Zed\QuoteRequestsRestApi\Business\Reader\QuoteReader;
use Spryker\Zed\QuoteRequestsRestApi\Business\Reader\QuoteReaderInterface;
use Spryker\Zed\QuoteRequestsRestApi\Business\Updater\QuoteRequestUpdater;
use Spryker\Zed\QuoteRequestsRestApi\Business\Updater\QuoteRequestUpdaterInterface;
use Spryker\Zed\QuoteRequestsRestApi\Dependency\Facade\QuoteRequestsRestApiToCartsRestApiFacadeInterface;
use Spryker\Zed\QuoteRequestsRestApi\Dependency\Facade\QuoteRequestsRestApiToQuoteRequestFacadeInterface;
use Spryker\Zed\QuoteRequestsRestApi\QuoteRequestsRestApiDependencyProvider;

/**
 * @method \Spryker\Zed\QuoteRequestsRestApi\QuoteRequestsRestApiConfig getConfig()
 */
class QuoteRequestsRestApiBusinessFactory extends AbstractBusinessFactory
{
    public function createQuoteReader(): QuoteReaderInterface
    {
        return new QuoteReader($this->getCartsRestApiFacade());
    }

    public function createQuoteRequestCreator(): QuoteRequestCreatorInterface
    {
        return new QuoteRequestCreator(
            $this->createQuoteReader(),
            $this->createQuoteRequestResponseMapper(),
            $this->getQuoteRequestFacade(),
        );
    }

    public function createQuoteRequestUpdater(): QuoteRequestUpdaterInterface
    {
        return new QuoteRequestUpdater(
            $this->createQuoteReader(),
            $this->createQuoteRequestResponseMapper(),
            $this->getQuoteRequestFacade(),
        );
    }

    public function createQuoteRequestResponseMapper(): QuoteRequestResponseMapperInterface
    {
        return new QuoteRequestResponseMapper();
    }

    public function getCartsRestApiFacade(): QuoteRequestsRestApiToCartsRestApiFacadeInterface
    {
        return $this->getProvidedDependency(QuoteRequestsRestApiDependencyProvider::FACADE_CARTS_REST_API);
    }

    public function getQuoteRequestFacade(): QuoteRequestsRestApiToQuoteRequestFacadeInterface
    {
        return $this->getProvidedDependency(QuoteRequestsRestApiDependencyProvider::FACADE_QUOTE_REQUEST);
    }
}
