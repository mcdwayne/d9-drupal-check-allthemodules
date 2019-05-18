<?php

namespace Drupal\eat\Form;

use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\taxonomy\Entity\Vocabulary;

class EatAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eat_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['eat.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    drupal_set_message(t('Only node is currently available'), 'warning');
    // Get all configuration required for this form.
    // Show all available entity types.
    $content_entity_types = [];
    $entity_type_definitions = \Drupal::entityTypeManager()->getDefinitions();
    foreach ($entity_type_definitions as $definition) {
      // For nodes.
      if ($definition instanceof ContentEntityType) {
        $entity_type_id = $definition->getProvider();

        $form[$entity_type_id] = [
          '#type' => 'details',
          '#collapsed' => TRUE,
          '#collapsible' => TRUE,
          '#title' => $entity_type_id,
        ];

        // For node(s) only right now.
        if ($entity_type_id == 'node') {
          $types = \Drupal::entityTypeManager()
            ->getStorage('node_type')
            ->loadMultiple();
          $content_entity_types[$entity_type_id] = $definition->getLabel();

          $bundles = [];
          foreach ($types as $type) {
            $name = $type->get('name');
            $key = $type->get('type');
            $bundles[$entity_type_id.'@'.$key] = $name;
          }
          foreach ($bundles as $k => $v) {
            $form[$entity_type_id][$k] = [
              '#collapsible' => TRUE,
              '#collapsed' => 'TRUE',
              '#type' => 'details',
              '#title' => $v
            ];
            // Get all vocabularies.
            $vocabularies = Vocabulary::loadMultiple();
            $vocabulary_items = [];
            foreach ($vocabularies as $key => $value) {
              $vocabulary_items[$key] = $value->get('name');
            }

            // Select vocabularies that have been added.
            $config = $this->config('eat.settings');
            $config = $config->getRawData();

            if (empty($config)) {
              $form[$entity_type_id][$k]['eat@'.$k.'@'.'vocabularies'] = [
                '#type' => 'checkboxes',
                '#description' => $this->t('Set vocabularies to entity types and bundles.'),
                '#title' => $this->t('Vocabularies'),
                '#multiple' => TRUE,
                '#options' => $vocabulary_items,
              ];
            }
            else {
              foreach ($config['eat_item'] as $item) {
                $bundle = explode("node@", $k);
                if ($item['#bundle'] == $bundle[1]) {
                  $form[$entity_type_id][$k]['eat@'.$k.'@'.'vocabularies'] = [
                    '#type' => 'checkboxes',
                    '#description' => $this->t('Set vocabularies to entity types and bundles.'),
                    '#title' => $this->t('Vocabularies'),
                    '#multiple' => TRUE,
                    '#options' => $vocabulary_items,
                    '#default_value' => $item['#vocab'],
                  ];
                }
              }
            }
          }
        }
      }
    }

    $form['batch_update'] = [
      '#markup' => $this->t('Batch update content for Eat. <a href="/admin/config/system/eat/batch">Click here</a>')
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ((array) $form_state->getValues() as $key => $value) {
      if (strpos($key, 'eat') !== false) {
        $terms = [];
        foreach ($value as $t) {
          if (!empty($t)) {
            $terms[] = $t;
          }
        }

        // Determine field items needed.
        $items = explode("eat_", $key);
        $entity_type_raw = explode("@", $items[0]);

        $eat_items[] = [
          '#entity_type' => $entity_type_raw[1],
          '#bundle' => $entity_type_raw[2],
          '#vocab' => $terms
        ];
      }
    }


    \Drupal::configFactory()->getEditable('eat.settings')
      ->set('eat_item', $eat_items)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
