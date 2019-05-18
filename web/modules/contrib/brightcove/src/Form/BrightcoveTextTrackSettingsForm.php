<?php

namespace Drupal\brightcove\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BrightcoveTextTrackSettingsForm.
 *
 * @package Drupal\brightcove\Form
 *
 * @ingroup brightcove
 */
class BrightcoveTextTrackSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'BrightcoveTextTrack_settings';
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
    $form['BrightcoveTextTrack_settings']['#markup'] = 'Settings form for Brightcove Text Track entities. Manage field settings here.';
    return $form;
  }

}
