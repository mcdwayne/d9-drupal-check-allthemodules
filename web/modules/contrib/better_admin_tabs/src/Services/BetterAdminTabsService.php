<?php

namespace Drupal\better_admin_tabs\Services;

/**
 * Class BetterAdminTabs.
 *
 * @package Drupal\techbooks
 */
class BetterAdminTabsService {

  public function getDefaultValues() {
    $path = '/' . drupal_get_path('module', 'better_admin_tabs') . '/assets/dist/svg/';
    return [
      'entity.node.canonical' => [
        'color' => '#f0D223',
        'icon' => $path . 'settings.svg',
      ],
      'entity.node.edit_form' => [
        'color' => '#3d9970',
        'icon' => $path . 'edit.svg',
      ],
      'entity.node.delete_form' => [
        'color' => '#da2327',
        'icon' => $path . 'delete.svg',
      ],
      'entity.node.version_history' => [
        'color' => 'deeppink',
        'icon' => $path . 'revisions.svg',
      ],
      'entity.node.devel_load' => [
        'color' => 'orangered',
        'icon' => $path . 'devel.svg',
      ],
      'entity.node.content_translation_overview' => [
        'color' => 'blue',
        'icon' => $path . 'translate.svg',
      ],
      'entity.node.display' => [
        'color' => '#FFB30E',
        'icon' => $path . 'view.svg',
      ],
    ];
  }

  public function getDefaultColor($routename) {
    $config = \Drupal::config('better_admin_tabs.settings');
    $configKey = str_replace('.', '_', $routename);

    // Get color value from saved config form.
    if ($config->get($configKey . '_color')) {
      return $config->get($configKey . '_color');
    }

    // Get color value from default values.
    $defaults = $this->getDefaultValues();
    if (isset($defaults[$routename])) {
      return $defaults[$routename]['color'];
    }

    // Still nothing ? return a fallback color.
    return '#f0D223';
  }

  public function getDefaultIcon($routename) {
    $config = \Drupal::config('better_admin_tabs.settings');
    $configKey = str_replace('.', '_', $routename);

    // Get color value from saved config form.
    if ($config->get($configKey . '_icon')) {
      return $config->get($configKey . '_icon');
    }

    // Get color value from default values.
    $defaults = $this->getDefaultValues();
    if (isset($defaults[$routename])) {
      return $defaults[$routename]['icon'];
    }

    // Still nothing ? return a fallback icon.
    return '/' . drupal_get_path('module', 'better_admin_tabs') . '/assets/dist/svg/settings.svg';
  }

}
