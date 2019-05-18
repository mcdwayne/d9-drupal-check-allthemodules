<?php

namespace Drupal\prod_check\Plugin\ProdCheck\Modules;

use Drupal\contact\Entity\ContactForm;
use Drupal\prod_check\Plugin\ProdCheck\ProdCheckBase;

/**
 * Contact status check
 *
 * @ProdCheck(
 *   id = "contact",
 *   title = @Translation("Contact"),
 *   category = "modules",
 *   provider = "contact"
 * )
 */
class Contact extends ProdCheckBase {

  /**
   * Found email addresses
   */
  public $matches;

  /**
   * {@inheritdoc}
   */
  public function init() {
    $forms = $this->queryService->get('contact_form')->condition('status', 1)->execute();

    $this->matches = array();
    $prod_check_sitemail = $this->configFactory->get('prod_check.settings')->get('site_email');
    foreach ($forms as $form) {
      $entity = ContactForm::load($form);
      if ($entity) {
        foreach ($entity->getRecipients() as $mail) {
          if (preg_match('/' . $prod_check_sitemail . '/i', $mail)) {
            $this->matches[] = '"' . $entity->label() . ': ' . $mail . '"';
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function state() {
    return empty($this->matches);
  }

  /**
   * {@inheritdoc}
   */
  public function successMessages() {
    return [
      'value' => $this->t('Contact e-mail addresses are OK.'),
      'description' => $this->t('Your settings are OK for production use.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function failMessages() {
    return [
      'value' => $this->t('Dangerous contact e-mail addresses are %categories', array('%categories' => implode(',', $this->matches))),
      'description' => $this->generateDescription(
        $this->title(),
        'entity.contact_form.collection',
        'The %link recipient e-mail addresses should not be development addresses on production sites!'
      ),
    ];
  }

}
