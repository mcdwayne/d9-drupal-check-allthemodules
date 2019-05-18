<?php

namespace Drupal\uc_attribute\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Displays options and the modifications to products they represent.
 */
class AttributeOptionsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_attribute_options_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $aid = NULL) {
    $attribute = uc_attribute_load($aid);

    $form['#title'] = $this->t('Options for %name', ['%name' => $attribute->name]);

    $form['options'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Default cost'),
        $this->t('Default price'),
        $this->t('Default weight'),
        $this->t('List position'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No options for this attribute have been added yet.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'uc-attribute-option-table-ordering',
        ],
      ],
    ];

    foreach ($attribute->options as $oid => $option) {
      $form['options'][$oid]['#attributes']['class'][] = 'draggable';
      $form['options'][$oid]['name'] = [
        '#markup' => $option->name,
      ];
      $form['options'][$oid]['cost'] = [
        '#theme' => 'uc_price',
        '#price' => $option->cost,
      ];
      $form['options'][$oid]['price'] = [
        '#theme' => 'uc_price',
        '#price' => $option->price,
      ];
      $form['options'][$oid]['weight'] = [
        '#markup' => (string) $option->weight,
      ];
      $form['options'][$oid]['ordering'] = [
        '#type' => 'weight',
        '#title' => $this->t('List position'),
        '#title_display' => 'invisible',
        '#default_value' => $option->ordering,
        '#attributes' => ['class' => ['uc-attribute-option-table-ordering']],
      ];
      $form['options'][$oid]['operations'] = [
        '#type' => 'operations',
        '#links' => [
          'edit' => [
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute('uc_attribute.option_edit', ['aid' => $attribute->aid, 'oid' => $oid]),
          ],
          'delete' => [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('uc_attribute.option_delete', ['aid' => $attribute->aid, 'oid' => $oid]),
          ],
        ],
      ];
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('options') as $oid => $option) {
      db_update('uc_attribute_options')
        ->fields([
          'ordering' => $option['ordering'],
        ])
        ->condition('oid', $oid)
        ->execute();
    }

    $this->messenger()->addMessage($this->t('The changes have been saved.'));
  }

}
