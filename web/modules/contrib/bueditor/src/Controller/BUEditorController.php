<?php

namespace Drupal\bueditor\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for bueditor routes.
 */
class BUEditorController extends ControllerBase {

  /**
   * Returns an administrative overview of BUEditor Editors.
   */
  public function adminOverview() {
    $output['editors'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['bueditor-editor-list']],
      'title' => ['#markup' => '<h2>' . $this->t('Available editors') . '</h2>'],
      'list' => $this->entityTypeManager()->getListBuilder('bueditor_editor')->render(),
    ];
    $output['#attached']['library'][] = 'bueditor/drupal.bueditor.admin';
    return $output;
  }

  /**
   * Returns an administrative overview of BUEditor Buttons.
   */
  public function buttonsOverview() {
    // Custom buttons
    $output['custom_buttons'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['bueditor-button-list bbl-custom']],
      'title' => ['#markup' => '<h2>' . $this->t('Custom buttons') . '</h2>'],
      'list' => $this->entityTypeManager()->getListBuilder('bueditor_button')->render(),
    ];
    // Plugin buttons
    $groups = [];
    $header = [
      ['data' => $this->t('ID'), 'class' => 'button-id'],
      ['data' => $this->t('Name'), 'class' => 'button-label'],
    ];
    foreach (\Drupal::service('plugin.manager.bueditor.plugin')->getButtonGroups() as $key => $group) {
      $rows = [];
      foreach ($group['buttons'] as $bid => $data) {
        $rows[] = [$bid, isset($data['label']) ? $data['label'] : ''];
      }
      $groups[$key] = [
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#caption' => $group['label'],
        '#attributes' => ['class' => ['bueditor-button-group bbg-' . $key]],
      ];
    }
    $output['plugin_buttons'] = [
      '#type' => 'details',
      '#attributes' => ['class' => ['bueditor-button-list bbl-plugins']],
      '#title' => $this->t('Plugin buttons'),
      'list' => $groups,
    ];
    $output['#attached']['library'][] = 'bueditor/drupal.bueditor.admin';
    return $output;
  }

}
