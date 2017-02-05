# Countries
## A Laravel Countries & Currencies

[![Latest Stable Version](https://img.shields.io/packagist/v/pragmarx/countries.svg?style=flat-square)](https://packagist.org/packages/pragmarx/countries) [![License](https://img.shields.io/badge/license-BSD_3_Clause-brightgreen.svg?style=flat-square)](LICENSE) [![Downloads](https://img.shields.io/packagist/dt/pragmarx/countries.svg?style=flat-square)](https://packagist.org/packages/pragmarx/countries) [![Code Quality](https://img.shields.io/scrutinizer/g/antonioribeiro/countries.svg?style=flat-square)](https://scrutinizer-ci.com/g/antonioribeiro/countries/?branch=master) [![StyleCI](https://styleci.io/repos/74829244/shield)](https://styleci.io/repos/74829244)

## Requirements

- PHP 7.0+
- Laravel 5.3+

## Installing

Use Composer to install it:

    composer require pragmarx/countries

## Installing on Laravel

Add the Service Provider and Facade alias to your `app/config/app.php` (Laravel 4.x) or `config/app.php` (Laravel 5.x):

    PragmaRX\Countries\ServiceProvider::class,

## Publish config and views

    php artisan vendor:publish

## Hit The Countries Panel

    http://yourdomain.com/countries/panel
    
## Configure All The Things

- Panel
- Title and messages
- Resource checkers
- Slack icon
- Sort resources in the panel
- Notification channels
- Template location
- Routes and prefixes
- Mail server
- Cache
- Scheduler

## Allowing Slack Notifications

To receive notifications via Slack, you'll have to setup [Incoming Webhooks](https://api.slack.com/incoming-webhooks) and add this method to your User model with your webhook: 

    /**
     * Route notifications for the Slack channel.
     *
     * @return string
     */
    public function routeNotificationForSlack()
    {
        return config('services.slack.webhook_url');
    }

## Cache

When Countries result is cached, you can flush the chage to make it process all resources again by adding `?flush=true` to the url: 

    http://yourdomain.com/countries/panel?flush=true

## Events

If you prefer to build you own notifications systems, you can disable it and listen for the following event  

    PragmaRX\Countries\Events\RaiseCountriesIssue::class

## Broadcasting Checker

Broadcasting checker is done via ping and pong system. The broadcast checker will ping your service, and it must pong back. Basically what you need to do is to call back a url with some data:

### Redis + Socket.io

    var request = require('request');
    var server = require('http').Server();
    var io = require('socket.io')(server);
    var Redis = require('ioredis');
    var redis = new Redis();
    
    redis.subscribe('pragmarx-countries-broadcasting-channel');
    
    redis.on('message', function (channel, message) {
        message = JSON.parse(message);
    
        if (message.event == 'PragmaRX\\Countries\\Events\\CountriesPing') {
            request.get(message.data.callbackUrl + '?data=' + JSON.stringify(message.data));
        }
    });
    
    server.listen(3000);

### Pusher

    <!DOCTYPE html>
    <html>
        <head>
            <title>Pusher Test</title>
            <script src="https://js.pusher.com/3.2/pusher.min.js"></script>
            <script>
                var pusher = new Pusher('YOUR-PUSHER-KEY', {
                    encrypted: true
                });
    
                var channel = pusher.subscribe('pragmarx-countries-broadcasting-channel');
    
                channel.bind('PragmaRX\\Countries\\Events\\CountriesPing', function(data) {
                    var request = (new XMLHttpRequest());
    
                    request.open("GET", data.callbackUrl + '?data=' + JSON.stringify(data));
    
                    request.send();
                });
            </script>
        </head>
    
        <body>
            Pusher waiting for events...
        </body>
    </html>


## Author

[Antonio Carlos Ribeiro](http://twitter.com/iantonioribeiro)

## License

Countries is licensed under the BSD 3-Clause License - see the `LICENSE` file for details

## Contributing

Pull requests and issues are more than welcome.
