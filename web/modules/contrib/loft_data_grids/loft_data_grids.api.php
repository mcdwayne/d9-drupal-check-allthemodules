<?php
/**
 * @file
 * API documentation for loft_data_grids module.
 */

/**
 * Implements hook_loft_data_grids_info_alter(&$info).
 *
 * @param array &$info
 */
function hook_loft_data_grids_info_alter(&$info)
{
    if (isset($info['MarkdownExporter'])) {
        // Alter the class used for the markdown exporter.
        $info['MarkdownExporter']['class'] = 'JSmith\LoftDataGrids\MarkdownExporter';
    }
}

/**
 * Implements hook_loft_data_grids_exporters_info().
 */
function hook_loft_data_grids_exporters_info(&$exporters)
{

    // Add in another custom exporter object, leveraging it's getInfo hook.
    $info = new MyCoolExporter(new \AKlump\LoftDataGrids\ExportData);
    $exporters[] = $info->getInfo();
}


/**
 * Implements hook_loft_data_grids_exporters_alter().
 *
 * Alter the array of exporter definitions.
 */
function hook_loft_data_grids_exporters_alter(array &$exporters)
{

}
