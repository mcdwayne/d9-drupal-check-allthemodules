<?php

/**
 * @file
 * Contains \Drupal\time_spent\Controller\timeSpentController.
 */

namespace Drupal\imageproperty_check\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Url;
use Drupal;


class imagepropertyCheckController extends ControllerBase {

  public function __construct() {
    $this->database = Drupal::database();
    $this->imageproperty_check_config = Drupal::config('imageproperty_check.settings');
    $this->imageproperty_check_cron_pager = Drupal::config('imageproperty_check_pager_cron.settings');

  }
  public function imagepropertyCheckReportsDisplay() {
    $header = array(
    t('Image id'),
    t('Image name'),
    t('Size'),
    t('Location of the file'),
    );
    $rows = array();
    $pager = $this->imageproperty_check_cron_pager->get('imageproperty_check_pager');
    $query = db_select('imageproperty_check', 'ip')
    ->fields('ip', array('image_id', 'image_name', 'image_size', 'image_path'));
    // $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    // $query->limit($pager);
    $images_glitches = $query->execute()->fetchAll();
    if(!$images_glitches) {
      $output .= "<br />";
      $output .= t('There are no images with glitches in the memory size
      because no maximum image size is been set for image presets. You can
      set the values for the maximum memory size of each image preset
      style') ;
      $output .= "<br />";
    }
    else {
      $output .= "<br />";
      $output .= t("<h3>Images which exceed certain size limit </h3>");
      foreach ($images_glitches as $row) {
        $rows[] = array(
          $row->image_id,
          $row->image_name,
          format_size($row->image_size),
          $row->image_path,
        );
      }
    }
    return array(
    '#type' => 'table',
    '#prefix' => $output,
    '#attributes' => array('style' => 'width:1000px'),
    '#header' => $header,
    '#rows' => $rows,
    );

  }

  public function imagepropertyCheckReports() {
    $form = \Drupal::formBuilder()->getForm('Drupal\imageproperty_check\Form\ImagepropertyCheckRunCron');
    $form_render = drupal_render($form);
    db_delete('imageproperty_check')
     ->execute();
    $list_image_style = image_style_options();
    unset($list_image_style['']);
    foreach ($list_image_style as $key => $value) {
      $images = file_scan_directory('public://styles/' . $key, '/.*/');
      $imageproperty_check_value = $this->imageproperty_check_config->get('imageproperty_check_type_'.$key);
      if ($imageproperty_check_value != 0) {
        foreach ($images as $image_obj) {
          $uri = $image_obj->uri;
          $image = Drupal::service('image.factory')->get($uri);
          $image_name = $image_obj->name;
          $image_path = $image_obj->uri;
          $image_filename = $image_obj->filename;
          $image_size_bs = $image->getFileSize();
          $image_size = ($image_size_bs) / 1000;
          if ($image_size > $imageproperty_check_value) {
            $this->database->insert('imageproperty_check')
            ->fields(array(
              'image_name' => $image_name,
              'image_size' => $image_size_bs,
              'image_path' => $image_path,
              'image_filename' => $image_filename,
            ))
            ->execute();
          }
        }
      }
    }
    $header = array(
    t('Image id'),
    t('Image name'),
    t('Size'),
    t('Location of the file'),
    );
    $rows = array();
    $query = db_select('imageproperty_check', 'ip')
    ->fields('ip', array('image_id', 'image_name', 'image_size', 'image_path'));
    $images_glitches = $query->execute()->fetchAll();
    $pager = $this->imageproperty_check_cron_pager->get('imageproperty_check_pager');
    if(!$images_glitches) {
      $output .= "<br />";
      $output .= t('There are no images with glitches in the memory size
      because no maximum image size is been set for image presets. You can
      set the values for the maximum memory size of each image preset
      style') ;
      $output .= "<br />";
    }
    else {
      $output .= "<br />";
      $output .= t("<h3>Images which exceed certain size limit </h3>");
      foreach ($images_glitches as $row) {
        $rows[] = array(
          $row->image_id,
          $row->image_name,
          format_size($row->image_size),
          $row->image_path,
        );
      }
    }
    return array(
    '#type' => 'table',
    '#prefix' => $output,
    '#attributes' => array('style' => 'width:1000px'),
    '#header' => $header,
    '#rows' => $rows,
    );
  }
}