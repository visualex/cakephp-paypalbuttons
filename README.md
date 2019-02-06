# PaypalButtons plugin

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.txt)

## Installation

Install the plugin with composer
```
$ composer require tresorg/cakephp-paypalbuttons
```

Manual install is discouraged but you can check the composer.json file for package requirements.

Add the following configuration to Datasources in your `config/app.php` file.

```
'Datasources' => [
   ...
   'paypal_buttons' => [
      'className' => 'Muffin\Webservice\Connection',
      'service' => 'Tresorg/PaypalButtons.PaypalButtons',
      'endpoint' => 'https://api-3t.paypal.com/nvp',
      'credentials' => [
         'USER'      => 'paypal.username',
         'PWD'       => 'pass***word',
         'SIGNATURE' => 'your-signature-here-012345678900123456789001234567890',
         'VERSION'   => 94,
      ],
      // these options can be overridden upon button creation, per button
      'defaults' => [
         'notify_url'   => 'http://exmpl.com/ipn.php',      // IPN calls get called here
         'return'       => 'http://exmpl.com/thanks.php',   // Successful Sales Page
         'cancel_return'=> 'http://exmpl.com/tryagain.php', // Unsuccessful Sale URL
      ],
   ],
],
```

## Usage

If you are loading this plugin from within an object that is not a CakePHP Controller or Shell,
use the `ModelAwareTrait` in your class to get the methods: `modelFactory` & `loadModel`.

```
// use the Muffin Webservice
$this->modelFactory('Endpoint', ['Muffin\Webservice\Model\EndpointRegistry', 'get']);
// load your remote Webservice:
$paypalButtonsRemoteWebservice = $this->loadModel('PaypalButtons.PaypalButtons', 'Endpoint');
// sends the data to paypal
subscription = $this->PaypalButtonsRemote->save( new Entity([
   'item_number' => 'yourInventoryNumber',
   'item_name' => 'MyCoolProductName',
   'price' => 9.99,
   'subscription_type' => 'M', //monthly subscription
   'subscription_recurring' => '1', //recurring subscription
]));
// receive the paypal button ID add it to your form, etc
$buttonId = $buttonCreated->id;
```

## Contribute

This Plugin works great with all currencies, subscriptions, trial periods.
Shipping is not implemented yet.

Feel free to contribute, fork and PR.

🍺
