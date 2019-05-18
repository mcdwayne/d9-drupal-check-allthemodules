<?php

namespace Drupal\prod_check\Plugin\ProdCheck\Settings;

use Drupal\prod_check\Plugin\ProdCheck\ProdCheckBase;

/**
 * Site email status check
 *
 * @ProdCheck(
 *   id = "site_email",
 *   title = @Translation("Website email"),
 *   category = "modules"
 * )
 */
class SiteMail extends ProdCheckBase {

  /**
   * Dangerous mail address
   */
  public $dangerous_mail;

  /**
   * {@inheritdoc}
   */
  public function init() {
    $prod_check_sitemail = $this->configFactory->get('prod_check.settings')->get('site_email');
    $mail = $this->configFactory->get('system.site')->get('mail');
    if (preg_match('/' . $prod_check_sitemail . '/i', $mail)) {
      $this->dangerous_mail = $mail;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function state() {
    return empty($this->dangerous_mail);
  }

  /**
   * {@inheritdoc}
   */
  public function successMessages() {
    return [
      'value' => $this->t('Website e-mail addresses is OK.'),
      'description' => $this->t('Your settings are OK for production use.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function failMessages() {
    return [
      'value' => $this->t('Dangerous website e-mail address is %mail', array('%mail' => $this->dangerous_mail)),
      'description' => $this->generateDescription(
        $this->title(),
        'system.site_information_settings',
        'The %link e-mail address should not be a development addresses on production sites!'
      ),
    ];
  }

}
