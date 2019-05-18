<?php

namespace Drupal\httpbl\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\httpbl\HttpblManager;

/**
 * Field handler to display human-ized definitions of the integer status values.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("status_enhanced")
 */
class StatusEnhanced extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    //unset($options['display_as_link']);
    $options['status_enhanced'] = array('default' => TRUE);
    return $options;
  }

  /**
   * Provide link to the page being visited.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    //unset ($form['display_as_link']);
    $form['status_enhanced'] = array(
      '#title' => $this->t('Status enhanced'),
      '#description' => $this->t('Shows the definitions of the integer status values, and when a blacklisted host is also banned.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['status_enhanced']),
    );
  }

  /**
   * {@inheritdoc}
   */
/*
  public function preRender(&$values) {
    $this->getValueOptions();
  }
*/

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $values->{$this->field_alias};
    if (!empty($this->options['status_enhanced']) || !isset($this->valueOptions[$value])) {
      // Status with humanized conversion.
      $httpblManager = \Drupal::service('httpbl.evaluator');
      $human = $httpblManager->getHumanStatus($value);
      $enhanced_value = t($value . ' - <em style="color: lightgrey;">' . $human . '</em>');
      //alternate, without any forced style.
      //$enhanced_value = t($value . ' - ' . $human);
      //$enhanced_value = t($value . ' - <span class="humanized">' . $human . '</span>');
     
      // If this entity is blacklisted && Ban module exists...
      if (($value == HTTPBL_LIST_BLACK) && (\Drupal::moduleHandler()->moduleExists('ban'))) {
        // Also check if it has been banned.
        $ip = $values->httpbl_host_host_ip;
        $banManager = \Drupal::service('ban.ip_manager');
  
        // If this host is also found in ban_ip table...
        if ($banManager->isBanned($ip)) {
          // Report as banned on the list, in addition to being blacklisted.
          $enhanced_value  = t($value . ' - <em style="color: lightgrey;">' . $human . ' and Banned!</em>');
          //alternate, without any forced style.
          //$enhanced_value  = t($value . ' - ' . $human . ' and Banned!');
          //$enhanced_value  = t($value . ' - <span class="humanized">' . $human . ' and Banned!</span>');
       }
      }
    
      // @todo Probably will have to play by the rules.  Not ready to sanatize this.
      //$result = $this->sanitizeValue($enhanced_value);
      $result = $enhanced_value;
    }
    else {
      $result = $this->valueOptions[$value];
    }

    return $result;
  }

}
