<?php

namespace Drupal\entity_generic;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the entity type.
 */
class GenericViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $table_name = $this->entityType->getDataTable() ?: $this->entityType->getBaseTable();

    // Filter by name using autocomplete
    $key = $this->entityType->getKey('id') . '_autocomplete';
    $data[$table_name][$key]['real field'] = $this->entityType->getKey('id');
    $data[$table_name][$key]['filter']['id'] = 'entity_generic_id_autocomplete';
    $data[$table_name][$key]['filter']['title'] = $this->t('ID (autocomplete)');
    $data[$table_name][$key]['filter']['help'] = $this->t('The entity ID. Uses an autocomplete widget to find an entity, the actual filter uses the resulting entity ID.');

    // Filter by name using select
    $key = $this->entityType->getKey('id') . '_select';
    $data[$table_name][$key]['real field'] = $this->entityType->getKey('id');
    $data[$table_name][$key]['filter']['id'] = 'entity_generic_id_select';
    $data[$table_name][$key]['filter']['title'] = $this->t('ID (select)');
    $data[$table_name][$key]['filter']['help'] = $this->t('The entity ID. Uses a select widget to find an entity, the actual filter uses the resulting entity ID.');

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function addEntityLinks(array &$data) {
    parent::addEntityLinks($data);

    $entity_type_id = $this->entityType->id();
    $t_arguments = ['@entity_type_label' => $this->entityType->getLabel()];

    if ($this->entityType->hasLinkTemplate('edit-modal-form')) {
      $data['edit_modal_' . $entity_type_id] = [
        'field' => [
          'title' => $this->t('Link to modal edit @entity_type_label', $t_arguments),
          'help' => $this->t('Provide an edit link for modal form to the @entity_type_label.', $t_arguments),
          'id' => 'entity_generic_link_edit_modal',
        ],
      ];
    }

    if ($this->entityType->hasLinkTemplate('delete-modal-form')) {
      $data['delete_modal_' . $entity_type_id] = [
        'field' => [
          'title' => $this->t('Link to modal delete @entity_type_label', $t_arguments),
          'help' => $this->t('Provide a delete link for modal form to the @entity_type_label.', $t_arguments),
          'id' => 'entity_generic_link_delete_modal',
        ],
      ];
    }

    if ($this->entityType->hasLinkTemplate('toggle-status-modal-form')) {
      $data['toggle_status_modal_' . $entity_type_id] = [
        'field' => [
          'title' => $this->t('Link to modal toggle status @entity_type_label', $t_arguments),
          'help' => $this->t('Provide a toggle status link for modal form to the @entity_type_label.', $t_arguments),
          'id' => 'entity_generic_toggle_status_modal',
        ],
      ];
    }

  }

}
