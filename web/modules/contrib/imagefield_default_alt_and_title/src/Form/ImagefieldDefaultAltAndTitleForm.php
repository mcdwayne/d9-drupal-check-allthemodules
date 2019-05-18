<?php

/**
 * @file
 * Contains \Drupal\imagefield_default_alt_and_title\Form\ImagefieldDefaultAltAndTitleForm.
 */

namespace Drupal\imagefield_default_alt_and_title\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class ImagefieldDefaultAltAndTitleForm extends ConfigFormBase {

  /**
   * Imagefield default alt and title form ID.
   */
  public function getFormID() {
    return 'imagefield_default_alt_and_title';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'imagefield_default_alt_and_title.settings',
    ];
  }

  /**
   * Imagefield default alt and title config form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('imagefield_default_alt_and_title.settings');
    $form['imagefield_default_alt_and_title_entity_types'] = [
      '#type' => 'checkboxes',
      '#title' => t('Entity types'),
      '#options' => $this->ImagefieldDefaultAltAndTitleEntityList(),
      '#default_value' => $config->get('imagefield_default_alt_and_title_entity_types'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Imagefield default alt and title config form submit.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('imagefield_default_alt_and_title.settings')
                        ->set('imagefield_default_alt_and_title_entity_types', $form_state->getValue('imagefield_default_alt_and_title_entity_types'))
                        ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Load all entity types available on the site.
   */
  public function ImagefieldDefaultAltAndTitleEntityList() {
    $all_types = [];
    $use_entitys = ['commerce_product_type', 'node_type', 'taxonomy_vocabulary'];
    $entity_all_types_list = \Drupal::entityTypeManager()->getDefinitions();
    if (!empty($entity_all_types_list)) {
      foreach ($entity_all_types_list as $key => $data) {
        if (in_array($key, $use_entitys)) {
          $contentTypesList = \Drupal::service('entity.manager')
                                     ->getStorage($key)
                                     ->loadMultiple();
          if (!empty($contentTypesList)) {
            foreach ($contentTypesList as $key_bundles => $data_bundles) {
              $all_types[$key_bundles] = $key_bundles;
            }
          }
        }
      }
    }

    return $all_types;
  }
}
