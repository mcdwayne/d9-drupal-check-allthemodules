<?php

namespace Drupal\uc_attribute\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the class/product attributes options form.
 */
abstract class ObjectOptionsFormBase extends FormBase {

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
    return 'uc_object_options_form';
  }

  /**
   * Constructs Options Form array on behalf of subclasses.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildBaseForm(array $form, FormStateInterface $form_state) {
    $form['attributes']['#tree'] = TRUE;

    foreach ($this->attributes as $aid => $attribute) {
      $base_attr = uc_attribute_load($aid);
      if ($base_attr->options) {
        $form['attributes'][$aid]['options'] = [
          '#type' => 'table',
          '#header' => [
            $this->t('Options'),
            $this->t('Default'),
            $this->t('Cost'),
            $this->t('Price'),
            $this->t('Weight'),
            $this->t('List position'),
          ],
          '#caption' => ['#markup' => '<h2>' . $attribute->name . '</h2>'],
          '#empty' => $this->t('This attribute does not have any options.'),
          '#tabledrag' => [
            [
              'action' => 'order',
              'relationship' => 'sibling',
              'group' => 'uc-attribute-option-table-ordering',
            ],
          ],
        ];

        $query = db_select('uc_attribute_options', 'ao')
          ->fields('ao', [
            'aid',
            'oid',
            'name',
          ]);
        $query->leftJoin($this->optionTable, 'po', "ao.oid = po.oid AND po." . $this->idField . " = :id", [':id' => $this->idValue]);

        $query->addField('ao', 'cost', 'default_cost');
        $query->addField('ao', 'price', 'default_price');
        $query->addField('ao', 'weight', 'default_weight');
        $query->addField('ao', 'ordering', 'default_ordering');

        $query->fields('po', ['cost', 'price', 'weight', 'ordering'])
          ->addExpression('CASE WHEN po.ordering IS NULL THEN 1 ELSE 0 END', 'null_order');

        $query->condition('aid', $aid)
          ->orderBy('null_order')
          ->orderBy('po.ordering')
          ->orderBy('default_ordering')
          ->orderBy('ao.name');

        $result = $query->execute();
        foreach ($result as $option) {
          $oid = $option->oid;

          $form['attributes'][$aid]['options'][$oid]['#attributes']['class'][] = 'draggable';
          $form['attributes'][$aid]['options'][$oid]['select'] = [
            '#type' => 'checkbox',
            '#title' => $option->name,
            '#default_value' => isset($attribute->options[$oid]),
          ];
          $form['attributes'][$aid]['options'][$oid]['default'] = [
            '#type' => 'radio',
            '#title' => $this->t('Default'),
            '#title_display' => 'invisible',
            '#parents' => ['attributes', $aid, 'default'],
            '#return_value' => $oid,
            '#default_value' => $attribute->default_option,
          ];
          $form['attributes'][$aid]['options'][$oid]['cost'] = [
            '#type' => 'uc_price',
            '#title' => $this->t('Cost'),
            '#title_display' => 'invisible',
            '#default_value' => is_null($option->cost) ? $option->default_cost : $option->cost,
            '#size' => 6,
            '#allow_negative' => TRUE,
          ];
          $form['attributes'][$aid]['options'][$oid]['price'] = [
            '#type' => 'uc_price',
            '#title' => $this->t('Price'),
            '#title_display' => 'invisible',
            '#default_value' => is_null($option->price) ? $option->default_price : $option->price,
            '#size' => 6,
            '#allow_negative' => TRUE,
          ];
          $form['attributes'][$aid]['options'][$oid]['weight'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Weight'),
            '#title_display' => 'invisible',
            '#default_value' => is_null($option->weight) ? $option->default_weight : $option->weight,
            '#size' => 5,
          ];
          $form['attributes'][$aid]['options'][$oid]['ordering'] = [
            '#type' => 'weight',
            '#title' => $this->t('List position'),
            '#title_display' => 'invisible',
            '#delta' => 50,
            '#default_value' => is_null($option->ordering) ? $option->default_ordering : $option->ordering,
            '#attributes' => ['class' => ['uc-attribute-option-table-ordering']],
          ];
        }
      }
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save changes'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $error = FALSE;

    foreach ($form_state->getValue('attributes') as $attribute) {
      $selected_opts = [];
      if (isset($attribute['options'])) {
        foreach ($attribute['options'] as $oid => $option) {
          if ($option['select']) {
            $selected_opts[] = $oid;
          }
        }
      }
      if (!empty($selected_opts) && !in_array($attribute['default'], $selected_opts)) {
        $form_state->setErrorByName($attribute['default']);
        $error = TRUE;
      }
    }

    if ($error) {
      $this->messenger()->addError($this->t('All attributes with enabled options must specify an enabled option as default.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('attributes') as $aid => $attribute) {
      if (isset($attribute['default'])) {
        db_update($this->attributeTable)
          ->fields([
            'default_option' => $attribute['default'],
          ])
          ->condition($this->idField, $this->idValue)
          ->condition('aid', $aid)
          ->execute();
      }

      if (isset($attribute['options'])) {
        db_delete($this->optionTable)
          ->condition($this->idField, $this->idValue)
          ->condition('oid', array_keys($attribute['options']), 'IN')
          ->execute();

        foreach ($attribute['options'] as $oid => $option) {
          if ($option['select']) {
            unset($option['select']);
            $option[$this->idField] = $this->idValue;
            $option['oid'] = $oid;

            db_insert($this->optionTable)
              ->fields($option)
              ->execute();
          }
          else {
            $this->optionRemoved($aid, $oid);
          }
        }
      }
    }

    $this->messenger()->addMessage($this->t('The changes have been saved.'));
  }

  /**
   * Called when submission of this form caused an option to be removed.
   */
  protected function optionRemoved($aid, $oid) {
  }

}
