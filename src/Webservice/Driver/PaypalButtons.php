<?php
namespace Tresorg\PaypalButtons\Webservice\Driver;

use Cake\Cache\Cache;
use GuzzleHttp\Client;
use Cake\Utility\Hash;
use Tresorg\PaypalButtons\Model\Endpoint\Schema\PaypalButtonSchema;
use Muffin\Webservice\AbstractDriver;

class PaypalButtons extends AbstractDriver
{

   public function initialize()
   {
      // todo mock handler for tests
      $this->client(new Client());
   }

   public function schema()
   {
      $schema = new PaypalButtonSchema(\get_class($this));
      return array_map( function(){ return null; }, array_flip( $schema->columns() ) );
   }

   public function credentials()
   {
      return $this->config()['credentials'];
   }

   public function endpoint()
   {
      return $this->config()['endpoint'];
   }

   public function defaults()
   {
      return $this->config()['defaults'];
   }

}