<?php

/**
 * @file
 * Definition of Drupal\maestro\Plugin\views\field\MaestroEngineProcessInitiator
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
 * Field handler to translate the UID field into a username
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("maestro_process_initiator_user")
 */
class MaestroEngineProcessInitiator extends FieldPluginBase {

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
    return $options;
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    $form['show_as_link'] = array(
      '#title' => $this->t('Show as an HTML link to the user account.'),
      '#type' => 'checkbox',
      '#default_value' => isset($this->options['show_as_link']) ? $this->options['show_as_link'] : 0,
    );
    
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    global $base_url;

    $item = $values->_entity;
    //this will ONLY work for processes.
    if ($item->getEntityTypeId() == 'maestro_process') {
      $usr = user_load(intval($item->initiator_uid->getString()));
      if($usr) {
        if(isset($this->options['show_as_link']) && $this->options['show_as_link'] == 1) {
          $build['initiator_username'] = array(
            '#type' => 'link',
            '#title' => $usr->getAccountName(),
            '#url' => Url::fromRoute('entity.user.canonical', ['user' => $usr->id()]),
          );
        }
        else {
          $build['initiator_username'] = array(
            '#plain_text' => $usr->getAccountName(),
          );
        }
      }
      
      
      return $build;
    }
    else {
      return '';
    }
  }
}