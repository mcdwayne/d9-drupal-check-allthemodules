<?php

/**
 * @file
 * Contains Drupal\jquery_matchheight\Form\SettingsForm.
*/

namespace Drupal\jquery_matchheight\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
class SettingsForm extends ConfigFormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormID()
  {
    return 'jquery_matchheight_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config('jquery_matchheight.settings');

    $form['jquery_matchheight'] = array(

      '#markup' => '<p>Placeholder for settings form</p>'
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    parent::submitForm($form, $form_state);

    $this->config('jquery_matchheight.settings')
        ->save();
  }
}
