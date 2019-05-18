<?php

namespace Drupal\editor_note\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Editor note widget.
 *
 * @FieldWidget(
 *   id = "editor_note_widget",
 *   label = @Translation("Editor Note"),
 *   module = "editor_note",
 *   field_types = {
 *     "editor_note_item"
 *   }
 * )
 */
class EditorNoteWidget extends WidgetBase implements WidgetInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $entity = $items->getEntity();
    $wrapper_id = 'editor-notes-' . $delta;

    $element = [
      '#type' => 'details',
      '#title' => $this->t('Editor Notes'),
      '#element_validate' => [
        [$this, 'validate'],
      ],
      '#access' => $entity->get('path')->access('edit'),
      '#attributes' => [
        'class' => ['editor-notes-form'],
        'id' => $wrapper_id,
      ],
    ];

    /** @var \Drupal\editor_note\EditorNoteHelperService $note_helper */
    $note_helper = \Drupal::service('editor_note.helper');
    $notes = $note_helper->getNotesByEntityAndField($entity->id(), $this->fieldDefinition->getName());
    if ($notes) {
      $element['table'] = $note_helper->generateTable($this->fieldDefinition, $notes, TRUE);
    }

    $element['editor_notes'] = [
      '#type' => 'textarea',
      '#default_value' => '',
    ];

    // Add "Add Note" button to only existed entities.
    if (!$form_state->getFormObject()->getEntity()->isNew()) {
      $element['add_note'] = [
        '#type' => 'button',
        '#value' => $this->t('Add Note'),
        '#ajax' => [
          'callback' => [$this, 'onNoteSubmit'],
          'wrapper' => $wrapper_id,
        ],
      ];
    }

    return $element;
  }

  /**
   * Ajax callback for 'Add Note button'.
   *
   * @param array $form
   *   The form structure where widgets are being attached to. This might be a
   *   full form structure, or a sub-element of a larger form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form element.
   */
  public function onNoteSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element =& NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $host_entity = $form_state->getFormObject()->getEntity();
    $field_name = $button['#array_parents'][0];

    /** @var \Drupal\editor_note\EditorNoteHelperService $note_helper */
    $note_helper = \Drupal::service('editor_note.helper');
    // Save Editor Note entity.
    $note_helper->createNote($host_entity, $field_name, $element['editor_notes']['#value']);
    $notes = $note_helper->getNotesByEntityAndField($host_entity->id(), $field_name);
    // Rebuild Editor Notes table.
    $table = $note_helper->generateTable($this->fieldDefinition, $notes, TRUE);

    // Update some element values.
    $element['table'] = $table;
    $element['#open'] = TRUE;
    $element['editor_notes']['#value'] = '';

    return $element;
  }

  /**
   * Validate the color text field.
   *
   * @param array $element
   *   Element array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State object.
   */
  public function validate(array $element, FormStateInterface $form_state) {
    $value = $element['editor_notes']['#value'];
    $form_state->setValueForElement($element, ['value' => $value]);
  }

}
