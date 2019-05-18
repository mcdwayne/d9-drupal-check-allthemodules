<?php

namespace Drupal\patchinfo\Commands;

use Drush\Commands\DrushCommands;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Consolidation\OutputFormatters\Options\FormatterOptions;
use Drupal\update\UpdateManagerInterface;

/**
 * A Drush commandfile for the patchinfo:list command.
 */
class PatchInfoCommands extends DrushCommands {

  /**
   * Show a report of patches applied to Drupal core and contrib projects.
   *
   * @command patchinfo:list
   * @field-labels
   *   name: Name
   *   label: Title
   *   delta: Delta
   *   info: Info
   *   url: URL
   * @default-string-field name
   * @usage patchinfo-list --projects=drupal
   * @usage patchinfo-list --projects=drupal,pathauto
   * @usage patchinfo-list --format=yaml
   * @usage patchinfo-list --format=csv
   * @usage patchinfo-list --fields=project,info
   * @usage patchinfo-list --fields=Project,Delta
   * @aliases patchinfo-list, pil, pi-list
   * @hidden
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   Patch information as rows of fields.
   */
  public function list(array $options = [
    'projects' => NULL,
    'format' => 'table',
    'fields' => 'name,label,delta,info',
  ]) {

    $table = $this->getTableData($options['projects']);
    $data = new RowsOfFields($table);

    $data->addRendererFunction(
    // n.b. There is a fourth parameter $rowData that may be added here.
      function ($key, $cellData, FormatterOptions $options, $rowData) {
        if ($key === 'name') {
          return "<comment>$cellData</>";
        }
        if ($key === 'info') {
          return chunk_split($rowData['info']) . $rowData['url'];
        }
        return $cellData;
      }
    );

    return $data;
  }

  /**
   * Update manager service.
   *
   * @var \Drupal\update\UpdateManagerInterface
   */
  protected $updateManager;

  /**
   * Constructs update status data.
   *
   * @param \Drupal\update\UpdateManagerInterface $update_manager
   *   Update Manager Service.
   */
  public function __construct(UpdateManagerInterface $update_manager) {
    $this->updateManager = $update_manager;
  }

  /**
   * Returns table data for all patches in projects.
   *
   * @param string $projects
   *   List of projects to include.
   *
   * @return array
   *   Table data of all patches
   */
  protected function getTableData(string $projects = NULL) {
    $table = [];

    $limit_projects = !empty($projects) ? explode(',', $projects) : [];

    $patch_info = _patchinfo_get_info(TRUE);
    if (count($patch_info) === 0) {
      return $table;
    }
    // Get project information from update manager service.
    $projects = $this->updateManager->getProjects();

    $has_limit_projects = (count($limit_projects) > 0);
    foreach ($projects as $project) {
      if ($has_limit_projects && !in_array($project['name'], $limit_projects, TRUE)) {
        continue;
      }
      $patches = _patchinfo_get_patches($patch_info, $project);
      if (count($patches) > 0) {
        $label = $project['info']['name'] . ' (' . $project['name'] . ')';
        if ($project['name'] === 'drupal') {
          $label = 'Drupal (drupal)';
        }
        foreach ($patches as $delta => $patch) {
          $patchinfo_list_row = [
            'name' => $project['name'],
            'label' => $label,
            'delta' => $delta,
            'info' => chunk_split($patch['info']) . $patch['url'],
            'url' => $patch['url'],
          ];
          $table[$project['name'] . '-' . $delta] = $patchinfo_list_row;
        }
      }
    }
    return $table;
  }

}
