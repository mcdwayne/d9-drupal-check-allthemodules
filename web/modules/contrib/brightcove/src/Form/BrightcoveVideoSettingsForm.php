<?php

namespace Drupal\brightcove\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds form for video settings.
 *
 * @ingroup brightcove
 */
class BrightcoveVideoSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'BrightcoveVideo_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty implementation of the abstract submit class.
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['BrightcoveVideo_settings']['#markup'] = 'Settings form for Brightcove Videos. Manage field settings here.';
    return $form;
  }

}
