<?php

namespace Drupal\zuora\Soap;

use Drupal\zuora\Exception\ZuoraException;

/**
 * Class SubscribeOptions
 *
 * @property bool GenerateInvoice
 * @property bool ProcessPayments
 */
class SubscribeOptions extends zObject {
  protected $zNamespace = 'http://api.zuora.com/';
  protected $zType = 'SubscribeOptions';

  protected function getData() {
    if (empty($this->GenerateInvoice) || empty ($this->ProcessPayments)) {
      throw new ZuoraException('Invalid SubscribeOptions');
    }

    return array(
      'GenerateInvoice'=>$this->GenerateInvoice,
      'ProcessPayments'=>$this->ProcessPayments
    );
  }

}
