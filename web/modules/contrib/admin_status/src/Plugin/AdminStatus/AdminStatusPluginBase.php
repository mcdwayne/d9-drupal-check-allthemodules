<?php

namespace Drupal\admin_status\Plugin\AdminStatus;

use Drupal\admin_status\AdminStatusInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides base class for AdminStatus plugins with appropriate default methods.
 */
class AdminStatusPluginBase extends PluginBase implements AdminStatusInterface {

  /**
   * {@inheritdoc}
   */
  public function description() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function configForm(array $form,
                             FormStateInterface $form_state,
                             array $configValues) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configValidateForm(array $form,
                                     FormStateInterface $form_state,
                                     array $configValues) {
  }

  /**
   * {@inheritdoc}
   */
  public function configSubmitForm(array $form,
                                   FormStateInterface $form_state,
                                   array $configValues) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function message(array $configValues) {
    return [];
  }

}
