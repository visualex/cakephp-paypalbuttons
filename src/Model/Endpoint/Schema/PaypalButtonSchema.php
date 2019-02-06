<?php
namespace Tresorg\PaypalButtons\Model\Endpoint\Schema;

use Muffin\Webservice\Model\Schema;

class PaypalButtonSchema extends Schema
{

      public function initialize()
      {
         parent::initialize();

         $this->addColumn(
            'id', [
               'type' => 'string',
               'primaryKey' => true,
               'null' => false,
               'length' => 64,
               'comment' => 'Item id, gets set by PayPal',
         ]);

         // product id goes here
         $this->addColumn(
            'item_number', [
               'type' => 'string',
               'null' => false,
               'length' => 64,
               'comment' => 'Item number, short string',
         ]);

         // product name goes here
         $this->addColumn(
            'item_name', [
               'type' => 'string',
               'null' => false,
               'length' => 256,
               'comment' => 'Item name, long string',
         ]);

         $this->addColumn(
            'price', [
               'type' => 'decimal',
               'precision' => 2,
               'null' => false,
               'comment' => 'The price',
         ]);

         $this->addColumn(
            'currency', [
               'type' => 'string',
               'null' => true,
               'length' => 3,
               'comment' => 'The currency, defaults to USD',
         ]);

         $this->addColumn(
            'subscription_type', [
               'type' => 'string',
               'null' => true,
               'length' => 1,
               'comment' => 'String: M = Month or Y = Year',
         ]);

         $this->addColumn(
            'subscription_recurring', [
               'type' => 'integer',
               'null' => true,
               'length' => 1,
               'comment' => 'Set value to 1 to make the subscriptions recur.',
         ]);

         $this->addColumn(
            'subscription_recurring_times', [
               'type' => 'integer',
               'null' => true,
               'length' => 1,
               'comment' => 'Set value between 2 and 52 to limit subscriptions recurring.',
         ]);

         $this->addColumn(
            'trial_period_price', [
               'type' => 'decimal',
               'precision' => 2,
               'null' => true,
               'comment' => 'Price for the trial period',
         ]);

         $this->addColumn(
            'trial_period_duration_length', [
               'type' => 'integer',
               'null' => true,
               'comment' => 'Numbers 1 - 12',
         ]);

         $this->addColumn(
            'trial_period_duration_unit', [
               'type' => 'string',
               'null' => true,
               'length' => 1,
               'comment' => 'String: M = Month or Y = Year',
         ]);

         $this->addColumn(
            'custom', [
               'type' => 'string',
               'null' => true,
               'comment' => 'custom string, will be passed back with the PayPal IPN call',
         ]);

         $this->addColumn(
            'return', [
               'type' => 'string',
               'null' => true,
               'comment' => 'custom URL to return to after a successful sale',
         ]);

         $this->addColumn(
            'cancel_return', [
               'type' => 'string',
               'null' => true,
               'comment' => 'cancel URL to return to after cancelled sale',
         ]);
    }
}
