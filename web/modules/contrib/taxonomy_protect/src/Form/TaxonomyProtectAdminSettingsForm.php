<?php

/**
 * @file
 * Contains \Drupal\taxonomy_protect\Form\TaxonomyProtectAdminSettingsForm.
 */

namespace Drupal\taxonomy_protect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;

class TaxonomyProtectAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_protect_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['taxonomy_protect.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $vocabularies = Vocabulary::loadMultiple();
    $list = [];
    foreach ($vocabularies as $vocabulary) {
      $list[$vocabulary->id()] = $vocabulary->get('name');
    }
    if (!$list) {
      drupal_set_message($this->t('No vocabularies found.'), 'warning');
      return;
    }
    $form['taxonomy_protect_vocabularies'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Vocabularies to protect'),
      '#options' => $list,
      '#default_value' => \Drupal::config('taxonomy_protect.settings')->get('taxonomy_protect_vocabularies'),
      '#description' => $this->t('Users will be prevented from deleting selected vocabularies.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $vocabs = array_filter($form_state->getValue('taxonomy_protect_vocabularies'));
    $this->config('taxonomy_protect.settings')
      ->set('taxonomy_protect_vocabularies', $vocabs)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
