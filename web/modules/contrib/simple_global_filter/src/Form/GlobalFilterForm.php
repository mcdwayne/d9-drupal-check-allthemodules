<?php

namespace Drupal\simple_global_filter\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;

/**
 * Class GlobalFilterForm.
 */
class GlobalFilterForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $global_filter = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $global_filter->label(),
      '#description' => $this->t("Label for the Global filter."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $global_filter->id(),
      '#machine_name' => [
        'exists' => '\Drupal\simple_global_filter\Entity\GlobalFilter::load',
      ],
      '#disabled' => !$global_filter->isNew(),
    ];

    $vocabularies = \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary')->loadMultiple();
    $options = [];
    foreach ($vocabularies as $id => $vocabulary) {
      $options[$id] = $vocabulary->label();
    }
    $form['vocabulary_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Vocabulary'),
      '#description' => $this->t('Select the taxonomy vocabulary related to this global filter.'),
      '#required' => TRUE,
      '#options' => $options,
      '#default_value' => $global_filter->getVocabulary(),
      '#ajax' => [
        'callback' => '::termsVocabularyCallback',
        'wrapper' => 'vocabulary-container',
      ],
    ];

    $form['wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'vocabulary-container'],
    ];

    $alias_description = $this->t('Select the field which contains the alias information.');
    if (($vocabulary_name = $form_state->getValue('vocabulary_name')) ||
        ($vocabulary_name = $global_filter->getVocabulary())) {

      $disabled = FALSE;

      $default_value_options = static::getTermsFromVocabulary($vocabulary_name);
      $default_value_title = $this->t('Default value for vocabulary @vocabulary',
        ['@vocabulary' => \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary')->load($vocabulary_name)->label()]);

      $alias_title = $this->t('Choose the alias field');
      $alias_options = [];
      $fields = \Drupal::service('entity_field.manager')->
         getFieldDefinitions('taxonomy_term', $vocabulary_name);
      foreach ($fields as $field_name => $field) {
        // Only admit fields of string field_type.
        if (($field instanceof FieldConfig) && ($field->getType() == "string")) {
          $alias_options[$field_name] = $field->getLabel();
        }
      }

      if (count($alias_options) == 0) {
        $alias_description = $this->t('There is not any field suitable for being alias. Please add a field of type "Text (plain)" to the chosen vocabulary.');
      }
    }
    else {
      $disabled = TRUE;
      $alias_options = $default_value_options = [];
      $alias_title = $default_value_title = $this->t('First choose a vocabulary.');
    }

    $default_value = $global_filter->getDefaultValue()?
          $global_filter->getDefaultValue() : ($form_state->getValue('default_value')?
              $form_state->getValue('default_value') : '' );
    $form['wrapper']['default_value'] = [
      '#type' => 'select',
      '#title' => $default_value_title,
      '#description' => $this->t('If there has not been selected any value yet, this value will be returned.'),
      '#options' => $default_value_options,
      '#default_value' => $default_value,
      '#disabled' => $disabled,
      '#required' => TRUE,
    ];

    $alias_default_value = $global_filter->getAliasField()?
          $global_filter->getAliasField() : ($form_state->getValue('alias_field')?
              $form_state->getValue('alias_field') : NULL );

    $form['wrapper']['use_alias'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use alias value'),
      '#description' => $this->t('Do you want to use aliases in the global filter values?'),
      '#default_value' => isset($alias_default_value),
    ];

    $form['wrapper']['alias'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'alias-container'],
    ];

    $form['wrapper']['alias']['alias_field'] = [
      '#type' => 'select',
      '#title' => $alias_title,
      '#description' => $alias_description,
      '#options' => $alias_options,
      '#disabled' => $disabled,
      '#default_value' => $alias_default_value,
      '#states' => [
        'invisible' => [
          ':input[name="use_alias"]' => array('checked' => FALSE),
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $global_filter = $this->entity;
    $use_alias = $form_state->getValue('use_alias');
    if (!$use_alias) {
      $global_filter->set('alias_field', '');
    }
    $status = $global_filter->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Global filter.', [
          '%label' => $global_filter->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Global filter.', [
          '%label' => $global_filter->label(),
        ]));
    }
    $form_state->setRedirectUrl($global_filter->toUrl('collection'));
  }

  public static function getTermsFromVocabulary($vocabulary_name) {
    $options = [];
    foreach (\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vocabulary_name, 0, NULL, TRUE) as $term) {
      $options[$term->id()] = $term->label();
    }
    return $options;
  }

  public function termsVocabularyCallback(array $form, FormStateInterface $form_state) {
    return $form['wrapper'];
  }
}
