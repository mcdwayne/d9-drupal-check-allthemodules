<?php


use \Drupal\templating\Templating ;

function templating_drush_command() {
  $items = [];
  $items['cex-template'] = [
    'description' => 'Export Template',
      'aliases' => ['cext','cex-templating']
  ];
  $items['cim-template'] = [
    'description' => 'Import Template',
    'aliases' => ['cimt','cim-templating']
  ];

  return $items;
}
function drush_templating_cex_template() {
  $template = new Templating();
  $result =  $template->load_config_db_templating();
    if(!empty($result)){
      if (drush_confirm(dt('Find items '.sizeof($result).' , Are you sure to continue the process ?'))) {
        $template->saveConfigTemplate($result);
      }else{
        drush_print('Template Export Canceled');
      }
    }else{
      drush_print('No items are found');
    }



}
function drush_content_export_yaml_cim_template() {
  $template  = new Templating();
  $result = $template->getConfigTemplate();
  if(!empty($result)){
    if (drush_confirm(dt('Find items '.sizeof($result).' , Are you sure to continue the process ?'))) {
      $total = sizeof($result);
      foreach ($result as $key => $item){
        $info = [
          'item' => $item,
          'index' => $key,
          'total' => $total,
        ];
        $operations[] = [
          'templating_import_index_batch_process',
          [$info],
        ];
      }
      $batch = array(
        'operations' => $operations,
        'title' => t('Import Template content process'),
        'init_message' => t('Starting...'),
        'progress_message' => t('Completed @current of @total.'),
        'error_message' => t('An error occurred'),
        'finished' => 'templating_batch_finish',
        'file' => drupal_get_path('module', 'templating') . '/drush/templating.batch.inc',
      );
      // Start the batch job.
      batch_set($batch);
      drush_backend_batch_process();
    }else{
      drush_print('Template Import Canceled');
    }
  }else{
    drush_print('No items are found');
  }

}


