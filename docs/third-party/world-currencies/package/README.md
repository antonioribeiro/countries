#Currency information in JSON.
This repository contains currency information for currencies contained in ISO 4217 in JSON format.

##Example

```json
{
    "CAD": {
        "name": "Canadian Dollar",
        "iso": {
            "code": "CAD",
            "number": "124"
        },
        "units": {
            "major": {
                "name": "dollar",
                "symbol": "$"
            },
            "minor": {
                "name": "cent",
                "symbol": "¢",
                "majorValue": 0.01
            }
        },
        "banknotes": {
            "frequent": [
                "5$",
                "10$",
                "20$",
                "50$",
                "100$"
            ],
            "rare": [
                "1$",
                "2$",
                "500$",
                "1000$"
            ]
        },
        "coins": {
            "frequent": [
                "1$",
                "2$",
                "5¢",
                "10¢",
                "25¢"
            ],
            "rare": [
                "1¢",
                "50¢"
            ]
        }
    }
}
```

## How to contribute?
You can simply submit a pull request and I'll gladly review them and merge them if the changes are acceptable.
 - Indent using spaces with a tab width of 4 in the json5 source.
 - Make sure to rebuild the distribution version by executing grunt before submiting a pull request.
 - Only static data (no exchange rate data or inflation value)

## Change log
See the [change log](https://github.com/wiredmax/currency/blob/master/CHANGELOG.md).


## To do
 - [X] All the currency in ISO 4217 with their respective information and details.
 - [ ] i18n of currency names and units
 - [ ] More formats such as XML, YAML and CSV.
 - [ ] Countries using currency.
 - [ ] Central bank accociated to the currency and it's related information like the location and website, etc.

## Sources
http://www.currency-iso.org

http://www.xe.com/iso4217.php

The rest comes from Wikipedia

## License
See [LICENSE](https://github.com/wiredmax/currency/blob/master/LICENSE).