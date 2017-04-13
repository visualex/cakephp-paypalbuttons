<?php

namespace Tresorg\PaypalButtons\Model\Endpoint;

use Muffin\Webservice\Model\Endpoint;

class PaypalButtonsEndpoint extends Endpoint
{
    /**
     * {@inheritDoc}
     */
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->primaryKey('id');
        $this->displayField('id');
    }
}
