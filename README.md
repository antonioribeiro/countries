# Countries
## A Laravel Countries package, with lots of information  

[![Latest Stable Version](https://img.shields.io/packagist/v/pragmarx/countries.svg?style=flat-square)](https://packagist.org/packages/pragmarx/countries) [![License](https://img.shields.io/badge/license-BSD_3_Clause-brightgreen.svg?style=flat-square)](LICENSE) [![Code Quality](https://img.shields.io/scrutinizer/g/antonioribeiro/countries.svg?style=flat-square)](https://scrutinizer-ci.com/g/antonioribeiro/countries/?branch=master) [![Build](https://img.shields.io/travis/antonioribeiro/countries.svg?style=flat-square)](https://travis-ci.org/antonioribeiro/countries) [![StyleCI](https://styleci.io/repos/74829244/shield)](https://styleci.io/repos/74829244) <!--- [![Downloads](https://img.shields.io/packagist/dt/pragmarx/countries.svg?style=flat-square)](https://packagist.org/packages/pragmarx/countries) -->

### Geoology and Topology for countries

Amongst many other information you'll be able to plot country maps:

![Switzerland](docs/switzerland-geo.png)

### What does it gives you?

This package is a collection of some other packages with information on:

- Countries
    - name (common and native)
    - currency
    - languages
    - states
    - timezone
    - flags (sprites, flag icons, svg)
    - tld
    - multiple ISO codes
    - calling code
    - capital
    - alternate spellings
    - region & sub region
    - translations (country name translated to some other languages)
    - latitude and logitude
    - borders (countries) - you can hydrate those borders (like relatioships)
    - area
    - topology 
    - geometry

- Currencies
    - sign
    - ISO codes
    - title
    - subunits
    - usage (dates)
    
- States 
    - adm codes
    - name & alt name
    - type (state, city, province, canton, department, district, etc.)
    - latitude & longitude
    - language
    - (and many more)

- Flags. There are some options available out there, so this package will give you some:
    - sprite, based on [https://www.flag-sprites.com/](https://www.flag-sprites.com/)
    - flag-icon and flag-icon-squared, based on [https://github.com/lipis/flag-icon-css](https://github.com/lipis/flag-icon-css)
    - world-flags-sprite, based on [https://github.com/lafeber/world-flags-sprite](https://github.com/lafeber/world-flags-sprite)
    - svg, the flag svg file loaded into the json
    
- Validation
    - Customizable validation fields based on [Laravel Validation](https://laravel.com/docs/master/validation)
  
## Requirements

- PHP 7.0+
- Laravel 5.3+

## Installing

Use Composer to install it:

```
composer require pragmarx/countries
```

## Installing on Laravel

Add the Service Provider and Facade alias to your `config/app.php`:
    
```php
// config/app.php

'providers' => [
    // ...
    PragmaRX\Countries\ServiceProvider::class,
];

'aliases' => [
    // ...
    'Countries'=> PragmaRX\Countries\Facade::class,
];
```
  
## Usage

The package is based on Laravel Collections, so you basically have access to all methods in Collections, like 

```php
$all = Countries::all();
```

You, obviously, don't need to use the Facade, you can just get it from the app container: 

```php
$contries = app('pragmarx.countries');

\\ then

$all = $contries->all();
```

This filter

```php
Countries::where('name.common', 'Brazil')
```


Will find Brazil by its common name, which is a 

```
#items: array:22 [▼
  "name" => array:3 [▼
    "common" => "Brazil"
    "official" => "Federative Republic of Brazil"
    "native" => array:1 [▼
      "por" => array:2 [▼
        "official" => "República Federativa do Brasil"
        "common" => "Brasil"
      ]
    ]
  ]
```

Or alternatively you can filter like this
```php`
Countries::whereNameCommon('Brazil')
``
       
And, you can go deepeer
         
```php
Countries::where('name.native.por.common', 'Brasil')
```
     
Or search by the country top level domain
     
```php
Countries::where('tld.0', '.ch')
```
     
To get

```
"name" => array:3 [▼
  "common" => "Switzerland"
  "official" => "Swiss Confederation"
  "native" => array:4 [▶]
]
"tld" => array:1 [▼
  0 => ".ch"
]
```
And use things like pluck

```php
Countries::where('cca3', 'USA')->first()->states->pluck('name', 'postal')
```

To get

```php
"MA" => "Massachusetts"
"MN" => "Minnesota"
"MT" => "Montana"
"ND" => "North Dakota"
...
```
          
The package uses a modified Collection which allows you to access properties and methods as objects:
    
```php
Countries::where('cca3', 'FRA')
         ->first()
         ->borders
         ->first()
         ->name
         ->official
```

Should give
    
```
Principality of Andorra
```
         
Borders hydration is disabled by default, but you can have your borders hydrated easily by calling the hydrate method:

```php
Countries::where('name.common', 'United Kingdom')
         ->hydrate('borders')
         ->first()
         ->borders
         ->reverse()
         ->first()
         ->name
         ->common
```

Should return 

```
Ireland
````

### Extra where rules
Some properties are stored differently and we therefore need special rules for accessing them, these properties are
- `ISO639_3` => The 3 letter language code.
- `ISO4217`  => The 3 letter currency code.

You can of course access them like other properties
```php
Countries::whereISO639_3('por')->count()
Countries::where('ISO639_3', 'por')->count()
```

### Mapping
Sometimes you would like to access a property by a different name, this can be done in settings, this way
```php
'maps' => [
    'lca3' => 'ISO639_3'
]
```
Here we bind the language 3 letter short code ISO format to `lca3`, which is short for `language code alpha 3-letter`.
So now we can access the property by
```php
Countries::whereLca3('por')
```
Or 
```php
Countries::where('lca3', 'por')
```

## Some other examples from **Laravel News** and some other contributors

#### Generate a list of countries

```php
Countries::all()->pluck('name.common');
```

returns

```php
[
    "Aruba",
    "Afghanistan",
    "Angola",
    "Anguilla",
    "Åland Islands",
    ....
```

#### Generate a list of currencies

```php
Countries::all()->pluck('currency');
```

returns

```php
Countries::all()->pluck('currency')
```

#### Generate a list of States

```php
Countries::where('name.common', 'United States')
    ->first()
    ->states
    ->sortBy('name')
    ->pluck('name', 'postal')
```

returns

```php
[
    "AL": "Alabama",
    "AK": "Alaska",
    "AZ": "Arizona",
    "AR": "Arkansas",
    "CA": "California",
    ....
    ....
```

#### Get the timezone for a State

```php
return Countries::where('name.common', 'United States')->first()->timezone->NC;
```

returns

```php
America/New_York
```

#### Get a countries currency

```php
Countries::where('name.common', 'United States')->first()->currency;
```

returns

```php
[{
    "alternativeSigns": [],
    "ISO4217Code": "USD",
    "ISO4217Number": "840",
    "sign": "$",
    "subunits": 100,
    "title": "U.S. dollar",
    ....
```

#### Get all currencies

```php
Countries::currencies()
```

returns

```php
[
    0 => "AED"
    1 => "AFN"
    2 => "ALL"
    3 => "AMD"
    4 => "ANG"
    5 => "AOA"
    6 => "ARS"
    7 => "AUD"
    8 => "AWG"
    9 => "AZN"
    10 => "BAM"
    ....
```

### Validation

The validation is extending Laravel's validation, so you can use it like any other validation rules, like

```php
/**
 * Store a new blog post.
 *
 * @param  Request  $request
 * @return Response
 */
public function store(Request $request)
{
    $this->validate($request, [
        'title' => 'required|unique:posts|max:255',
        'body' => 'required',
        'country' => 'country' //Checks if valid name.common
    ]);

    // The blog post is valid, store in database...
}
```

Which validation rules there is and what there name should be, can all be configured in the configuration file.

```php
'validation' => [
    'rules' => [
	    'countryCommon' => 'name.common'
	]
]
```

By changing the configuration like this, we can now access the property `name.common`, by the validation rule `countryCommon`

You have to define all the validations rules in settings, only a few is defined by default, the default is

```php
'rules' 	=> [
    'country' 			=> 'name.common',
    'cca2',
    'cca2',
    'cca3',
    'ccn3',
    'cioc',
    'currency'			=> 'ISO4217',
    'language',
    'language_short'	=> 'ISO639_3',
]
```

## Publishing assets

You can publish configuration by doing:
```
php artisan vendor:publish --provider=PragmaRX\\Countries\\ServiceProvider
```

## Data

This package uses some other open source packages and, until we don't build a better documentation, you can find some more info about data on [mledoze/countries](https://github.com/mledoze/countries/blob/master/README.md) and how to use it on this fantastic [Laravel News article](https://laravel-news.com/countries-and-currencies).

## Cache

Since this data is not supposed to change, calls are automatically cached.
If you want to change this behaviour, you can edit `config/countries.php` file once it's published.

## Sample files

- [sample-partial.json](src/data/sample-partial.json): example of a country with no borders hydrated.
- [sample-full.json](src/data/sample-full.json): example of a fully hydrated country.

## Author

[Antonio Carlos Ribeiro](http://twitter.com/iantonioribeiro)

## License

Countries is licensed under the BSD 3-Clause License - see the `LICENSE` file for details

## Contributing

Pull requests and issues are more than welcome.
