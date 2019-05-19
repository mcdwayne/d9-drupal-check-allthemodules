<?php

namespace Drupal\zuora\Soap;

/**
 * Class ZuoraSoapAccount
 * @package Drupal\lp_zuora
 *
 * @property string Id
 * @property string AccountNumber
 * @property string Name
 * @property string DefaultPaymentMethod
 * @property string Batch
 * @property int BillCycleDay
 * @property bool AutoPay
 * @property string PaymentTerm
 * @property string Status
 * @property string Currency
 * @property string AccountType__c
 * @property string Segment
 *
 * @link: https://docs.google.com/spreadsheets/d/1Ft0PPTu98rJFLB5eU4b1qPMQKURKge3SMyDPhIYcoDc/edit#gid=229786908
 */
class Account extends zObject {

  protected $zType = 'Account';

}
