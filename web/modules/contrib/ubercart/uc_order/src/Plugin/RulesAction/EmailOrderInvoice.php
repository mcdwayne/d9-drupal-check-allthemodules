<?php

namespace Drupal\uc_order\Plugin\RulesAction;

use Drupal\uc_order\OrderInterface;

/**
 * Provides a 'Email order invoice' action.
 *
 * @RulesAction(
 *   id = "uc_order_email_invoice",
 *   label = @Translation("Email an order invoice"),
 *   category = @Translation("Order"),
 *   context = {
 *     "order" = @ContextDefinition("entity:uc_order",
 *       label = @Translation("Order")
 *     ),
 *     "from" = @ContextDefinition("email",
 *       label = @Translation("Sender"),
 *       description = @Translation("Enter the 'From' email address, or leave blank to use your store email address. You may use order tokens for dynamic email addresses."),
 *       required = FALSE
 *     ),
 *     "addresses" = @ContextDefinition("email",
 *       label = @Translation("Recipients"),
 *       description = @Translation("Enter the email addresses to receive the invoice, one on each line. You may use order tokens for dynamic email addresses."),
 *       multiple = TRUE
 *     ),
 *     "subject" = @ContextDefinition("string",
 *       label = @Translation("Subject"),
 *       translatable = TRUE
 *     ),
 *     "template" = @ContextDefinition("string",
 *       label = @Translation("Invoice template"),
 *       list_options_callback = "templateOptions",
 *       restriction = "input"
 *     ),
 *     "view" = @ContextDefinition("string",
 *       label = @Translation("Included information"),
 *       list_options_callback = "invoiceOptions",
 *       restriction = "input"
 *     )
 *   }
 * )
 */
class EmailOrderInvoice extends EmailActionBase {

  /**
   * Emails an invoice.
   *
   * Order token replacements may be used in the 'Sender', 'Subject' and
   * 'Addresses' fields.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order object.
   * @param string $from
   *   Sender's e-mail address.
   * @param string[] $addresses
   *   Recipients' e-mail addresses.
   * @param string $subject
   *   E-mail subject.
   * @param string $template
   *   Template name to use to format the invoice.
   * @param string $view
   *   Which view of the invoice - one of 'admin' or 'customer'.
   */
  protected function doExecute(OrderInterface $order, $from, array $addresses, $subject, $template, $view) {
    $settings = [
      'from' => $from,
      'addresses' => $addresses,
      'subject' => $subject,
      'template' => $template,
      'view' => $view,
    ];
    // Token replacements for the from, subject and body.
    $settings['replacements'] = [
      'uc_order' => $order,
    ];

    // Apply token replacements to the 'from' e-mail address.
    $from = $this->token->replace($settings['from'], $settings['replacements']);
    if (empty($from)) {
      $from = uc_store_email_from();
    }

    // Split up our recipient e-mail addresses so we can send a
    // separate e-mail to each.
    $recipients = [];
    foreach ($addresses as $address) {
      $address = trim($address);
      // Remove blank lines.
      if (!empty($address)) {
        // Apply token replacements to the 'recipient' e-mail address.
        $recipients[] = $this->token->replace($address, $settings['replacements']);
      }
    }

    $message = [
      '#theme' => 'uc_order_invoice',
      '#order' => $order,
      '#op' => $settings['view'],
      '#template' => $settings['template'],
    ];
    $settings['message'] = \Drupal::service('renderer')->renderPlain($message);

    // Use uc_order's hook_mail() to send a separate e-mail to each recipient.
    foreach ($recipients as $to) {
      $sent = $this->mailManager->mail('uc_order', 'rules-action-email', $to, uc_store_mail_recipient_langcode($to), $settings, $from);

      if (!$sent['result']) {
        $this->logger->get('uc_order')->error('Attempt to e-mail invoice for order @order_id to @email failed.', ['@email' => $to, '@order_id' => $order->id()]);
      }
    }
  }

}
