<?php

namespace Drupal\uc_file\Plugin\RulesAction;

use Drupal\uc_order\OrderInterface;
use Drupal\uc_order\Plugin\RulesAction\EmailActionBase;

/**
 * Provides a 'Send an order file email' action.
 *
 * @RulesAction(
 *   id = "uc_file_order_email",
 *   label = @Translation("Send an order email regarding files"),
 *   category = @Translation("Notification"),
 *   context = {
 *     "order" = @ContextDefinition("entity:uc_order",
 *       label = @Translation("Order")
 *     ),
 *     "expiration" = @ContextDefinition("string",
 *       label = @Translation("File expiration")
 *     ),
 *     "from" = @ContextDefinition("email",
 *       label = @Translation("Sender"),
 *       description = @Translation("Enter the 'From' email address, or leave blank to use your store email address. You may use order tokens for dynamic email addresses."),
 *       required = FALSE
 *     ),
 *     "addresses" = @ContextDefinition("email",
 *       label = @Translation("Recipients"),
 *       description = @Translation("Enter the email addresses to receive the notifications, one on each line. You may use order tokens for dynamic email addresses."),
 *       multiple = TRUE
 *     ),
 *     "subject" = @ContextDefinition("string",
 *       label = @Translation("Subject"),
 *       translatable = TRUE
 *     ),
 *     "message" = @ContextDefinition("string",
 *       label = @Translation("Message"),
 *       translatable = TRUE
 *     ),
 *     "format" = @ContextDefinition("string",
 *       label = @Translation("Message format"),
 *       list_options_callback = "messageFormats"
 *     )
 *   }
 * )
 */
class SendOrderEmail extends EmailActionBase {

  /**
   * Send an email with order and file replacement tokens.
   *
   * The recipients, subject, and message fields take order token replacements.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order object.
   * @param array $expiration
   *   Expiration information.
   * @param string $from
   *   Sender's e-mail address.
   * @param string[] $addresses
   *   Recipients' e-mail addresses.
   * @param string $subject
   *   E-mail subject.
   * @param string $message
   *   E-mail body.
   * @param string $format
   *   Format filter machine name.
   */
  protected function doExecute(OrderInterface $order, array $expiration, $from, array $addresses, $subject, $message, $format) {
    $settings = [
      'from' => $from,
      'addresses' => $addresses,
      'subject' => $subject,
      'message' => $message,
      'format' => $format,
    ];

    // Token replacements for the subject and body.
    $settings['replacements'] = [
      'uc_order' => $order,
      'uc_file' => $file_expiration,
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
      $recipients[] = trim($address);
      // Remove blank lines.
      if (!empty($address)) {
        // Apply token replacements to the 'recipient' e-mail address.
        $recipients[] = $this->token->replace($address, $settings['replacements']);
      }
    }

    // Use uc_order's hook_mail() to send a separate e-mail to each recipient.
    foreach ($recipients as $to) {
      $sent = $this->mailManager->mail('uc_order', 'rules-action-email', $to, uc_store_mail_recipient_langcode($to), $settings, $from);

      if (!$sent['result']) {
        $this->logger->get('uc_file')->error('Attempt to e-mail @to concerning order @order_id failed.', ['@email' => $to, '@order_id' => $order->id()]);
      }
    }
  }

}
