<?php
namespace Tresorg\PaypalButtons\Webservice;

use Cake\Core\Exception\Exception;
use Cake\ORM\Entity;
use Cake\Network\Exception\NotFoundException;
use Muffin\Webservice\Query;
use Muffin\Webservice\ResultSet;
use Muffin\Webservice\Webservice\Webservice;
use Psr\Http\Message\ResponseInterface;
use Tresorg\PaypalButtons\Webservice\Exception\RateLimitExceededException;
use Tresorg\PaypalButtons\Webservice\Exception\UnknownErrorException;

class PaypalButtonsWebservice extends Webservice
{

   private function remoteToLocalSchema($remote)
   {
      $data = $this->driver()->schema();
      foreach ($remote as $key => $value) {
         $value = str_replace('"', '', $value);
         if ($key == 'HOSTEDBUTTONID') {
            $data['id'] = $value;
         } elseif (preg_match('/^L_BUTTONVAR/', $key)) {
            parse_str($value, $subvar);
            $subKey = key($subvar);
            if ($subKey == 'amount') {
               // todo, this might have been a mistake, change to amount?
               $data['price'] = $subvar[key($subvar)];
            }
            $data[$subKey] = $subvar[key($subvar)];
         } else {
            $data[$key] = $value;
         }
      }
      return $data;
   }

   private function commonPostFieldsForCreatingAndUpdating($data)
   {
      $config = $this->driver()->defaults();
      $data = array_merge($config, $data); // all config data can be overridden

      // if we have added a specific return url
      if (isset($data['return'])) {
         $config['return'] = $data['return'];
      }

      // default to U.S. Dollar if the currency is not specified
      if (!isset($data['currency'])) {
         $data['currency'] = 'USD';
      }

      $commonPostFields = array(
         'BUTTONTYPE' => 'BUYNOW',
         'BUTTONSUBTYPE' => 'PRODUCTS',
         "L_BUTTONVAR0" => "no_note=1",
         "L_BUTTONVAR1" => "item_name=" . $data['item_name'],
         "L_BUTTONVAR2" => "item_number=" . $data['item_number'],
         // todo, shipping goods
         "L_BUTTONVAR3" => "no_shipping=1",
         "L_BUTTONVAR4" => "return=" . $data['return'],
         "L_BUTTONVAR5" => "cancel_return=" . $data['cancel_return'],
         "L_BUTTONVAR6" => "notify_url=" . $data['notify_url'],
         // the buyer's browser is redirected to the return URL by using the POST method, and all payment variables are included
         "L_BUTTONVAR7" => "rm=2",
         "L_BUTTONVAR8" => "amount=" . $data['price'],
         "L_BUTTONVAR9" => "currency_code=" . $data['currency'],
      );

      // if this is a subscription
      if (isset($data['subscription_type'])) {
         $commonPostFields['BUTTONTYPE'] = 'SUBSCRIBE';
         // recurring subscription
         if (isset($data['subscription_recurring'])) {
            $commonPostFields['L_BUTTONVAR10'] = 'src=1';
         }

         // times a subscription recurrs. unset for infinite.
         if (isset($data['subscription_recurring_times'])) {
            $commonPostFields['L_BUTTONVAR20'] = 'srt=' . $data['subscription_recurring_times'];
         }

         $commonPostFields['L_BUTTONVAR11'] = 'a3=' . $data['price'];
         $commonPostFields['L_BUTTONVAR12'] = 'p3=1'; // every 1 * subscription_type
         $commonPostFields['L_BUTTONVAR13'] = 't3=' . $data['subscription_type'];

         if (isset($data['trial_period_price'])) {
            $commonPostFields['L_BUTTONVAR14'] = 'a1=' . $data['trial_period_price'];
            $commonPostFields['L_BUTTONVAR15'] = 'p1=' . $data['trial_period_duration_length'];
            $commonPostFields['L_BUTTONVAR16'] = 't1=' . $data['trial_period_duration_unit'];
         }
         // Reattempt on failure. If a recurring payment fails,
         // PayPal attempts to collect the payment two more times before canceling the subscription.
         // Set this to 1 for Reattempt on failure. 0 to not try again.
         $commonPostFields['L_BUTTONVAR17'] = 'sra=1';
      }

      // the custom field, gets returned to the IPN
      if (isset($data['custom'])) {
         $commonPostFields['L_BUTTONVAR18'] = 'custom=' . $data['custom'];
      }
 
      $commonPostFields['L_BUTTONVAR19'] = 'lc=US';
     
      return array_merge($commonPostFields, $this->driver()->credentials());
   }

   protected function _executeCreateQuery(Query $query, array $options = [])
   {
      $client = $this->driver()->client();
      $data = $query->set();
      $postFields = $this->commonPostFieldsForCreatingAndUpdating($data);

      $postFields['METHOD'] = 'BMCreateButton';

      // paypal buttons give server errors sometimes, keep looping untill we get a button
      while (!isset($buttonInfo['HOSTEDBUTTONID'])) {
         $response = $client->request('POST', $this->driver()->endpoint(), [
            'form_params' => $postFields,
         ]);
         $response = (string) $response->getBody();
         parse_str($response, $buttonInfo);
      }

      // return schema-valid results
      $created = $this->remoteToLocalSchema($buttonInfo);
      return $this->_transformResource($query->endpoint(), $created);
   }

   protected function _executeReadQuery(Query $query, array $options = [])
   {
      $client = $this->driver()->client();
      $queryData = $query->where();

      $queryById = !empty($queryData['id']);
      $queryByDate = !empty($queryData['start_date']) && !empty($queryData['end_date']);

      if (!$queryById && !$queryByDate) {
         throw new Exception('PaypalButtons requires an id or start_date & end_date to be set in the find conditions.');
      }

      if ($queryById) {
         $postFields = ['METHOD' => 'BMGetButtonDetails', 'HOSTEDBUTTONID' => $queryData['id']];
      }

      if ($queryByDate) {
         $postFields = ['METHOD' => 'BMButtonSearch', 'STARTDATE' => $queryData['start_date'], 'ENDDATE' => $queryData['end_date']];
      }

      // if you need to override the credentials per query or
      // if you need to get info on remote objects from some other environment other than production
      if (isset($queryData['credentials'])) {
         $postFields = array_merge($postFields, $queryData['credentials']);
      }

      $postFields = array_merge($this->driver()->credentials(), $postFields);

      // keep on curling untill obtained
      while (!isset($buttonInfo['ACK'])) {
         $response = $client->request('POST', $this->driver()->endpoint(), [
            'form_params' => $postFields,
         ]);
         $response = (string) $response->getBody();
         parse_str($response, $buttonInfo);
      }

      if ($buttonInfo['ACK'] == 'Failure') {
         return new ResultSet([], 0);
      }

      $found = $this->remoteToLocalSchema($buttonInfo);
      $resources = $this->_transformResults($query->endpoint(), [$found]);
      return new ResultSet($resources, count($resources));
   }

   protected function _executeUpdateQuery(Query $query, array $options = [])
   {
      $client = $this->driver()->client();
      $updates = $query->set();
      $conditions = $query->where();
      $postFields = $this->commonPostFieldsForCreatingAndUpdating($updates);
      $postFields['METHOD'] = 'BMUpdateButton';
      $postFields['HOSTEDBUTTONID'] = $conditions['id'];
      while (!isset($buttonInfo['HOSTEDBUTTONID'])) {
         $response = $client->request('POST', $this->driver()->endpoint(), [
            'form_params' => $postFields,
         ]);
         $response = (string) $response->getBody();
         parse_str($response, $buttonInfo);
      }
      // return schema-valid results
      $created = $this->remoteToLocalSchema($buttonInfo);
      return $this->_transformResource($query->endpoint(), $created);
   }

   protected function _executeDeleteQuery(Query $query, array $options = [])
   {
      if ((!isset($query->where()['id'])) || (is_array($query->where()['id']))) {
        return false;
      }

      $ids = $query->where()['id'];
      if (!is_array($ids)) {
         $ids = [$ids];
      }
      $client = $this->driver()->client();
      foreach ($ids as $buttonId) {
         $postFields = ['METHOD' => 'BMManageButtonStatus', 'HOSTEDBUTTONID' => $buttonId, 'BUTTONSTATUS' => 'DELETE'];
         $postFields = array_merge($this->driver()->credentials(), $postFields);
         $correlationId = '';
         while (true) { // have to do this while the correlation id is the same, paypal sandbox has bugs sometimes
            $response = $client->request('POST', $this->driver()->endpoint(), [
               'form_params' => $postFields,
            ]);
            $response = (string) $response->getBody();
            parse_str($response, $buttonInfo);
            if ($correlationId != $buttonInfo['CORRELATIONID']) {
               break;
            }
            $correlationId = $buttonInfo['CORRELATIONID'];
         }
      }
      return true;
    }

 }
