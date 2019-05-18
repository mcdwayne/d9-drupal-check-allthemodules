<?php

namespace Drupal\drd_pi_acquia\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drd_pi_acquia\Entity\Account;

/**
 * Class Settings.
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      Account::getConfigName(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drd_pi_acquia_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(Account::getConfigName());

    drupal_set_message('No settings required at this point.');
    return parent::buildForm($form, $form_state);
  }

}
