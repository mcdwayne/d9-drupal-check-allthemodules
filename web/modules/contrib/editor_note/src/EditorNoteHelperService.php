<?php

namespace Drupal\editor_note;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Class EditorNoteHelperService.
 */
class EditorNoteHelperService {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Current user object.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Constructs a new OnboardStationEntityHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   Date format service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   Current user object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    DateFormatter $date_formatter,
    Connection $connection,
    AccountProxy $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
    $this->connection = $connection;
    $this->currentUser = $current_user;
  }

  /**
   * Create 'Editor Node' entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $host_entity
   *   Host entity to attach editor note.
   * @param string $field_machine_name
   *   Host entity editor note field machine name.
   * @param string $note
   *   Editor note.
   * @param string $format
   *   Text format.
   */
  public function createNote(ContentEntityInterface $host_entity, $field_machine_name, $note, $format = 'plain_text') {
    $storage = $this->entityTypeManager->getStorage('editor_note');

    $data = [
      'entity_id' => $host_entity->id(),
      'revision_id' => $host_entity->getRevisionId(),
      'note' => [
        'value' => $note,
        'format' => $format,
      ],
      'bundle' => $host_entity->bundle(),
      'field_machine_name' => $field_machine_name,
    ];

    $note = $storage->create($data);
    $note->save();
  }

  /**
   * Returns Editor Note entity ids for passed entity and field name.
   *
   * @param int $host_entity_id
   *   Host entity ID.
   * @param string $field_machine_name
   *   Field machine name.
   *
   * @return mixed
   *   A single field from the next record, or FALSE if there is no next record.
   */
  public function getNotesByEntityAndField($host_entity_id, $field_machine_name) {
    $query = $this->connection->select('editor_note', 'en');
    $query->fields('en', ['id']);
    $query->condition('en.entity_id', $host_entity_id);
    $query->condition('en.field_machine_name', $field_machine_name);
    $record_ids = $query->execute()->fetchAllKeyed(0, 0);

    if ($record_ids) {
      return $this->entityTypeManager->getStorage('editor_note')->loadMultiple($record_ids);
    }

    return [];
  }

  /**
   * Returns formatted notes table.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   Field definition object.
   * @param array $notes
   *   Array of notes data returned by editor_note_get_notes().
   * @param bool $widget
   *   Determines whether to use function in widget along with controls or
   *   display it in formatter as just a table without controls.
   * @param string|null $edit_path
   *   Path of the edit form where field is used. Fixes pager on ajax refresh.
   *
   * @return array
   *   Returns formatted notes ready for rendering.
   *
   * @see editor_note_get_notes()
   */
  public function generateTable(
    FieldDefinitionInterface $field,
    array $notes,
    $widget = FALSE,
    $edit_path = NULL) {
    $formatted_notes = [
      '#prefix' => '<div id="formatted_notes_' . $field->getName() . '">',
      '#suffix' => '</div>',
    ];

    if (!empty($notes)) {
      $rows = [];
      $counter = 0;
      $headers = $this->generateHeaders($widget);

      foreach ($notes as $note_id => $item) {
        $rows[$counter] = $this->generateRow($item, $widget, $field->getName(), $note_id);
        $counter++;
      }

      $notes_table = [
        '#theme' => 'table',
        '#header' => $headers,
        '#rows' => $rows,
        '#attributes' => [
          'class' => ['field-notes-table'],
        ],
      ];

      if (FALSE) {
        // @todo: Implement it.
//      if ($field['settings']['pager']['enabled']) {
        // An optional integer to distinguish between multiple pagers on one page
        // in case if 2 fields are present at the same time.
        static $page_element = 0;

        // Fixes pager on ajax refresh.
        // Otherwise pager links point on /system/ajax after ajax refresh.
        // @see https://www.drupal.org/node/1181370#comment-6088864
        // for more details.
        // @see theme_pager_link()
        if ($edit_path) {
          $_GET['q'] = $edit_path;
        }

        if ($field['settings']['pager']['pager_below']) {
          $formatted_notes['notes_table'] = $notes_table;
          $formatted_notes['notes_table_pager'] = [
            '#theme' => 'pager',
            '#element' => $page_element,
          ];
        }
        else {
          $formatted_notes['notes_table_pager'] = [
            '#theme' => 'pager',
            '#element' => $page_element,
          ];
          $formatted_notes['notes_table'] = $notes_table;
        }

        if (module_exists('field_group')) {
          // Remember which tab was active after page reload
          // when navigating between pager links.
          $settings = [
            'editorNoteContainer' => drupal_html_class('edit_link-' . $field['field_name']),
          ];
          $formatted_notes['notes_table']['#attached']['js'][] = [
            'data' => $settings,
            'type' => 'setting',
          ];
          $formatted_notes['notes_table']['#attached']['js'][] = drupal_get_path('module', 'editor_note') . '/js/editor_note.js';
        }

        $page_element++;
      }
      else {
        $formatted_notes['notes_table'] = $notes_table;
      }
    }

    // Hook is to allow other modules to alter the formatted notes
    // before they are rendered.
    //  drupal_alter('editor_note_format_notes', $formatted_notes);


    return $formatted_notes;
  }

  /**
   * Helper to prepare headers for the table.
   *
   * @param bool $widget
   *   Determines whether to use function in widget along with controls or
   *   display it in formatter as just a table without controls.
   *
   * @return array
   *   Headers data for the table.
   */
  protected function generateHeaders($widget) {
    $headers = [
      [
        'data' => $this->t('Notes'),
        'class' => ['field-label'],
      ],
      [
        'data' => $this->t('Updated by'),
        'class' => ['field-author'],
      ],
      [
        'data' => $this->t('Changed'),
        'class' => ['field-changed'],
      ],
      [
        'data' => $this->t('Created'),
        'class' => ['field-created'],
      ],
    ];

    if ($widget) {
      $headers[] = [
        'data' => $this->t('Actions'),
        'class' => ['field-operations'],
      ];
    }

    return $headers;
  }

  /**
   * Generates one Editor Notes table row.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $item
   *   Host entity to attach editor note.
   * @param bool $widget
   *   Determines whether to use function in widget along with controls or
   *   display it in formatter as just a table without controls.
   * @param string $field_name
   *   Field machine name.
   * @param int $note_id
   *   Row number.
   *
   * @return array
   *   Row data.
   */
  protected function generateRow(ContentEntityInterface $item, $widget, $field_name, $note_id) {
    /** @var \Drupal\user\UserInterface $author */
    $author = $item->uid->entity;
    $author_name = $author->label();

    $row = [
      'data' => [
        'note' => [
          'data' => Xss::filterAdmin($item->note->value),
          'class' => ['note'],
        ],
        'author' => [
          'uid' => $author->id(),
          'data' => $author->hasPermission('administer users') ? $author->toLink($author_name) : $author_name,
          'class' => ['author'],
        ],
        'changed' => [
          'data' => $this->dateFormatter->format($item->changed->value, 'short'),
          'class' => ['changed'],
        ],
        'created' => [
          'data' => $this->dateFormatter->format($item->changed->value, 'short'),
          'class' => ['created'],
        ],
      ],
      'class' => [Html::cleanCssIdentifier('note-' . $note_id)],
    ];

    $user_access = ($this->currentUser->id() == $item->uid->target_id)
      || $this->currentUser->hasPermission('administer any editor note');

    if ($widget && $user_access) {
      // !editor_note_access_crud_operations($field['field_name'], $note_id)
      $edit_link = [
        '#type' => 'link',
        '#title' => $this->t('Edit'),
        '#url' => Url::fromRoute('editor_note.modal_form', ['nojs' => 'ajax']),
        '#attributes' => [
          'class' => [
            'use-ajax',
            'ctools-modal-' . $field_name . '-edit_link',
          ],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => json_encode(['width' => '50%']),
          // Add this id so that we can test this form.
          'id' => 'ajax-example-modal-link',
        ],
      ];

      $delete_link = [
        '#type' => 'link',
        '#title' => $this->t('Remove'),
        '#url' => Url::fromRoute('editor_note.confirm_delete_editor_note_form', ['nojs' => 'ajax']),
        '#attributes' => [
          'class' => [
            'use-ajax',
            'ctools-modal-' . $field_name . '-remove',
          ],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => json_encode(['width' => '50%']),
          // Add this id so that we can test this form.
          'id' => 'ajax-example-modal-link',
        ],
      ];

      $basic_items[] = render($edit_link);
      $basic_items[] = render($delete_link);

      $row['data']['operations']['data'] = [
        '#theme' => 'item_list',
        '#items' => $basic_items,
      ];
    }

    return $row;
  }

}
