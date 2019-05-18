<?php

/**
 * @file
 * Contains \Drupal\area_print\Plugin\Block\PrintArea.
 */

namespace Drupal\area_print\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides configurable button which activates the area print module.
 *
 * @Block(
 *   id = "print_area",
 *   admin_label = @Translation("Print area"),
 *   category = @Translation("System")
 * )
 */
class PrintArea extends BlockBase implements ContainerFactoryPluginInterface {

  
  /**
   * {@inheritdoc}
   * 
   * @param \Drupal\area_print\Plugin\Block\ContainerInterface $container
   * @param array $configuration
   * @param type $plugin_id
   * @param type $plugin_definition
   * @return \static
   */
  static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static (
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }
  
  /**
   * {@inheritdoc}
   * 
   * - target_id: Id of the element you want to print (optional, defaults to 'content'),
   * - value: The text for the link/button (optional, defaults to t('print')),
   * - type:  either 'link' or 'button' (optional, defaults to 'button'),
   * - custom_css: path to a css file that will get added to the page before printing (optional).
   */
  public function defaultConfiguration() {
    return [
      'css_id' => 'main',
      'action_label' => $this->t('Print'),
      'type' => 'link'
      //'custom_css' => ''//not yet supported
    ];
  }
  
  
  /**
   * Overrides \Drupal\block\BlockBase::buildConfigurationForm().
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    unset($form['label_display'], $form['label']);
    $form['action_label'] = [
      '#title' => t('Label'),
      '#description' => t('The text of the link / button.') . ' Not sure how this is translated',
      '#type' => 'textfield',
      '#default_value' => $this->configuration['action_label'],
      '#weight' => 0
    ];
    $form['type'] = [
      '#title' => t('Type'),
      '#type' => 'radios',
      '#options' => [
        'link' => t('Link'),
        'button' => t('Button')
      ],
      '#default_value' => $this->configuration['type'],
      '#weight' => 1
    ];
    $form['css_id'] = [
      '#title' => t('Target css id'),
      '#description' => t('The #id of the css element to be printed'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['css_id'],
      '#weight' => 2
    ];
    return $form;
  }
  
  
  /**
   * {@inheritDoc}
   * @todo check if the parent does this
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    //parent::blockSubmit($form, $form_state); //does nothing
    $values = $form_state->getValues();
    foreach ($values as $key => $val) {
      $this->configuration[$key] = $val;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return area_print($this->configuration);
  }
}
