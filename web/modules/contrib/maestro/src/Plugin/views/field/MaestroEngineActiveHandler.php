<?php

/**
 * @file
 * Definition of Drupal\maestro\Plugin\views\field\MaestroEngineActiveHandler
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
 * Field handler to create a usable link to the task via the handler field
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("maestro_active_handler")
 */
class MaestroEngineActiveHandler extends FieldPluginBase {

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

    $options['show_as_link'] = ['default' => '0'];
    $options['link_text'] = ['default' => $this->t('Link')];

    return $options;
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    $form['show_as_link'] = array(
      '#title' => $this->t('Show as an HTML link'),
      '#type' => 'checkbox',
      '#default_value' => isset($this->options['show_as_link']) ? $this->options['show_as_link'] : 0,
    );
    
    $form['link_text'] = array(
      '#title' => $this->t('Text used for the link'),
      '#type' => 'textfield',
      '#default_value' => isset($this->options['link_text']) ? $this->options['link_text'] : $this->t('Link'),
      '#states' => array(
        'visible' => array(
          ':input[name="options[show_as_link]"]' => array('checked' => TRUE),
        ),
      ),
    );
    
    
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    global $base_url;

    $item = $values->_entity;
    //this will ONLY work for production assignments and/or maestro queues
    if ($item->getEntityTypeId() == 'maestro_production_assignments' || $item->getEntityTypeId() == 'maestro_queue') {
      //we are of the right types.  So let's get the right queue ID
      if ($item->getEntityTypeId() == 'maestro_production_assignments') {
        $queueID = $item->queue_id->getString();
      }
      else {
        $queueID = $item->id->getString();
      }
      
      $taskhandler = TaskHandler::getHandlerURL($queueID);
      
      if(isset($this->options['show_as_link']) && $this->options['show_as_link'] == 1) {
        $build['handler'][$queueID]['execute'] = array(
          '#type' => 'link',
          '#title' => isset($this->options['link_text']) ? $this->options['link_text'] : $this->t('Link'),
          '#url' => Url::fromUri($taskhandler),
        );
      }
      else {
        $build['handler'][$queueID]['execute'] = array(
          '#plain_text' => $taskhandler,
        );
      }
      
      return $build;
    }
    else {
      return '';
    }
  }
}