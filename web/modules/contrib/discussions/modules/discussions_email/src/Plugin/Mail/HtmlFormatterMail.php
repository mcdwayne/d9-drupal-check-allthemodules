<?php

namespace Drupal\discussions_email\Plugin\Mail;

use Drupal\Core\Mail\Plugin\Mail\PhpMail;

/**
 * Provides a 'HtmlFormatterMail' mail plugin.
 *
 * @Mail(
 *  id = "html_formatter_mail",
 *  label = @Translation("Html formatter mail")
 * )
 */
class HtmlFormatterMail extends PhpMail {

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
  }

}
