<?php

/**
 * @class
 * UI Controller for a Recurly Adjustment.
 */
class RecurlyAdjustmentUIController extends EntityDefaultUIController {
  /**
   * Overrides hook_menu() defaults.
   */
  public function hook_menu() {
    $items = parent::hook_menu();
    $wildcard = isset($this->entityInfo['admin ui']['menu wildcard']) ? $this->entityInfo['admin ui']['menu wildcard'] : '%entity_object';

    $items[$this->path]['type'] = MENU_LOCAL_TASK;

    // Edit and delete are removed by setting the entity status to
    // ENTITY_FIXED.
    unset($items[$this->path . '/add']);
    unset($items[$this->path . '/manage/' . $wildcard . '/clone']);

    return $items;
  }

  /**
   * Overrides overviewTableRow().
   *
   * Removes all links to clone an adjustment, since adjustments must be
   * created through Recurly.
   */
  protected function overviewTableRow($conditions, $id, $entity, $additional_cols = []) {
    $entity_uri = entity_uri($this->entityType, $entity);

    $row[] = ['data' => [
      '#theme' => 'entity_ui_overview_item',
      '#label' => entity_label($this->entityType, $entity),
      '#name' => !empty($this->entityInfo['exportable']) ? entity_id($this->entityType, $entity) : FALSE,
      '#url' => $entity_uri ? $entity_uri : FALSE,
      '#entity_type' => $this->entityType],
    ];

    // Add in any passed additional cols.
    foreach ($additional_cols as $col) {
      $row[] = $col;
    }

    // Add a row for the exportable status.
    if (!empty($this->entityInfo['exportable'])) {
      $row[] = ['data' => [
        '#theme' => 'entity_status',
        '#status' => $entity->{$this->statusKey},
      ]];
    }
    // In case this is a bundle, we add links to the field ui tabs.
    $field_ui = !empty($this->entityInfo['bundle of']) && entity_type_is_fieldable($this->entityInfo['bundle of']) && module_exists('field_ui');
    // For exportable entities we add an export link.
    $exportable = !empty($this->entityInfo['exportable']);
    // If i18n integration is enabled, add a link to the translate tab.
    $i18n = !empty($this->entityInfo['i18n controller class']);

    // Add operations depending on the status.
    if ($exportable) {
      $row[] = l(t('export'), $this->path . '/manage/' . $id . '/export');
    }
    return $row;
  }
}
