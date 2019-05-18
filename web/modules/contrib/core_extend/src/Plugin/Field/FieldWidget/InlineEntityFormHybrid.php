<?php

namespace Drupal\core_extend\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormComplex;
use Drupal\inline_entity_form\TranslationHelper;

/**
 * Complex inline widget.
 *
 * @FieldWidget(
 *   id = "inline_entity_form_hybrid",
 *   label = @Translation("Inline entity form - Hybrid"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = true
 * )
 */
class InlineEntityFormHybrid extends InlineEntityFormComplex implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  protected function prepareFormState(FormStateInterface $form_state, FieldItemListInterface $items, $translating = FALSE) {
    $widget_state = $form_state->get(['inline_entity_form', $this->iefId]);
    if (empty($widget_state)) {
      $widget_state = [
        'instance' => $this->fieldDefinition,
        'form' => NULL,
        'delete' => [],
        'entities' => [],
      ];
      // Store the $items entities in the widget state for further manipulation.
      foreach ($items as $delta => $item) {
        $entity = $item->entity;
        // The $entity can be NULL if the reference is broken.
        if ($entity) {
          // Display the entity in the correct translation.
          if ($translating) {
            $entity = TranslationHelper::prepareEntity($entity, $form_state);
          }
          $widget_state['entities'][$delta] = [
            'entity' => $entity,
            'weight' => $delta,
            'form' => 'edit',
            'needs_save' => $entity->isNew(),
          ];
        }
      }
      $form_state->set(['inline_entity_form', $this->iefId], $widget_state);
    }
  }

  /**
   * Validates the form when removing an item.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateConfirmRemove(&$form, FormStateInterface $form_state) {
    $form_state->clearErrors();
  }

  /**
   * Remove form submit callback.
   *
   * The row is identified by #ief_row_delta stored on the triggering
   * element.
   * This isn't an #element_validate callback to avoid processing the
   * remove form when the main form is submitted.
   *
   * @param array $form
   *   The complete parent form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the parent form.
   */
  public static function submitConfirmRemove($form, FormStateInterface $form_state) {
    $element = inline_entity_form_get_element($form, $form_state);
    $remove_button = $form_state->getTriggeringElement();
    $delta = $remove_button['#ief_row_delta'];

    /** @var \Drupal\Core\Field\FieldDefinitionInterface $instance */
    $instance = $form_state->get([
      'inline_entity_form',
      $element['#ief_id'],
      'instance',
    ]);

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $element['entities'][$delta]['#entity'];
    $entity_id = $entity->id();

    $form_values = NestedArray::getValue($form_state->getValues(), $element['entities'][$delta]['form']['#parents']);
    $form_state->setRebuild();

    $widget_state = $form_state->get(['inline_entity_form', $element['#ief_id']]);
    // This entity hasn't been saved yet, we can just unlink it.
    if (empty($entity_id) || ($remove_button['#allow_existing'] && empty($form_values['delete']))) {
      unset($widget_state['entities'][$delta]);
    }
    else {
      $widget_state['delete'][] = $entity;
      unset($widget_state['entities'][$delta]);
    }
    $form_state->set(['inline_entity_form', $element['#ief_id']], $widget_state);
  }

  /**
   * Adds actions to the inline entity form.
   *
   * @param array $element
   *   Form array structure.
   *
   * @return array
   *   The modifed element.
   */
  public static function buildEntityFormActions($element) {
    // Build a delta suffix that's appended to button #name keys for uniqueness.
    $iefId = $element['#ief_id'];
    $delta = $element['#ief_row_delta'];

    // Add action submit elements.
    $element['actions'] = [
      '#type' => 'container',
      '#weight' => 100,
    ];

    // Build a deta suffix that's appended to button #name keys for uniqueness.
    $name_delta = $iefId . '-' . $delta;

    $element['actions']['ief_remove_confirm'] = [
      '#type' => 'submit',
      '#value' => t('Remove'),
      '#name' => 'ief-remove-confirm-' . $name_delta,
      '#limit_validation_errors' => [$element['#parents']],
      '#validate' => [[self::class, 'validateConfirmRemove']],
      '#ajax' => [
        'callback' => 'inline_entity_form_get_element',
        'wrapper' => 'inline-entity-form-' . $iefId,
      ],
      '#allow_existing' => TRUE,
      '#submit' => [[self::class, 'submitConfirmRemove']],
      '#ief_row_delta' => $delta,
    ];

    return $element;
  }

}
