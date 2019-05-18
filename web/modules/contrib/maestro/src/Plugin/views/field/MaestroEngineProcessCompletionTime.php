<?php

/**
 * @file
 * Definition of Drupal\maestro\Plugin\views\field\MaestroEngineProcessCompletionTime
 */

namespace Drupal\maestro\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\maestro\Utility\TaskHandler;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;


/**
 * Field handler to translate the unixtime timestamp to a human readable format if you so choose to.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("maestro_process_completed_time")
 */
class MaestroEngineProcessCompletionTime extends FieldPluginBase {

  /**
   * @{inheritdoc}
   */
  public function query() {
    // no Query to be done.
  }

  /**
   * Define the available options
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['date_format'] = ['default' => 'medium'];
  
    return $options;
  }
  
  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $options = [
      'unix' => $this->t('Database Timestamp'),
      'short' => $this->t('Short ( 12/01/1901 - 23:59 )'),
      'medium' => $this->t('Medium ( Tue, 12/01/1901 - 23:59 )'),
      'long' => $this->t('Long ( Tuesday, December 1, 1901 - 23:59 )'),
      'html_datetime' => $this->t('HTML5 Date/Time ( YYYY-MM-DDThh:mm:ssTZD )'),
    ];
    
    $form['date_format'] = array(
      '#title' => $this->t('Date Format'),
      '#type' => 'select',
      '#default_value' => isset($this->options['date_format']) ? $this->options['date_format'] : 'name',
      '#options' => $options,
    );
  
    parent::buildOptionsForm($form, $form_state);
  }
  
  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $item = $values->_entity;
    $timestamp = $item->completed->getString();
    $format = '';
    if($this->options['date_format'] != 'unix') {
      if($timestamp) $format = \Drupal::service('date.formatter')->format($timestamp, $this->options['date_format']);
    }
    else {
      $format = $timestamp;
    }
    return $format; 
    
  }
}