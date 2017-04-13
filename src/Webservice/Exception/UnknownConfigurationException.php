<?php
namespace Tresorg\PaypalButtons\Webservice\Exception;

use Cake\Core\Exception\Exception;

class UnknownConfigurationException extends Exception
{
    protected $_messageTemplate = 'Unknown config value %s';
}
