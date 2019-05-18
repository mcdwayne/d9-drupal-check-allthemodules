<?php

/**
 * @file
 * Contains \Drupal\doabarrelroll\Form\DoabarrelrollSettingsForm.
 */

namespace Drupal\doabarrelroll\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Defines a form to configure the barrel roll settings.
 */
class DoabarrelrollSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'doabarrelroll_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['doabarrelroll_style'] = array(
      '#type' => 'checkbox',
      '#title' => t('Do the official barrel roll'),
      '#description' => t('The Barrel Roll, as made famous by Star Fox and Google, is actually a move known by airplane pilots as the Aileron Roll. The actual Barrel Roll is a different move, also supported by this module. After enabling this setting, try typing "Do a Barrel Roll" and "Do an Aileron Roll".' ),
      '#default_value' => \Drupal::config('doabarrelroll.settings')->get('style') == 'barrel',
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    \Drupal::config('doabarrelroll.settings')
      ->set('style', $form_state['values']['doabarrelroll_style'] ? 'barrel' : 'aileron')
      ->save();

    parent::submitForm($form, $form_state);
  }

}
