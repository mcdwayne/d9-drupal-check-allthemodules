<?php

namespace Drupal\moderation_note;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the entity.
 */
class ModerationNoteViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['moderation_note']['table']['provider'] = 'moderation_note';

    $data['moderation_note']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Moderation Notes'),
      'help' => $this->t('Moderation Notes'),
      'weight' => -10,
    ];

    $data['moderation_note']['node'] = [
      'real field' => 'entity_id',
      'relationship' => [
        'title' => $this->t('Notated content'),
        'help' => $this->t('Content the Moderation Note it is attached to.'),
        'base' => 'node_field_data',
        'base field' => 'nid',
        'id' => 'standard',
        'label' => $this->t('Notated content'),
        'extra' => [[
          'left_field' => 'entity_langcode',
          'field' => 'langcode',
        ],
        ],
      ],
    ];

    $data['moderation_note']['link'] = [
      'field' => [
        'title' => $this->t('Link to note'),
        'help' => $this->t('Provide link to view the note.'),
        'id' => 'moderation_note_link',
        'click sortable' => FALSE,
      ],
    ];

    $data['moderation_note']['parent']['filter']['allow empty'] = TRUE;
    $data['moderation_note']['uid']['filter']['id'] = 'user_name';
    $data['moderation_note']['assignee']['filter']['id'] = 'user_name';

    return $data;
  }

}
