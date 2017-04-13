<?php
namespace Tresorg\PaypalButtons\Model\Endpoint;

use Muffin\Webservice\Model\Endpoint;

class PaypalButtonsEndpoint extends Endpoint
{

   public function initialize(array $config)
   {
      parent::initialize($config);
      $this->primaryKey('id');
      $this->displayField('id');
   }
}
