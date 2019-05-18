<?php

namespace Drupal\formazing\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\formazing\Entity\FieldFormazingEntity;
use Drupal\formazing\FieldHelper\FieldAction;

/**
 * Class FormazingEntityFieldsOverview.
 *
 * @package Drupal\formazing\Form
 * @ingroup formazing
 */
class FormazingEntityFieldsOverview extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'formazing_elements_overview';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   *
   * @param FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    foreach ($form_state->getValues()['table'] as $key => $value) {
      $entity = FieldFormazingEntity::load($key);
      $entity->setWeight($value['weight']);
      $entity->save();
    }
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @param null $formazing_entity
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function buildForm(
    array $form, FormStateInterface $form_state, $formazing_entity = NULL
  ) {
    $ids = \Drupal::entityTypeManager()->getStorage('field_formazing_entity')->getQuery()->execute();

    $elements = FieldFormazingEntity::loadMultiple($ids);

    //fallback if simple form has no form elements
    if (!isset($elements)) {
      return $form;
    }

    $elements = array_filter($elements, function (FieldFormazingEntity $formazing) use ($formazing_entity) {
      return $formazing_entity === $formazing->getFormId();
    });

    $form['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Label', [], ['formazing']),
        $this->t('Type', [], ['formazing']),
        $this->t('Weight', [], ['formazing']),
        $this->t('Operations', [], ['formazing']),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'mytable-order-weight',
        ]
      ],
    ];

    uasort($elements, [FieldAction::class, 'orderWeight']);

    /** @var FieldFormazingEntity $element */
    foreach ($elements as $element) {
      $form['table'][$element->id()]['label'] = [
        '#type' => 'label',
        '#title' => $element->getName(),
      ];

      $form['table'][$element->id()]['#attributes']['class'] = [
        'draggable',
      ];

      $form['table'][$element->id()]['#weight'] = $element->getWeight();

      /** @var \Drupal\formazing\FieldSettings\TextField $fieldType */
      $fieldType = $element->getFieldType();

      $form['table'][$element->id()]['type'] = [
        '#type' => 'label',
        '#title' => ucfirst($fieldType::getMachineTypeName()),
      ];

      // TableDrag: Weight column element.
      // NOTE: The tabledrag javascript puts the drag handles inside the first column,
      // then hides the weight column. This means that tabledrag handle will not show
      // if the weight element will be in the first column so place it further as in this example.
      $form['table'][$element->id()]['weight'] = [
        '#type' => 'weight',
        '#title' => t('Weight for @title', ['@title' => $element->getName()]),
        '#title_display' => 'invisible',
        '#default_value' => $element->getWeight(),
        // Classify the weight element for #tabledrag.
        '#attributes' => [
          'class' => ['mytable-order-weight']
        ],
      ];
      $form['table'][$element->id()]['delete'] = [
        '#type' => 'operations',
        '#links' => [
          'edit' => [
            'title' => $this->t('Edit', [], ['formazing']),
            'url' => Url::fromRoute('entity.formazing_entity_field.edit', [
              'formazing_entity' => $formazing_entity,
              'field_formazing_entity' => $element->id(),
            ]),
          ],
          'delete' => [
            'title' => $this->t('Delete', [], ['formazing']),
            'url' => Url::fromRoute('entity.field_formazing_entity.delete_form', [
                'field_formazing_entity' => $element->id(),
              ]),
          ],
        ],
      ];
    }
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save order', [], ['formazing']),
      // TableSelect: Enable the built-in form validation for #tableselect for
      // this form button, so as to ensure that the bulk operations form cannot
      // be submitted without any selected items.
      '#tableselect' => TRUE,
    ];

    return $form;
  }
}