<?php

namespace Drupal\ief_dnd_categories\Form;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;

class IefDndAdminForm extends ConfigFormBase {

  protected $alteredFields = [];

  public function getFormId()
  {
    return 'ief_dnd_admin_form';
  }

  public function getEditableConfigNames()
  {
    return [
      'ief_dnd_categories.settings'
    ];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $options = [
      '' => 'None',
    ];
    $optionsInfos = [];
    $bundlesInfos = \Drupal::service("entity_type.bundle.info")->getAllBundleInfo();
    foreach ($bundlesInfos as $contentType => $bundles) {
      foreach ($bundles as $bundle => $data) {
        if (\Drupal::service('entity_type.manager')
          ->getDefinition($contentType)
          ->isSubclassOf('\\Drupal\\Core\\Entity\\FieldableEntityInterface')) {
          $formModeConfig = entity_get_form_display($contentType, $bundle, 'default');
          $formComponents = $formModeConfig->getComponents();
          foreach($formComponents as $property => $config) {
            if (isset($config['type']) && $config['type'] === 'inline_entity_form_complex') {
              $optionsInfos[$contentType][] = [
                'bundle' => $bundle,
                'field' => $property
              ];
              $bundleFieldString = $contentType . '::' . $bundle . '::' . $property;
              $bundleFieldOptions = $this->getCategoryField($contentType, $bundle, $property);
              if (!empty($bundleFieldOptions)) {
                $optionVal = $bundleFieldString . '::' . $bundleFieldOptions;
                $options[$optionVal] = $bundleFieldString;
              }
            }
          }
        }
      }
    }

    $form = [
      'category_fields' => [
        '#type' => 'select',
        '#multiple' => TRUE,
        '#required' => TRUE,
        '#options' => $options,
        '#default_value' => $this->config('ief_dnd_categories.settings')->get('category_fields'),
        '#title' => 'Category field',
        '#description' => 'Entity reference fields with inline entity form complex widget and multiple cardinality, shown as "content type"::"bundle"::"entity inline form field". The inline entity must have one field reference to a single taxonomy vocabulary.',
        '#attributes' => ['id' => 'category-field']
      ],
      'actions' => [
        'alter_fields' => [
          '#type' => 'submit',
          '#value' => 'Alter fields with d&d categories'
        ],
      ],
    ];
    return $form;
  }

  public function getCategoryField($contentType, $bundle, $property) {
    $bundleFields = \Drupal::entityManager()->getFieldDefinitions($contentType, $bundle);
    $bundleFieldsStorage = \Drupal::entityManager()->getFieldStorageDefinitions($contentType);
    $entityReferenceFieldCardinality = $bundleFieldsStorage[$property]->getCardinality();
    if (isset($bundleFields[$property]) && $entityReferenceFieldCardinality !== 1) {
      $bundleFieldSettings = $bundleFields[$property]->getSettings();
      $fieldContentType = preg_replace('/^[^:]*:/', '', $bundleFieldSettings['handler']);
      if (isset($bundleFieldSettings['handler_settings']['target_bundles'])) {
        $fieldBundles = $bundleFieldSettings['handler_settings']['target_bundles'];
        if (empty($fieldBundles)) {
          $fieldBundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($fieldContentType);
        }
        foreach ($fieldBundles as $bundle) {
          $fieldDefinition = \Drupal::entityManager()->getFieldDefinitions($fieldContentType, $bundle);
          foreach ($fieldDefinition as $key => $data) {
            if (strpos($key, 'field_') === 0) {
              $fieldSettings = $data->getSettings();
              if (isset($fieldSettings['handler'])) {
                if (preg_match('/:taxonomy_term$/', $fieldSettings['handler']) === 1) {
                  $fieldCategoryVocabulary = reset($fieldSettings['handler_settings']['target_bundles']);
                  return $fieldContentType . '::' . $key . '::' . $fieldCategoryVocabulary;
                }
              }
            }
          }
        }
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('ief_dnd_categories.settings')
      ->set('category_fields', $values['category_fields'])
      ->save();
    parent::submitForm($form, $form_state);
  }
}
