<?php

namespace Drupal\dream_fields\Plugin\DreamField;

use Drupal\dream_fields\DreamFieldPluginBase;
use Drupal\dream_fields\FieldBuilderInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Component\Utility\Html;

/**
 * Base class for all 3 list plugins.
 */
abstract class DreamFieldList extends DreamFieldPluginBase {

  /**
   * {@inheritdoc}
   *
   * @see buildForm
   */
  public function getForm() {
    $key = $this->getPluginId();
    $form = [];
    $form['list_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select which data you want to use'),
      '#required' => TRUE,
      '#options' => [],
      '#attributes' => [
        'class' => ['dream-fields--' . $key],
      ],
    ];

    if (\Drupal::moduleHandler()->moduleExists('taxonomy')) {
      $form['list_type']['#options']['link_to_voc'] = $this->t('Select an existing vocabulary');
      $form['list_type']['#options']['create_voc'] = $this->t('Create a new vocabulary');
      $vocabularies = [];
      foreach (Vocabulary::loadMultiple() as $vocabulary) {
        $vocabularies[$vocabulary->id()] = Html::escape($vocabulary->label());
      }
      $form['list_type_create_voc'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Enter the name of the vocabulary you want to be created'),
        '#states' => [
          'visible' => [
            'input.dream-fields--' . $key => ['value' => 'create_voc'],
          ],
          'required' => [
            'input.dream-fields--' . $key => ['value' => 'create_voc'],
          ],
        ],
      ];
      $form['list_type_link_to_voc'] = [
        '#type' => 'select',
        '#options' => $vocabularies,
        '#title' => $this->t('Select the vocabulary you want to use'),
        '#states' => [
          'visible' => [
            'input.dream-fields--' . $key => ['value' => 'link_to_voc'],
          ],
          'required' => [
            'input.dream-fields--' . $key => ['value' => 'link_to_voc'],
          ],
        ],
      ];
    }

    if (\Drupal::moduleHandler()->moduleExists('node')) {
      $form['list_type']['#options']['link_to_entity'] = $this->t('Select the type of content to link to');
      $bundle_info = \Drupal::entityManager()->getBundleInfo('node');
      $bundle_options = [];
      foreach ($bundle_info as $bundle_key => $bundle_label) {
        $bundle_options[$bundle_key] = $bundle_label['label'];
      }
      $form['list_type_link_to_entity'] = [
        '#type' => 'select',
        '#options' => $bundle_options,
        '#title' => $this->t('Select the content type to link to.'),
        '#states' => [
          'visible' => [
            'input.dream-fields--' . $key => ['value' => 'link_to_entity'],
          ],
          'required' => [
            'input.dream-fields--' . $key => ['value' => 'link_to_entity'],
          ],
        ],
      ];
    }

    // Make sure manual is the last option.
    $form['list_type']['#options']['manual'] = $this->t('Enter data manually');

    $form['list_type_manual'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Enter here your data'),
      '#description' => $this->t('Enter each option on a separate line, you may specify each value as key|value.'),
      '#states' => [
        'visible' => [
          'input.dream-fields--' . $key => ['value' => 'manual'],
        ],
        'required' => [
          'input.dream-fields--' . $key => ['value' => 'manual'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function saveForm($values, FieldBuilderInterface $field_builder) {

    if ($values['list_type'] === 'manual') {
      $allowed_values = [];
      $all_numeric = TRUE;
      $list = $values['list_type_manual'];
      $list = explode("\n", $list);
      $list = array_map('trim', $list);
      $list = array_filter($list, 'strlen');
      foreach ($list as $value) {
        $value = explode("|", $value);
        $allowed_values[$value[0]] = isset($value[1]) ? $value[1] : $value[0];
        if (!is_numeric($value[0])) {
          $all_numeric = FALSE;
        }
      }

      // If all keys are numeric, switch to list_integer.
      if ($all_numeric) {
        $field_builder
          ->setField('list_integer', [
            'allowed_values' => $allowed_values,
          ]);
      }
      else {
        $field_builder
          ->setField('list_string', [
            'allowed_values' => $allowed_values,
          ]);
      }
    }

    if ($values['list_type'] === 'link_to_entity') {
      $field_builder
        ->setField('entity_reference',
          ['target_type' => 'node'],
          [
            'link_to_entity' => TRUE,
            'hanlder' => 'default:node',
            'hanlder_settings' => [
              'target_bundles' => [
                $values['list_type_link_to_entity'] => $values['list_type_link_to_entity'],
              ],
            ],
          ]);
    }

    if ($values['list_type'] === 'create_voc') {
      $vocabulary = Vocabulary::create([
        'name' => $values['list_type_create_voc'],
        'vid' => \Drupal::service('dream_fields.vocabulary_machine_name_generator')->getMachineName($values['list_type_create_voc']),
      ]);
      $vocabulary->save();
      $field_builder
        ->setField('entity_reference', [
          'target_type' => 'taxonomy_term',
        ], [
          'link_to_entity' => TRUE,
          'handler' => 'default',
          'handler_settings' => [
            'target_bundles' => [
              $vocabulary->id() => $vocabulary->id(),
            ],
          ]
        ]);
    }

    if ($values['list_type'] === 'link_to_voc') {
      $vid = $values['list_type_link_to_voc'];
      $field_builder
        ->setField('entity_reference', [
          'target_type' => 'taxonomy_term',
        ], [
          'link_to_entity' => TRUE,
          'handler' => 'default',
          'handler_settings' => [
            'target_bundles' => [
              $vid => $vid,
            ],
          ]
        ]);
    }

  }

}
