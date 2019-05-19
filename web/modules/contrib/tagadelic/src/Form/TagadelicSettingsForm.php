<?php

/**
 * @file
 * Contains \Drupal\tagadelic\Form\TagadelicSettingsForm.
 */

namespace Drupal\tagadelic\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Vocabularies used in the tag cloud.
 */
class TagadelicSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tagadelic.settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tagadelic.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('tagadelic.settings');
    $vocabularies = Vocabulary::loadMultiple();

    $options = array();
    foreach ($vocabularies as $vid => $vocabulary) {
      $options[$vid] = $vocabulary->get('name');
    }

    $form['tagadelic_vocabularies'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Vocabularies used in Tag Cloud'),
      '#default_value' => (empty($config->get('tagadelic_vocabularies'))) ? array() : $config->get('tagadelic_vocabularies'),
      '#options' => $options,
    );

    return parent::buildForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('tagadelic.settings');
    $config->set('tagadelic_vocabularies', $form_state->getValue('tagadelic_vocabularies'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
