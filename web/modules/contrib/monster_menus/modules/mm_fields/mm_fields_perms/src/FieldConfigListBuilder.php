<?php

namespace Drupal\mm_fields_perms;

use Drupal\Core\Entity\EntityInterface;
use Drupal\field_ui\FieldUI;
use Drupal\monster_menus\Constants;

class FieldConfigListBuilder extends \Drupal\field_ui\FieldConfigListBuilder {

  /** @var array Default tooltip */
  private $defaultTip;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return parent::buildHeader() + [
      'mm_fields_perms' => [
        'data' => $this->t('Permissions'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $field_config) {
    /** @var \Drupal\field\FieldConfigInterface $field_config */
    $row = parent::buildRow($field_config);

    $settings = $field_config->getThirdPartySettings('mm_fields_perms');
    if (isset($settings['use_defaults']) && !$settings['use_defaults']) {
      $row['data']['mm_fields_perms']['data'] = $this->tooltip(
        $settings['default_modes'] ? $settings['default_modes'][0] : '',
        $settings['users'],
        $settings['groups'],
        $this->t('custom'),
        $this->t('Custom Permissions')
      );
    }
    else {
      if (!isset($this->defaultTip)) {
        _mm_fields_perms_defaults(_mm_fields_perms_get_entity_type_config($field_config), $default_modes, $users, $groups);
        $this->defaultTip = $this->tooltip(
          !empty($default_modes[0]) ? $default_modes[0] : FALSE,
          $users,
          $groups,
          $this->t('default'),
          $this->t('Default Permissions')
        );
      }
      $row['data']['mm_fields_perms']['data'] = $this->defaultTip;
    }

    return $row;
  }


  /**
   * Generate a tooltip for a given field in the field overview table, which shows
   * the permissions for that field at a glance.
   */
  private function tooltip($default_mode, $users, $groups, $link_text, $tip_title) {
    $table = array();
    $table[] = $this->tooltipRow($this->t('<strong>Everyone:</strong>'), '', $default_mode);

    if ($users) {
      $rows = array();
      foreach ($users as $mode => $list) {
        foreach ($list as $uid) {
          $name = mm_content_uid2name($uid);
          $rows[(string) $name] = $this->tooltipRow('', $name, $mode);
        }
      }
      ksort($rows);
      $rows[mm_ui_mmlist_key0($rows)]['data'][0] = $this->t('<strong>Individuals:</strong>');
      $table = array_merge($table, $rows);
    }

    if ($groups) {
      $rows = array();
      foreach ($groups as $mode => $list) {
        foreach ($list as $gid) {
          $name = mm_content_get_name($gid);
          $rows[(string) $name] = $this->tooltipRow('', $name, $mode);
        }
      }
      ksort($rows);
      $rows[mm_ui_mmlist_key0($rows)]['data'][0] = $this->t('<strong>Groups:</strong>');
      $table = array_merge($table, $rows);
    }

    return [
      '#theme' =>'tooltip',
      '#text' => $link_text,
      '#title' => $tip_title,
      '#tip' => [
        '#theme' => 'table',
        '#header' => [],
        '#rows' => $table,
      ],
      '#html' => TRUE,
    ];

  }

  /**
   * Translate a permissions mode into a human-readable string, and return an
   * array for the entire table row.
   */
  private function tooltipRow($col1, $col2, $perm) {
    if ($perm == Constants::MM_PERMS_WRITE) {
      $mode = $this->t('edit/view');
    }
    else if ($perm == Constants::MM_PERMS_READ) {
      $mode = $this->t('view');
    }
    else {
      $mode = $this->t('(none)');
    }
    return ['no_striping' => TRUE, 'data' => [$col1, $col2, $mode]];
  }

}