<?php

/**
 * @class RecurlyEntityInvoiceException
 * Extend exceptions to keep a reference of the invoice that triggered the
 * exception.
 */
abstract class RecurlyEntityInvoiceException extends Exception {

  protected $invoice;

  /**
   * @param Recurly_Invoice $invoice
   *   The invoice that triggered the exception.
   * @param string $message
   *   (optional) The exception message.
   * @param int $code
   *   (optional) The exception code.
   * @param Exception $previous
   *   (optional) The previous exception that was thrown.
   */
  public function __construct(Recurly_Invoice $invoice, $message = "", $code = 0, Exception $previous = NULL) {
    $this->invoice = $invoice;
    parent::__construct($message, $code, $previous);
  }

  /**
   * Return the invoice associated with this exception.
   *
   * @return Recurly_Invoice
   *   The invoice that triggered this exception.
   */
  public function invoice() {
    return $this->invoice;
  }
}
