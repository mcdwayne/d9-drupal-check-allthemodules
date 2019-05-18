<?php

namespace Drupal\brightcove\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds form for playlist settings.
 *
 * @ingroup brightcove
 */
class BrightcovePlaylistSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'BrightcovePlaylist_settings';
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
    $form['BrightcovePlaylist_settings']['#markup'] = 'Settings form for Brightcove Playlists. Manage field settings here.';
    return $form;
  }

}
