<?php

namespace Drupal\uc_attribute\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the class/product attributes overview form.
 */
abstract class ObjectAttributesFormBase extends FormBase {

  /**
   * The attributes.
   *
   * @var array
   */
  protected $attributes = [];

  /**
   * The attribute table that this form will write to.
   *
   * @var string
   */
  protected $attributeTable;

  /**
   * The option table that this form will write to.
   *
   * @var string
   */
  protected $optionTable;

  /**
   * The identifier field that this form will use.
   *
   * @var string
   */
  protected $idField;

  /**
   * The identifier value that this form will use.
   *
   * @var string
   */
  protected $idValue;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_object_attributes_form';
  }

  /**
   * Constructs Attributes Form array on behalf of subclasses.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  protected function buildBaseForm(array $form, FormStateInterface $form_state) {
    $form['attributes'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Remove'),
        $this->t('Name'),
        $this->t('Label'),
        $this->t('Default'),
        $this->t('Required'),
        $this->t('List position'),
        $this->t('Display'),
      ],
      '#empty' => $this->t('No attributes available.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'uc-attribute-table-ordering',
        ],
      ],
    ];

    foreach ($this->attributes as $aid => $attribute) {
      $option = isset($attribute->options[$attribute->default_option]) ? $attribute->options[$attribute->default_option] : NULL;
      $form['attributes'][$aid]['#attributes']['class'][] = 'draggable';
      $form['attributes'][$aid]['remove'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Remove'),
        '#title_display' => 'invisible',
      ];
      $form['attributes'][$aid]['name'] = [
        '#markup' => $attribute->name,
      ];
      $form['attributes'][$aid]['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#title_display' => 'invisible',
        '#default_value' => empty($attribute->label) ? $attribute->name : $attribute->label,
        '#size' => 20,
        '#maxlength' => 255,
      ];
      $form['attributes'][$aid]['option'] = [
        '#markup' => $option ? ($option->name . ' (' . uc_currency_format($option->price) . ')') : $this->t('n/a'),
      ];
      $form['attributes'][$aid]['required'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Required'),
        '#title_display' => 'invisible',
        '#default_value' => $attribute->required,
      ];
      $form['attributes'][$aid]['ordering'] = [
        '#type' => 'weight',
        '#title' => $this->t('List position'),
        '#title_display' => 'invisible',
        '#default_value' => $attribute->ordering,
        '#attributes' => ['class' => ['uc-attribute-table-ordering']],
      ];
      $form['attributes'][$aid]['display'] = [
        '#type' => 'select',
        '#title' => $this->t('Display'),
        '#title_display' => 'invisible',
        '#default_value' => $attribute->display,
        '#options' => _uc_attribute_display_types(),
      ];
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save changes'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $changed = FALSE;

    foreach ($form_state->getValue('attributes') as $aid => $attribute) {
      if ($attribute['remove']) {
        $remove_aids[] = $aid;
      }
      else {
        unset($attribute['remove']);
        db_merge($this->attributeTable)
          ->key('aid', $aid)
          ->fields($attribute)
          ->execute();
        $changed = TRUE;
      }
    }

    if (isset($remove_aids)) {
      $select = db_select('uc_attribute_options', 'ao')
        ->fields('ao', ['oid'])
        ->condition('ao.aid', $remove_aids, 'IN');
      db_delete($this->optionTable)
        ->condition('oid', $select, 'IN')
        ->condition($this->idField, $this->idValue)
        ->execute();

      db_delete($this->attributeTable)
        ->condition($this->idField, $this->idValue)
        ->condition('aid', $remove_aids, 'IN')
        ->execute();

      $this->attributesRemoved();

      $this->messenger()->addMessage($this->formatPlural(count($remove_aids), '1 attribute has been removed.', '@count attributes have been removed.'));
    }

    if ($changed) {
      $this->messenger()->addMessage($this->t('The changes have been saved.'));
    }
  }

  /**
   * Called when submission of this form caused attributes to be removed.
   */
  protected function attributesRemoved() {
  }

}
