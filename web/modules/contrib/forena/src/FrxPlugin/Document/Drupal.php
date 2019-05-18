<?php
/**
 * @file Drupal.inc
 * Standard web document manager
 * @author metzlerd
 *
 */
namespace Drupal\forena\FrxPlugin\Document;

/**
 * Provides Drupal Rendering in a themed drupal page.
 *
 * @FrxDocument(
 *   id= "drupal",
 *   name="Drupal Render Array",
 *   ext="drupal"
 * )
 */
class Drupal extends DocumentBase {

  public function header() {
    $this->write_buffer='';
  }

  /**
   * @return array
   *   Drupal render array containing report.
   */
  public function flush() {
    $content=[];

    // Set Dynamic title for the page
    if ($this->title) {
      $content['#title'] = $this->title;
    }

    // Add the parameters form
    if ($this->parameters_form) {
      $content['parameters'] = $this->parameters_form;
    }

    // Add the skin library references
    if ($this->skin_name) {
      if (!empty($this->skin->info['library'])) {
        $content['#attached']['library'][] = 'forena/skin.' . $this->skin_name;
      }

      if (!empty($this->skin->info['libraries'])) {
        foreach ($this->skin->info['libraries'] as $library) {
          $content['#attached']['library'][] = $library;
        }
      }
    }

    // Add the content.
    $content['report']['#type'] = 'inline_template';
    $content['report']['#context'] = [];
    $content['report']['#template'] = $this->write_buffer;
    return $content;
  }


}