<?php

namespace Drupal\xero_contact_sync;

use Drupal\xero\XeroQueryFactory;

class XeroContactSyncLookupService {

  /**
   * A Xero query.
   *
   * @var \Drupal\xero\XeroQueryFactory
   */
  protected $xeroQueryFactory;

  /**
   * Create a XeroContactSyncLookupService object.
   *
   * @param \Drupal\xero\XeroQueryFactory $xero_query_factory
   *   The Xero query factory.
   */
  public function __construct(XeroQueryFactory $xero_query_factory) {
    $this->xeroQueryFactory = $xero_query_factory;
  }

  public function lookupByContactNumber($contact_number) {
    if ($contact_number === NULL) {
      return FALSE;
    }
    $xeroQuery = $this->xeroQueryFactory->get();
    $xeroQuery->setType('xero_contact');
    $xeroQuery->addCondition('ContactNumber', $contact_number);
    $item_list = $xeroQuery->execute();

    $result = FALSE;
    if ($item_list !== FALSE && count($item_list) !== 0) {
      $result = $item_list->get(0);
    }
    return $result;
  }

  public function lookupByContactEmailAddress($email) {
    if (empty($email)) {
      return FALSE;
    }
    $xeroQuery = $this->xeroQueryFactory->get();
    $xeroQuery->setType('xero_contact');
    $xeroQuery->addCondition('EmailAddress', $email);
    $item_list = $xeroQuery->execute();

    $result = FALSE;
    if ($item_list !== FALSE && count($item_list) !== 0) {
      $result = $item_list->get(0);
    }
    return $result;
  }

}
