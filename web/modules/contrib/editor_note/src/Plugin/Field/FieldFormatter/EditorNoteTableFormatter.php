<?php

namespace Drupal\editor_note\Plugin\Field\FieldFormatter;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\Annotation\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'Editor Note' formatter.
 *
 * @FieldFormatter(
 *   id = "editor_note_table_formatter",
 *   label = @Translation("Editor Note table"),
 *   module = "editor_note",
 *   field_types = {
 *     "editor_note_item"
 *   }
 * )
 */
class EditorNoteTableFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entity = $items->getEntity();
    /** @var \Drupal\editor_note\EditorNoteHelperService $note_helper */
    $note_helper = \Drupal::service('editor_note.helper');
    $notes = $note_helper->getNotesByEntityAndField($entity->id(), $this->fieldDefinition->getName());
    $field = $this->fieldDefinition;
    $table = $note_helper->generateTable($field, $notes, FALSE);

    if (!isset($table['notes_table'])) {
      return [];
    }

    $data = $table['notes_table'];
    $data['#prefix'] = $table['#prefix'];
    $data['#suffix'] = $table['#suffix'];
    return $data;
  }

}
