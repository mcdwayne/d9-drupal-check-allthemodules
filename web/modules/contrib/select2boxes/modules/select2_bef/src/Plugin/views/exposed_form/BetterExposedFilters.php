<?php

namespace Drupal\select2_bef\Plugin\views\exposed_form;

use Drupal\better_exposed_filters\Plugin\views\exposed_form\BetterExposedFilters as BetterExposedFiltersOrigin;
use Drupal\Core\Form\FormStateInterface;
use Drupal\select2boxes\PreloadBuildTrait;

/**
 * Class BetterExposedFilters.
 *
 * @ViewsExposedForm(
 *   id = "bef",
 *   title = @Translation("Better Exposed Filters"),
 *   help = @Translation("Provides additional options for exposed form elements.")
 * )
 *
 * @package Drupal\select2_bef\Plugin\views\exposed_form
 */
class BetterExposedFilters extends BetterExposedFiltersOrigin {
  use PreloadBuildTrait;

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $bef      = &$form['bef'];
    $settings = $this->getSettings();

    foreach ($bef as $name => $value) {
      if (static::isField($name)) {
        if (static::isEntityReferenceField($name)) {
          // Handle default value for referenced bundles.
          $referenced = [];
          if (!empty($settings[$name]['more_options']['reference_bundles'])) {
            foreach ($settings[$name]['more_options']['reference_bundles'] as $bundle => $val) {
              if ($val == $bundle) {
                $referenced[] = $bundle;
              }
            }
          }
          $bef[$name]['more_options']['reference_bundles'] = [
            '#type'          => 'checkboxes',
            '#title'         => $this->t('Entity bundles'),
            '#default_value' => $referenced,
            '#options'       => $this->buildReferenceBundlesList(static::convertDatabaseFieldToFieldname($name)),
            '#states'        => [
              'visible' => [
                [
                  ":input[name=\"exposed_form_options[bef][$name][bef_format]\"]" => [
                    'value' => 'select2boxes_autocomplete_multi',
                  ],
                ],
                'or',
                [
                  ":input[name=\"exposed_form_options[bef][$name][bef_format]\"]" => [
                    'value' => 'select2boxes_autocomplete_single',
                  ],
                ],
              ],
            ],
          ];
          // Add mandatory sign to the title.
          // We don't want do this via "#required" property,
          // because we need custom validation for this field
          // to prevent "required" errors being produced in the fields
          // which aren't use this at all.
          $bef[$name]['more_options']['reference_bundles']['#title'] .= '<span class="form-required"></span>';

          $bef[$name]['more_options']['enable_preload'] = [
            '#type'          => 'checkbox',
            '#title'         => $this->t('Enable preloaded entries'),
            '#default_value' => $settings[$name]['more_options']['enable_preload'],
            '#states'        => [
              'visible' => [
                [
                  ":input[name=\"exposed_form_options[bef][$name][bef_format]\"]" => [
                    'value' => 'select2boxes_autocomplete_multi',
                  ],
                ],
              ],
            ],
          ];
          $bef[$name]['more_options']['preload_count'] = [
            '#type'          => 'textfield',
            '#title'         => $this->t('Maximum number of entries that will be pre-loaded'),
            '#description'   => $this->t('If maximum number is not specified then all entries will be preloaded'),
            '#default_value' => $settings[$name]['more_options']['preload_count'],
            '#states'        => [
              'visible' => [
                ":input[name=\"exposed_form_options[bef][$name][bef_format]\"]" => [
                  'value' => 'select2boxes_autocomplete_multi',
                ],
                ":input[name=\"exposed_form_options[bef][$name][more_options][enable_preload]\"]" => [
                  'checked' => TRUE,
                ],
              ],
            ],
          ];
        }
        $bef[$name]['more_options']['limited_search'] = [
          '#type'          => 'checkbox',
          '#title'         => $this->t('Limit search box visibility by list length'),
          '#default_value' => $settings[$name]['more_options']['limited_search'],
          '#states'        => [
            'visible' => [
              [
                ":input[name=\"exposed_form_options[bef][$name][bef_format]\"]" => [
                  'value' => 'select2boxes_autocomplete_list',
                ],
              ],
              'or',
              [
                ":input[name=\"exposed_form_options[bef][$name][bef_format]\"]" => [
                  'value' => 'select2boxes_autocomplete_single',
                ],
              ],
            ],
          ],
        ];
        $bef[$name]['more_options']['minimum_search_length'] = [
          '#type'          => 'textfield',
          '#title'         => $this->t('Minimum list length'),
          '#default_value' => $settings[$name]['more_options']['minimum_search_length'],
          '#states'        => [
            'visible' => [
              [
                ":input[name=\"exposed_form_options[bef][$name][bef_format]\"]" => [
                  'value' => 'select2boxes_autocomplete_list',
                ],
                ":input[name=\"exposed_form_options[bef][$name][more_options][limited_search]\"]" => [
                  'checked' => TRUE,
                ],
              ],
              'or',
              [
                ":input[name=\"exposed_form_options[bef][$name][bef_format]\"]" => [
                  'value' => 'select2boxes_autocomplete_single',
                ],
                ":input[name=\"exposed_form_options[bef][$name][more_options][limited_search]\"]" => [
                  'checked' => TRUE,
                ],
              ],
            ],
          ],
        ];
      }
      if (self::isField($name) || $name == 'langcode') {
        $this->addIncludeIconsOption($bef, $name, $settings);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $entity_reference_formats = [
      'select2boxes_autocomplete_single',
      'select2boxes_autocomplete_multi',
    ];
    $values = $form_state->getValue(['exposed_form_options', 'bef']);
    foreach ($values as $name => &$value) {
      if (static::isEntityReferenceField($name)) {
        $reference = &$value['more_options']['reference_bundles'];
        if (!empty($reference)) {
          // Remove zero-value options from the list.
          foreach ($reference as $bundle => $val) {
            if (empty($val)) {
              unset($reference[$bundle]);
            }
          }
        }
        // Custom "required" field validation(for entity reference fields only).
        if (empty($reference) && in_array($value['bef_format'], $entity_reference_formats)) {
          $element = &$form['bef'][$name]['more_options']['reference_bundles'];
          $form_state->setError($element, $this->t('Entity bundles field is required'));
        }
      }
    }
    $form_state->setValue(['exposed_form_options', 'bef'], $values);
    parent::validateOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    // Reset filter's extra option to Dropdown.
    $formats = ['select2boxes_autocomplete_multi', 'select2boxes_autocomplete_single'];
    /** @var \Drupal\views\ViewEntityInterface $storage */
    $storage = \Drupal::routeMatch()
      ->getParameters()
      ->get('view')
      ->get('storage');
    $displays = $storage
      ->get('display');
    $display_id = \Drupal::routeMatch()->getParameter('display_id');
    $filters = &$displays[$display_id]['display_options']['filters'];

    $bef = $form_state->getValue('exposed_form_options')['bef'];
    foreach ($bef as $name => $value) {
      if (static::isEntityReferenceField($name)) {
        if (in_array($value['bef_format'], $formats)) {
          $filters[$name]['type'] = 'select';
        }
      }
    }
    $storage->set('display', $displays);
    parent::submitOptionsForm($form, $form_state);
  }

  /**
   * Add "Include flags icons" option if all dependencies are met.
   *
   * @param array &$bef
   *   BEF options form.
   * @param string $name
   *   Element's name.
   * @param array $settings
   *   BEF settings array.
   */
  protected function addIncludeIconsOption(array &$bef, $name, array $settings) {
    // Check if the field is of allowed type to include flags icons.
    $types = ['language_field', 'language', 'country', 'langcode'];
    if (in_array($this->getFieldType($name), $types)) {
      if (\Drupal::moduleHandler()->moduleExists('flags')) {
        // Add a new option to the settings form.
        $bef[$name]['more_options']['include_flags'] = [
          '#type'          => 'checkbox',
          '#title'         => $this->t('Include flags icons'),
          '#default_value' => isset($settings[$name]['more_options']['include_flags']) ? $settings[$name]['more_options']['include_flags'] : '',
          '#states'        => [
            // Make it invisible for any widgets except Select2 Boxes.
            'visible' => [
              ":input[name=\"exposed_form_options[bef][$name][bef_format]\"]" => [
                'value' => 'select2boxes_autocomplete_list',
              ],
            ],
          ],
        ];
      }
    }
  }

  /**
   * Check if the name is a field name.
   *
   * @param string $name
   *   Input name to check.
   *
   * @return bool
   *   Checking result.
   */
  protected static function isField($name) {
    return (bool) (stripos($name, 'field_') !== FALSE);
  }

  /**
   * Check if the field is entity reference.
   *
   * @param string $name
   *   Input field name to check.
   *
   * @return bool
   *   Checking result.
   */
  protected static function isEntityReferenceField($name) {
    return (bool) (stripos($name, '_target_id') !== FALSE);
  }

  /**
   * Get field's type.
   *
   * @param string $name
   *   Field's BEF name.
   *
   * @return string
   *   Field's type.
   */
  protected function getFieldType($name) {
    if (stripos($name, '_target_id') !== FALSE) {
      $name = static::convertDatabaseFieldToFieldname($name);
    }
    elseif (stripos($name, '_value') !== FALSE) {
      $name = str_replace('_value', '', $name);
    }
    $entity_type = $this->view->getBaseEntityType()->id();
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
    $field_definition = \Drupal::service('entity_field.manager')
      ->getFieldStorageDefinitions($entity_type)[$name];
    return $field_definition->getType();
  }

  /**
   * Build reference bundles list.
   *
   * @param string $field
   *   Field name.
   *
   * @return array
   *   Reference bundles list.
   */
  protected function buildReferenceBundlesList($field) {
    $bundles = [];
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
    $field_definition = \Drupal::service('entity_field.manager')
      ->getFieldStorageDefinitions($this->view->getBaseEntityType()->id())[$field];
    $entity_type = $field_definition->getSetting('target_type');
    $bundles_info = \Drupal::service('entity_type.bundle.info')
      ->getBundleInfo($entity_type);
    foreach ($bundles_info as $bundle_name => $bundle_label) {
      $bundles[$bundle_name] = $bundle_label['label'];
    }
    return $bundles;
  }

  /**
   * Convert db field name(column name) to field name.
   *
   * @param string $name
   *   Database field name.
   *
   * @return string
   *   Field name.
   */
  protected static function convertDatabaseFieldToFieldname($name) {
    return str_replace('_target_id', '', $name);
  }

  /**
   * {@inheritdoc}
   */
  public function renderExposedForm($block = FALSE) {
    $form = parent::renderExposedForm($block);
    $settings = $this->getSettings();
    /** @var \Drupal\Core\Entity\EntityFieldManager $service */
    $service = \Drupal::service('entity_field.manager');
    $map = $service->getFieldMapByFieldType('entity_reference');
    // Additional code to allow "preloading" option works with exposed filters.
    foreach ($settings as $name => $setting) {
      if (static::isField($name)) {
        if (isset($setting['more_options']['enable_preload']) && $setting['more_options']['enable_preload']) {
          $field_name = static::convertDatabaseFieldToFieldname($name);
          $count      = $setting['more_options']['preload_count'];
          $field      = $map[$this->view->getBaseEntityType()->id()][$field_name];
          $bundle     = reset($field['bundles']);
          /** @var \Drupal\field\Entity\FieldConfig $field_settings */
          $field_settings = $service
            ->getFieldDefinitions(
              $this->view->getBaseEntityType()->id(),
              $bundle
            )[$field_name];
          $form['#attached']['drupalSettings']['preloaded_entries'][$field_name] = $this->buildPreLoaded(
            $count,
            $field_settings
          );
        }
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormSubmit(&$form, FormStateInterface $form_state, &$exclude) {
    parent::exposedFormSubmit($form, $form_state, $exclude);
    // Additional code to make initial values
    // work correctly with exposed filters.
    /** @var \Drupal\Core\Entity\EntityFieldManager $service */
    $service = \Drupal::service('entity_field.manager');
    $map = $service->getFieldMapByFieldType('entity_reference');
    foreach ($form_state->getValues() as $name => $value) {
      if (static::isEntityReferenceField($name) && !empty($value) && is_array($value)) {
        $field_name = static::convertDatabaseFieldToFieldname($name);
        $field      = $map[$this->view->getBaseEntityType()->id()][$field_name];
        $bundle     = reset($field['bundles']);
        /** @var \Drupal\field\Entity\FieldConfig $field_settings */
        $field_settings = $service
          ->getFieldDefinitions(
            $this->view->getBaseEntityType()->id(),
            $bundle
          )[$field_name];
        $entities = \Drupal::entityTypeManager()
          ->getStorage($field_settings->getSetting('target_type'))
          ->loadMultiple($value);
        $values = [];
        if (!empty($entities)) {
          $values = array_map(function ($entity) {
            /** @var \Drupal\Core\Entity\EntityInterface $entity */
            return $entity->label();
          }, $entities);
        }
        $form['#attached']['drupalSettings']['initValues'][$field_name] = $values;
      }
    }
  }

}
