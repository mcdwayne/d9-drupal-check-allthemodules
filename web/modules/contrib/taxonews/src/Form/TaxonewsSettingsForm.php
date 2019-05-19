<?php

/**
 * @file
 * Contains \Drupal\taxonews\TaxonewsSettingsForm.
 */

namespace Drupal\taxonews\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\SystemConfigFormBase;
use Drupal\taxonews\Taxonews;

/**
 * Configure book settings for this site.
 */
class TaxonewsSettingsForm extends ConfigFormBase {

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'taxonews_admin_settings';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $vocabularies = taxonomy_vocabulary_load_multiple();
    array_walk($vocabularies, function (&$vocabulary, $vid) {
      $vocabulary = t('@name: @description', array(
        '@name' => $vocabulary->name,
        '@description' => $vocabulary->description,
      ));
    });

    $config = $this->configFactory->get('taxonews.settings');
    $form['taxonews_allowed_vocabularies'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Vocabularies for which to generated Taxonews blocs.'),
      '#default_value' => $config->get('allowed_vocabularies'),
      '#options' => $vocabularies,
      '#description' => t('Blocks will be available for each vocabulary configured here.'),
    );

    $form['adminSettings'] = taxonews()->adminSettings();
    return parent::buildForm($form, $form_state);
  }

  /**
   * Delete existing blocks for terms not in allowed vocabularies.
   *
   * These can happen when a previously allowed vocabulary, for which some
   * blocks had been placed, ceases to be allowed. The placed blocks must be
   * removed because their plugin can no longer be instanciated.
   *
   * @param array $allowed_vocabularies
   *   An array of vocabulary ids.
   *
   * @return void
   */
  public function deleteExcessBlocks($allowed_vocabularies) {
    // Find all taxonews Block entities.
    $ids = \Drupal::entityQuery('block')
      ->condition('id', ".taxonews_", 'CONTAINS')
      ->execute();

    if (empty($allowed_vocabularies)) {
      $allowed_existing_ids = array();
    }
    else {
      // Find allowed Taxonews Block entities.
      $q = \Drupal::entityQuery('block');
      foreach ($allowed_vocabularies as $vid) {
        $q->condition('id', ".taxonews_{$vid}_", 'CONTAINS');
      }
      $allowed_existing_ids = $q->execute();
    }

    $excess = array_diff($ids, $allowed_existing_ids);
    entity_delete_multiple('block', $excess);
    Taxonews::cacheFlush();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $allowed_vocabularies = array_filter($form_state->getValue('taxonews_allowed_vocabularies'));
    if (empty($allowed_vocabularies)) {
      drupal_set_message(t('You did not enable any vocabulary for Taxonews: the module is therefore unused and you should disable it or enable at least one vocabulary.'), 'warning');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $allowed_vocabularies = array_filter($form_state->getValue('taxonews_allowed_vocabularies'));

    // Save allowed vocabularies.
    sort($allowed_vocabularies);
    $this->configFactory->get('taxonews.settings')
      ->set('allowed_vocabularies', $allowed_vocabularies)
      ->save();

    $this->deleteExcessBlocks($allowed_vocabularies);
    parent::submitForm($form, $form_state);
  }
}
