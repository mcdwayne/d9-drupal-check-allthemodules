<?php

/**
 * @file
 * Drush commands provided by Patch Info module.
 */

/**
 * Implements hook_drush_command().
 */
function patchinfo_drush_command() {
  $items = [];

  $items['patchinfo-list'] = [
    'description' => 'Show a report of patches applied to Drupal core and contrib projects.',
    'arguments' => [
      'projects' => 'Optional. A list of installed projects to show.',
    ],
    'options' => [
      'pipe' => 'Return a list of the projects with any patches applied, one project per line.',
    ],
    'outputformat' => [
      'default' => 'table',
      'pipe-format' => 'list',
      'field-labels' => [
        'name' => 'Name',
        'label' => 'Title',
        'delta' => '#',
        'info' => 'Info',
      ],
      'fields-default' => ['label', 'delta', 'info'],
      'fields-pipe' => ['name'],
      'output-data-type' => 'format-table',
    ],
    'aliases' => ['pil', 'pi-list'],
  ];

  return $items;
}

/**
 * Command callback for patchinfo-list command.
 *
 * Displays patch info for installed projects.
 *
 * @param string $limit_projects
 *   Comma-separated list of machine-readable project names.
 */
function drush_patchinfo_list($limit_projects = '') {
  $table = [];

  $limit_projects = !empty($limit_projects) ? explode(',', $limit_projects) : [];

  $patch_info = _patchinfo_get_info(TRUE);
  if (count($patch_info) > 0) {
    // Get project information from update manager service.
    $projects = \Drupal::service('update.manager')->getProjects();

    $has_limit_projects = (count($limit_projects) > 0);
    foreach ($projects as $project) {
      if ($has_limit_projects && !in_array($project['name'], $limit_projects)) {
        continue;
      }
      $patches = _patchinfo_get_patches($patch_info, $project);
      if (count($patches) > 0) {
        $label = $project['info']['name'] . ' (' . $project['name'] . ')';
        if ($project['name'] == 'drupal') {
          $label = 'Drupal (drupal)';
        }
        foreach ($patches as $delta => $patch) {
          $patchinfo_list_row = [
            'name' => $project['name'],
            'label' => $label,
            'delta' => $delta,
            'info' => chunk_split($patch['info']) . $patch['url'],
          ];
          drush_command_invoke_all_ref('patchinfo_list_row_alter', $patchinfo_list_row, $patch);
          $table[$project['name'] . '-' . $delta] = $patchinfo_list_row;
        }
      }
    }
  }

  return $table;
}
