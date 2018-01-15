tax
===

[![Build Status](https://travis-ci.org/commerceguys/tax.svg?branch=master)](https://travis-ci.org/commerceguys/tax)

A PHP 5.5+ tax management library.

Features:
- Smart data model designed for fluctuating tax rate amounts ("19% -> 21% on January 1st")
- Predefined tax rates for EU countries and Switzerland. More to come.
- Tax resolvers with logic for all major use cases.

Requires [commerceguys/zone](https://github.com/commerceguys/zone).

The backstory behind the library design can be found in [this blog post](https://drupalcommerce.org/blog/31036/commerce-2x-stories-taxes).

Don't see your country's tax types and rates in the dataset? Send us a PR!

# Data model

[Zone](https://github.com/commerceguys/zone/blob/master/src/Model/ZoneInterface.php) 1-1 [TaxType](https://github.com/commerceguys/tax/blob/master/src/Model/TaxTypeInterface.php) 1-n [TaxRate](https://github.com/commerceguys/tax/blob/master/src/Model/TaxRateInterface.php) 1-n [TaxRateAmount](https://github.com/commerceguys/tax/blob/master/src/Model/TaxRateAmountInterface.php)

Each tax type has a zone and one or more tax rates.
Each tax rate has one or more tax rate amounts.

Example:
- Tax type: French VAT
- Zone: "France (VAT)" (covers "France without Corsica" and "Monaco")
- Tax rates: Standard, Intermediate, Reduced, Super Reduced
- Tax rate amounts for Standard: 19.6% (until January 1st 2014), 20% (from January 1st 2014)

The base interfaces don't impose setters, since they aren't needed by the service classes.
Extended interfaces ([TaxTypeEntityInterface](https://github.com/commerceguys/tax/blob/master/src/Model/TaxTypeEntityInterface.php), ([TaxRateEntityInterface](https://github.com/commerceguys/tax/blob/master/src/Model/TaxRateEntityInterface.php), ([TaxRateAmountEntityInterface](https://github.com/commerceguys/tax/blob/master/src/Model/TaxRateAmountEntityInterface.php)) are provided for that purpose,
as well as matching [TaxType](https://github.com/commerceguys/tax/blob/master/src/Model/TaxType.php), [TaxRate](https://github.com/commerceguys/tax/blob/master/src/Model/TaxRate.php) and [TaxRateAmount](https://github.com/commerceguys/tax/blob/master/src/Model/TaxRateAmount.php) classes that can be used as examples or mapped by Doctrine.

# Tax resolvers

The process of finding the most suitable tax type/rate/amount for the given taxable object is called resolving.
Along with the [Taxable object](https://github.com/commerceguys/tax/blob/master/src/TaxableInterface.php), a [Context object](https://github.com/commerceguys/tax/blob/master/src/Resolver/Context.php) containing customer and store information is also passed to the system.

Tax is resolved in three steps:

1. Resolve the tax types.
2. Resolve the tax rate for each resolved tax type.
3. Get the tax rate amount for each resolved tax rate (by calling `$rate->getAmount($date)`).

Tax types and tax rates are resolved by invoking registered resolvers (sorted by priority) until one of them returns a result.

Included tax type resolvers:

- [CanadaTaxTypeResolver](https://github.com/commerceguys/tax/blob/master/src/Resolver/TaxType/CanadaTaxTypeResolver.php) (Canada specific logic)

  The store charges the tax defined by the customer’s home province/territory.

  `If selling from a store in Quebec to a customer in Ontario, apply the Ontario HST.`

- [EuTaxTypeResolver](https://github.com/commerceguys/tax/blob/master/src/Resolver/TaxType/EuTaxTypeResolver.php) (EU specific logic)

  `A French store selling physical products (e.g. t-shirts) will charge French VAT to EU customers.`

  `A French store selling digital products (e.g. ebooks) from Jan 1st 2015 will apply the EU customer's tax rates (German customer - German VAT, etc)`

  `A French store will charge the 0% Intra-Community rate if the EU customer has provided a VAT number.`

- [DefaultTaxTypeResolver](https://github.com/commerceguys/tax/blob/master/src/Resolver/TaxType/DefaultTaxTypeResolver.php) (logic valid for most countries)

  If both the customer and the store belong to the same zone, returns the matched tax type.

  `The Serbian store is selling to a Serbian customer, use Serbian VAT.`

Included tax rate resolvers:

- [DefaultTaxRateResolver](https://github.com/commerceguys/tax/blob/master/src/Resolver/TaxRate/DefaultTaxRateResolver.php) - Returns a tax type's default tax rate.

Users would create a custom resolver for:
- "No tax in New York for t-shirts under 200$"
- "No tax for school supplies on september 1st (US tax holiday)"
- "Reduced rate for ebooks in France and other countries".
- "Return the tax type / rate referenced by the $taxable object"
(explicit place of supply, e.g. "French company providing a training in Spain")

Usage example:
```php
use CommerceGuys\Tax\Repository\TaxTypeRepository;
use CommerceGuys\Tax\Resolver\TaxType\ChainTaxTypeResolver;
use CommerceGuys\Tax\Resolver\TaxType\CanadaTaxTypeResolver;
use CommerceGuys\Tax\Resolver\TaxType\EuTaxTypeResolver;
use CommerceGuys\Tax\Resolver\TaxType\DefaultTaxTypeResolver;
use CommerceGuys\Tax\Resolver\TaxRate\ChainTaxRateResolver;
use CommerceGuys\Tax\Resolver\TaxRate\DefaultTaxRateResolver;
use CommerceGuys\Tax\Resolver\TaxResolver;

// The repository, and the resolvers are usualy initialized by the
// container, this is just a verbose example.
$taxTypeRepository = new TaxTypeRepository();
$chainTaxTypeResolver = new ChainTaxTypeResolver();
$chainTaxTypeResolver->addResolver(new CanadaTaxTypeResolver($taxTypeRepository));
$chainTaxTypeResolver->addResolver(new EuTaxTypeResolver($taxTypeRepository));
$chainTaxTypeResolver->addResolver(new DefaultTaxTypeResolver($taxTypeRepository));
$chainTaxRateResolver = new ChainTaxRateResolver();
$chainTaxRateResolver->addResolver(new DefaultTaxRateResolver());
$resolver = new TaxResolver($chainTaxTypeResolver, $chainTaxRateResolver);

// You can also provide the customer's tax number (e.g. VAT number needed
// to trigger Intra-Community supply rules in EU), list of additional countries
// where the store is registered to collect tax, a different calculation date.
$context = new Context($customerAddress, $storeAddress);

$amounts = $resolver->resolveAmounts($taxable, $context);
// More rarely, if only the types or rates are needed:
$rates = $resolver->resolveRates($taxable, $context);
$types = $resolver->resolveTypes($taxable, $context);

```

# Credits
- [Source for EU data](http://ec.europa.eu/taxation_customs/sites/taxation/files/resources/documents/taxation/vat/how_vat_works/rates/vat_rates_en.pdf)
