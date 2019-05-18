<?php

namespace Drupal\keep_referenced_entities\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure settings for module Keep referenced entities.
 */
class KeepReferencedEntitiesForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'keep_referenced_entities_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['keep_referenced_entities.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('keep_referenced_entities.settings');
    $entity_types_config = unserialize($config->get('entity_types'));

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable protection of referenced entities from deletion.'),
      '#default_value' => $config->get('enabled'),
    ];

    $form['entity_types'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Entity types:'),
      '#tree' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Get available entity types and bundles.
    $entity_manager = \Drupal::getContainer()->get('entity.manager');
    $definitions = $entity_manager->getDefinitions();
    ksort($definitions);
    foreach ($definitions as $definition) {
      $form['entity_types'][$definition->id()] = [
        '#type' => 'checkbox',
        '#title' => $definition->getLabel(),
        '#default_value' => $entity_types_config[$definition->id()],
      ];

      $form['entity_types'][$definition->id() . '_bundles'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Bundles:'),
        '#tree' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name="entity_types[' . $definition->id() . ']"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $bundles = $entity_manager->getBundleInfo($definition->id());
      foreach ($bundles as $bundle_id => $bundle_name) {
        $form['entity_types'][$definition->id() . '_bundles'][$bundle_id] = [
          '#type' => 'checkbox',
          '#title' => $bundle_name['label'],
          '#default_value' => $entity_types_config[$definition->id() . '_bundles'][$bundle_id],
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_types = $form_state->getValue('entity_types');

    $this->config('keep_referenced_entities.settings')
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('entity_types', serialize($entity_types))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
