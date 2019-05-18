<?php

/**
 * @file
 * Definition of Drupal\maestro\Plugin\views\field\MaestroEngineUserWhoCompleted
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
 * Field handler to create a linkage to the user who completed a task
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("maestro_user_who_completed")
 */
class MaestroEngineUserWhoCompleted extends FieldPluginBase {

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
    $options['user_display_style'] = ['default' => 'name'];
    $options['link_to_user'] = ['default' => 0];
    return $options;
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $options = [
      'name' => $this->t('Username'),
      'uid' => $this->t('The User ID'),
      'email' => $this->t('Email Address'),
    ];
    
    $form['user_display_style'] = array(
      '#title' => $this->t('How to show the user who completed the task?'),
      '#type' => 'select',
      '#default_value' => isset($this->options['user_display_style']) ? $this->options['user_display_style'] : 'name',
      '#options' => $options,
    );
    
    $form['link_to_user'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Display as link to the user?'),
      '#description' => $this->t('When checked, the output in the view will show a link to the user\'s profile.'),
      '#default_value' => isset($this->options['link_to_user']) ? $this->options['link_to_user'] : 0 ,
    );
    
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $result = '';
    $item = $values->_entity;
    //this will ONLY work for maestro queues
    if ($item->getEntityTypeId() == 'maestro_queue') {
      $usr =\Drupal::entityTypeManager()->getStorage('user')->load($item->uid->getString());
      if($usr) {
        $link_to_user = $usr->toUrl('canonical')->toString();
        switch($this->options['user_display_style']) {
          case 'name':
            $result = $usr->name->getString();
            break;
            
          case 'uid':
            $result = $item->uid->getString();
            break;
            
          case 'email':
            $result = $usr->email->getString();
            break;
        }
      }
    }
    else {
      return '';
    }
    
    if($this->options['link_to_user'] && $result) {
      return ['#markup' => '<a href="' . $link_to_user . '" class="maestro_who_completed_field">' . $result . '</a>'];
    }
    else {
      return $result;
    }
  }
}