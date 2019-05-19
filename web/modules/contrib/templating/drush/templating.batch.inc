<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
use \Drupal\templating\Templating ;


//Bacth Function for deleting a node
function templating_export_index_batch_process($info, $context)
{

    // check if numeric

//    try {
//      $export = new ContentExport();
//      $status = $export->exportContent($info['id']);
//      if($status){
//        drush_print('Success exported id = '.$info['id']);
//      }
//
//    } catch (\Exception $e) {
//        $message = "Item :" . $info['id']. " Error: " . $e->getMessage();
//        \Drupal::logger("content_export_yaml")->error($message);
//    }


}
//Bacth Function for deleting a node
function templating_import_index_batch_process($info, $context)
{

  // check if numeric

  try {
    $template = new Templating();
    $status = $template->save_item($info['item']);
    if($status){
      drush_print('Success imported id = '.$info['index']);
    }
  } catch (\Exception $e) {
    $message = "Item :" . $info['index']. " Error: " . $e->getMessage();
    \Drupal::logger("templating")->error($message);
  }


}
