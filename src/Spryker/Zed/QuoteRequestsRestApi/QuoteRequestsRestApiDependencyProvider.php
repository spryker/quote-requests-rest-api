<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\QuoteRequestsRestApi;

use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;
use Spryker\Zed\QuoteRequestsRestApi\Dependency\Facade\QuoteRequestsRestApiToCartsRestApiFacadeBridge;
use Spryker\Zed\QuoteRequestsRestApi\Dependency\Facade\QuoteRequestsRestApiToQuoteRequestFacadeBridge;

/**
 * @method \Spryker\Zed\QuoteRequestsRestApi\QuoteRequestsRestApiConfig getConfig()
 */
class QuoteRequestsRestApiDependencyProvider extends AbstractBundleDependencyProvider
{
    /**
     * @var string
     */
    public const FACADE_CARTS_REST_API = 'FACADE_CARTS_REST_API';

    /**
     * @var string
     */
    public const FACADE_QUOTE_REQUEST = 'FACADE_QUOTE_REQUEST';

    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);
        $container = $this->addCartsRestApiFacade($container);
        $container = $this->addQuoteRequestFacade($container);

        return $container;
    }

    protected function addQuoteRequestFacade(Container $container): Container
    {
        $container->set(static::FACADE_QUOTE_REQUEST, function (Container $container) {
            return new QuoteRequestsRestApiToQuoteRequestFacadeBridge(
                $container->getLocator()->quoteRequest()->facade(),
            );
        });

        return $container;
    }

    protected function addCartsRestApiFacade(Container $container): Container
    {
        $container->set(static::FACADE_CARTS_REST_API, function (Container $container) {
            return new QuoteRequestsRestApiToCartsRestApiFacadeBridge(
                $container->getLocator()->cartsRestApi()->facade(),
            );
        });

        return $container;
    }
}
