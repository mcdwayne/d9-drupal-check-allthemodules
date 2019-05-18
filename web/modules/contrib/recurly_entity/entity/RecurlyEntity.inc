<?php

/**
 * @class
 * The top-level object containing common methods to all Recurly objects.
 */
class RecurlyEntity extends Entity {

  /**
   * Construct a Recurly Entity.
   *
   * The primary purpose of this is to ensure that the Recurly PHP library has
   * been loaded as it doesn't use Drupal's autoloader.
   */
  public function __construct(array $values = [], $entityType = NULL) {
    recurly_client_initialize();
    parent::__construct($values, $entityType);
  }

  /**
   * Map the Recurly account from the subscription to a local owner ID.
   *
   * Most of the time this will be a user id, but the Recurly module allows
   * accounts to be mapped to any entity type.
   *
   * @param string $account_code
   *   The Recurly account code to find the entity IDs for.
   *
   * @throws RecurlyEntityNoOwnerException
   *
   * @return array
   *   An array with the entity type and entity ID.
   */
  public static function entityOwner($account_code) {
    $local_account = recurly_account_load(['account_code' => $account_code], TRUE);
    if ($local_account) {
      return [
        'entity_type' => $local_account->entity_type,
        'entity_id' => $local_account->entity_id,
      ];
    }

    throw new RecurlyEntityNoOwnerException(format_string('There is no owner associated with account code @account-code.', ['@account-code' => $account_code]));
  }

  /**
   * Validate that the given invoice has been collected.
   *
   * By default, this method will attempt to retry collection three times,
   * waiting 1 second in between retries.
   *
   * @param Recurly_Invoice $invoice
   *   The invoice to validate.
   * @param int $retries
   *   The number of times to retry collection before marking the invoice as
   *   failed.
   *
   * @throws RecurlyEntityInvoiceFailedException
   * @throws RecurlyEntityInvoiceTimeoutException
   */
  public static function invoiceCollected(Recurly_Invoice $invoice, $retries = 3) {

    // Logging. Track each retry attempt.
    watchdog('recurly', 'Trying to collect invoice.', ['invoice_state' => @$invoice->state, 'retries_remaining' => @$retries]);

    // Possible invoice states are collected, failed, open, and past_due.
    while ($retries > 0) {
      switch ($invoice->state) {
        case 'open':
          $invoice = Recurly_Invoice::get($invoice->invoice_number);
          break;

        case 'failed':
        case 'past_due':
          throw new RecurlyEntityInvoiceFailedException($invoice, format_string('Invoice !uuid is marked as failed.', ['!uuid' => $invoice->uuid]));

        case 'collected':
        default:
          return;
      }
      // Wait before evaluating the invoice and trying again (in seconds).
      sleep(variable_get('recurly_entity_invoice_wait', 1));
      $retries--;
    }

    if ($retries <= 0) {
      throw new RecurlyEntityInvoiceTimeoutException($invoice, format_string('Invoice validation for !uuid hit the retries limit.', ['!uuid' => $invoice->uuid]));
    }

  }

  /**
   * Set the owner of this entity.
   *
   * This is commonly used when constructing Recurly entities.
   *
   * @param array &$values
   *   The array of values used to construct the entity, from Entity API. If an
   *   owner entity is found, 'entity_type' and 'entity_id' keys will be added.
   */
  protected function setOwner(array &$values) {
    if (!isset($values['entity_type']) || !isset($values['entity_id'])) {
      // See if this object has an account_code; that might save us from having
      // to do a remote account load.
      if (isset($values['account_code'])) {
        $account_code = $values['account_code'];
      }
      else {
        $account_code = $values['account']->idFromHref();
      }

      if ($owner = $this->entityOwner($account_code)) {
        $values = array_merge($values, $owner);
      }
    }
  }
}

class RecurlyEntityNoOwnerException extends Exception {}
class RecurlyEntityInvoiceTimeoutException extends RecurlyEntityInvoiceException {}
