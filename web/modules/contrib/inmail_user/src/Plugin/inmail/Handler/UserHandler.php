<?php

namespace Drupal\inmail_user\Plugin\inmail\Handler;

use Drupal\user\Entity\User;
use Drupal\inmail\MIME\MimeMessageInterface;
use Drupal\inmail\Plugin\inmail\Handler\HandlerBase;
use Drupal\inmail\ProcessorResultInterface;

/**
 * Message handler that creates a user from a mail message.
 *
 * @ingroup handler
 *
 * @Handler(
 *   id = "inmail_user",
 *   label = @Translation("User Creator"),
 *   description = @Translation("Creates a user from a mail message.")
 * )
 */
class UserHandler extends HandlerBase {

  /**
   * {@inheritdoc}
   */
  public function help() {
    return [
      '#type' => 'item',
      '#markup' => $this->t('Creates a user from a mail message.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function invoke(MimeMessageInterface $message, ProcessorResultInterface $processor_result) {
    try {

      $email = '';

      // Try to get the address from email.
      if (is_array($message->getFrom()) && count($message->getFrom())) {
        $matches = [];
        $from = reset($message->getFrom())->getAddress();
        preg_match('/[^@<\s]+@[^@\s>]+/', $from, $matches);
        if (!empty($matches)) {
          $email = reset($matches);
        }
      }

      // Not found, use the default.
      if (!$email) {
        $email = \Drupal::config('invoice_agent.settings')->get('email');
      }

      // Try to load user by email address.
      $matched_users = \Drupal::entityTypeManager()
        ->getStorage('user')
        ->loadByProperties(['mail' => $email]);

      // Create a user, if does not exists.
      if (empty($matched_users)) {
        $user = User::create();
        $user->setPassword(uniqid());
        $user->enforceIsNew();
        $user->setEmail($email);
        $user->setUsername($email);
        $user->activate();
        $user->save();
      }

      \Drupal::logger('mailhandler')->notice('User @email has been created.', [
        '@email' => $email,
      ]);

    }
    catch (\Exception $e) {
      watchdog_exception('inmail_user', $e);
    }
  }

}
