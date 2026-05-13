<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\QuoteRequestsRestApi\Api\Storefront\Mapper;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteRequestTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RestQuoteRequestsAttributesTransfer;
use Spryker\Service\Container\Attributes\Plugins;
use Spryker\Service\Serializer\SerializerServiceInterface;

/**
 * Builds the storefront `QuoteRequests` resource from a `QuoteRequestTransfer`, replaying the
 * legacy `RestQuoteRequestAttributesExpanderPluginInterface` chain. Uses the Serializer for
 * the typed shape and then patches the four `writable: false` relationship-source properties
 * (Serializer drops them on read; `ApiPlatformRelationshipResolver` needs them for `?include=…`).
 */
class QuoteRequestResourceMapper
{
    /**
     * @param array<\Spryker\Glue\QuoteRequestsRestApiExtension\Dependency\Plugin\RestQuoteRequestAttributesExpanderPluginInterface> $restQuoteRequestAttributesExpanderPlugins
     */
    public function __construct(
        protected SerializerServiceInterface $serializer,
        #[Plugins(dependencyProviderMethod: 'getRestQuoteRequestAttributesExpanderPlugins')]
        protected array $restQuoteRequestAttributesExpanderPlugins = [],
    ) {
    }

    /**
     * @template TResource of object
     *
     * @param class-string<TResource> $resourceClass
     *
     * @return TResource
     */
    public function denormalizeQuoteRequestResource(
        QuoteRequestTransfer $quoteRequestTransfer,
        string $localeName,
        string $resourceClass,
    ): object {
        $data = $this->mapQuoteRequestTransferToResourceData($quoteRequestTransfer, $localeName);

        $resource = $this->serializer->denormalize($data, $resourceClass);
        // PHPStan narrows `$resource` to `TResource` via the `class-string<TResource> $resourceClass`
        // generic — runtime assertion is a no-op in production and documents the contract.
        assert($resource instanceof $resourceClass);

        $companyUserTransfer = $quoteRequestTransfer->getCompanyUser();

        if (property_exists($resource, 'companyUserUuid')) {
            $resource->companyUserUuid = $companyUserTransfer?->getUuid();
        }

        if (property_exists($resource, 'companyBusinessUnitUuid')) {
            $resource->companyBusinessUnitUuid = $companyUserTransfer?->getCompanyBusinessUnit()?->getUuid();
        }

        if (property_exists($resource, 'customerReference')) {
            $resource->customerReference = $companyUserTransfer?->getCustomer()?->getCustomerReference();
        }

        if (property_exists($resource, 'concreteProductSkus')) {
            $resource->concreteProductSkus = $this->extractConcreteProductSkus($quoteRequestTransfer);
        }

        return $resource;
    }

    /**
     * @return array<string, mixed>
     */
    public function mapQuoteRequestTransferToResourceData(
        QuoteRequestTransfer $quoteRequestTransfer,
        string $localeName,
    ): array {
        $data = $quoteRequestTransfer->toArray(true, true);

        $companyUserTransfer = $quoteRequestTransfer->getCompanyUser();

        $data['versions'] = $this->extractVersionReferences($quoteRequestTransfer);
        $data['customer'] = $this->flattenCustomer($quoteRequestTransfer);
        $data['shownVersion'] = $this->resolveShownVersion($quoteRequestTransfer);

        $data = $this->applyExpanderPlugins($data, $quoteRequestTransfer, $localeName);

        // Set top-level relationship-source keys AFTER the legacy expander chain — these are not
        // declared on `RestQuoteRequestsAttributes`/`RestQuoteRequestCustomer`, so wrapping the
        // array through those transfers inside the expander loop would drop them silently.
        $data['companyUserUuid'] = $companyUserTransfer?->getUuid();
        $data['companyBusinessUnitUuid'] = $companyUserTransfer?->getCompanyBusinessUnit()?->getUuid();
        $data['customerReference'] = $companyUserTransfer?->getCustomer()?->getCustomerReference();
        $data['concreteProductSkus'] = $this->extractConcreteProductSkus($quoteRequestTransfer);

        return $data;
    }

    /**
     * The legacy expander plugins operate on collections of
     * {@see RestQuoteRequestsAttributesTransfer} keyed by index. We wrap our single
     * resource into a one-element collection, pass it through the chain, then merge
     * the (possibly enriched) result back into the flat data array we return.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function applyExpanderPlugins(
        array $data,
        QuoteRequestTransfer $quoteRequestTransfer,
        string $localeName,
    ): array {
        if ($this->restQuoteRequestAttributesExpanderPlugins === []) {
            return $data;
        }

        $restAttributesTransfers = [(new RestQuoteRequestsAttributesTransfer())->fromArray($data, true)];
        $quoteRequestTransfers = [$quoteRequestTransfer];

        foreach ($this->restQuoteRequestAttributesExpanderPlugins as $expanderPlugin) {
            $restAttributesTransfers = $expanderPlugin->expand(
                $restAttributesTransfers,
                $quoteRequestTransfers,
                $localeName,
            );
        }

        $expanded = $restAttributesTransfers[0] ?? null;

        if ($expanded === null) {
            return $data;
        }

        return array_replace($data, $expanded->toArray(true, true));
    }

    /**
     * @return array<string>
     */
    public function extractConcreteProductSkus(QuoteRequestTransfer $quoteRequestTransfer): array
    {
        $shownVersion = $quoteRequestTransfer->getIsLatestVersionVisible()
            ? $quoteRequestTransfer->getLatestVersion()
            : $quoteRequestTransfer->getLatestVisibleVersion();

        if ($shownVersion === null) {
            return [];
        }

        $quote = $shownVersion->getQuote();

        if ($quote === null) {
            return [];
        }

        $skus = [];

        foreach ($quote->getItems() as $itemTransfer) {
            $sku = $itemTransfer->getSku();

            if ($sku !== null && $sku !== '') {
                $skus[] = $sku;
            }
        }

        return array_values(array_unique($skus));
    }

    /**
     * Maps each `QuoteRequestVersion` to its `versionReference`, filtering out nulls and re-indexing.
     *
     * @return array<string>
     */
    public function extractVersionReferences(QuoteRequestTransfer $quoteRequestTransfer): array
    {
        return array_values(array_filter(array_map(
            fn ($version) => $version->getVersionReference(),
            $quoteRequestTransfer->getQuoteRequestVersions()->getArrayCopy(),
        )));
    }

    /**
     * Builds a denormalized "customer" payload — flattens `CompanyUser` + nested `Customer`
     * transfers into a single associative array consumable by the storefront resource.
     *
     * @return array<string, mixed>|null
     */
    public function flattenCustomer(QuoteRequestTransfer $quoteRequestTransfer): ?array
    {
        $companyUserTransfer = $quoteRequestTransfer->getCompanyUser();

        if ($companyUserTransfer === null) {
            return null;
        }

        $customerData = [
            'idCompanyUser' => $companyUserTransfer->getIdCompanyUser(),
            'uuid' => $companyUserTransfer->getUuid(),
            'idCompany' => $companyUserTransfer->getFkCompany(),
            'idCompanyBusinessUnit' => $companyUserTransfer->getFkCompanyBusinessUnit(),
            'companyBusinessUnitUuid' => $companyUserTransfer->getCompanyBusinessUnit()?->getUuid(),
        ];

        $customerTransfer = $companyUserTransfer->getCustomer();

        if ($customerTransfer !== null) {
            $customerData['idCustomer'] = $customerTransfer->getIdCustomer();
            $customerData['customerReference'] = $customerTransfer->getCustomerReference();
            $customerData['email'] = $customerTransfer->getEmail();
            $customerData['firstName'] = $customerTransfer->getFirstName();
            $customerData['lastName'] = $customerTransfer->getLastName();
        }

        return $customerData;
    }

    /**
     * Resolves which `QuoteRequestVersion` the storefront should surface — the latest version
     * when `isLatestVersionVisible` is true, otherwise the latest visible version — and maps
     * its cart to the storefront shape.
     *
     * @return array<string, mixed>|null
     */
    public function resolveShownVersion(QuoteRequestTransfer $quoteRequestTransfer): ?array
    {
        $quoteRequestVersionTransfer = $quoteRequestTransfer->getIsLatestVersionVisible()
            ? $quoteRequestTransfer->getLatestVersion()
            : $quoteRequestTransfer->getLatestVisibleVersion();

        if ($quoteRequestVersionTransfer === null) {
            return null;
        }

        $versionData = $quoteRequestVersionTransfer->toArray(true, true);

        $quoteTransfer = $quoteRequestVersionTransfer->getQuote();

        if ($quoteTransfer !== null) {
            $versionData['cart'] = $this->mapCart($quoteTransfer);
        }

        return $versionData;
    }

    /**
     * @return array<string, mixed>
     */
    public function mapCart(QuoteTransfer $quoteTransfer): array
    {
        return [
            'priceMode' => $quoteTransfer->getPriceMode(),
            'currency' => $quoteTransfer->getCurrency()?->getCode(),
            'store' => $quoteTransfer->getStore()?->getName(),
            'totals' => $this->mapTotals($quoteTransfer),
            'items' => $this->mapItems($quoteTransfer),
            'billingAddress' => $quoteTransfer->getBillingAddress()?->toArray(true, true),
            'discounts' => $this->mapDiscounts($quoteTransfer),
            'shipments' => $this->mapShipments($quoteTransfer),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function mapTotals(QuoteTransfer $quoteTransfer): ?array
    {
        $totalsTransfer = $quoteTransfer->getTotals();

        if ($totalsTransfer === null) {
            return null;
        }

        $totalsData = $totalsTransfer->toArray(true, true);
        $taxTotalTransfer = $totalsTransfer->getTaxTotal();

        if ($taxTotalTransfer !== null) {
            $totalsData['taxTotal'] = $taxTotalTransfer->toArray(true, false);
        }

        return $totalsData;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function mapDiscounts(QuoteTransfer $quoteTransfer): array
    {
        $discounts = [];

        foreach ($quoteTransfer->getVoucherDiscounts() as $discountTransfer) {
            $discounts[] = $discountTransfer->toArray(true, true);
        }

        return $discounts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function mapShipments(QuoteTransfer $quoteTransfer): array
    {
        $shipments = [];
        $processedShipmentIds = [];

        foreach ($quoteTransfer->getItems() as $itemTransfer) {
            $shipmentTransfer = $itemTransfer->getShipment();

            if ($shipmentTransfer === null) {
                continue;
            }

            $shipmentId = $shipmentTransfer->getIdSalesShipment();

            if ($shipmentId !== null && isset($processedShipmentIds[$shipmentId])) {
                continue;
            }

            if ($shipmentId !== null) {
                $processedShipmentIds[$shipmentId] = true;
            }

            $shipments[] = $shipmentTransfer->toArray(true, true);
        }

        return $shipments;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function mapItems(QuoteTransfer $quoteTransfer): array
    {
        $items = [];

        foreach ($quoteTransfer->getItems() as $itemTransfer) {
            $itemData = $itemTransfer->toArray(true, true);
            $itemData['salesUnit'] = $this->mapItemSalesUnit($itemTransfer);
            $itemData['calculations'] = $this->mapItemCalculations($itemTransfer);
            $itemData['selectedProductOptions'] = $this->mapSelectedProductOptions($itemTransfer);
            $items[] = $itemData;
        }

        return $items;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function mapItemSalesUnit(ItemTransfer $itemTransfer): ?array
    {
        $salesUnitTransfer = $itemTransfer->getAmountSalesUnit();

        if ($salesUnitTransfer === null) {
            return null;
        }

        return [
            'id' => $salesUnitTransfer->getIdProductMeasurementSalesUnit(),
            'amount' => $itemTransfer->getAmount(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function mapItemCalculations(ItemTransfer $itemTransfer): array
    {
        return [
            'unitPrice' => $itemTransfer->getUnitPrice(),
            'sumPrice' => $itemTransfer->getSumPrice(),
            'taxRate' => $itemTransfer->getTaxRate(),
            'unitNetPrice' => $itemTransfer->getUnitNetPrice(),
            'sumNetPrice' => $itemTransfer->getSumNetPrice(),
            'unitGrossPrice' => $itemTransfer->getUnitGrossPrice(),
            'sumGrossPrice' => $itemTransfer->getSumGrossPrice(),
            'unitTaxAmountFullAggregation' => $itemTransfer->getUnitTaxAmountFullAggregation(),
            'sumTaxAmountFullAggregation' => $itemTransfer->getSumTaxAmountFullAggregation(),
            'sumSubtotalAggregation' => $itemTransfer->getSumSubtotalAggregation(),
            'unitSubtotalAggregation' => $itemTransfer->getUnitSubtotalAggregation(),
            'unitProductOptionPriceAggregation' => $itemTransfer->getUnitProductOptionPriceAggregation(),
            'sumProductOptionPriceAggregation' => $itemTransfer->getSumProductOptionPriceAggregation(),
            'unitDiscountAmountAggregation' => $itemTransfer->getUnitDiscountAmountAggregation(),
            'sumDiscountAmountAggregation' => $itemTransfer->getSumDiscountAmountAggregation(),
            'unitDiscountAmountFullAggregation' => $itemTransfer->getUnitDiscountAmountFullAggregation(),
            'sumDiscountAmountFullAggregation' => $itemTransfer->getSumDiscountAmountFullAggregation(),
            'unitPriceToPayAggregation' => $itemTransfer->getUnitPriceToPayAggregation(),
            'sumPriceToPayAggregation' => $itemTransfer->getSumPriceToPayAggregation(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function mapSelectedProductOptions(ItemTransfer $itemTransfer): array
    {
        $selectedProductOptions = [];

        foreach ($itemTransfer->getProductOptions() as $productOptionTransfer) {
            $selectedProductOptions[] = [
                'optionGroupName' => $productOptionTransfer->getGroupName(),
                'sku' => $productOptionTransfer->getSku(),
                'optionName' => $productOptionTransfer->getValue(),
                'price' => $productOptionTransfer->getSumPrice(),
            ];
        }

        return $selectedProductOptions;
    }
}
