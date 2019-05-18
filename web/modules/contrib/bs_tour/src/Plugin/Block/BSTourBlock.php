<?php

namespace Drupal\bs_tour\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Bootstrap Tour' block.
 *
 * Drupal\Core\Block\BlockBase gives us a very useful set of basic functionality
 * for this configurable block. We can just fill in a few of the blanks with
 * defaultConfiguration(), blockForm(), blockSubmit(), and build().
 *
 * @Block(
 *   id = "bs_tour_block",
 *   admin_label = @Translation("Bootstrap Tour"),
 *   category = @Translation("User Interface")
 * )
 */
class BSTourBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['bs_tour_block_settings'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Bootstrap Tour block settings'),
      '#collapsible' => FALSE,
    );
    $form['bs_tour_block_settings']['bs_tour_block_settings_text'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Bootstrap Tour "Start the Tour" text'),
      '#default_value' => isset($config['bs_tour_block_settings_text']) ? $config['bs_tour_block_settings_text'] : $this->t('Start the Tour'),
      '#maxlength' => 50,
      '#description' => $this->t('Enter the button/text value for starting the tour action.'),
      '#required' => TRUE,
    );
    $form['bs_tour_block_settings']['bs_tour_block_settings_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => array(
        'button' => $this->t('Button'),
        'text' => $this->t('Text'),
      ),
      '#default_value' => isset($config['bs_tour_block_settings_type']) ? $config['bs_tour_block_settings_type'] : 'button',
      '#description' => $this->t('Select the type of the tour action.'),
      '#required' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('bs_tour_block_settings_text', $form_state->getValue(array('bs_tour_block_settings', 'bs_tour_block_settings_text')));
    $this->setConfigurationValue('bs_tour_block_settings_type', $form_state->getValue(array('bs_tour_block_settings', 'bs_tour_block_settings_type')));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    $bs_tour_block_settings_text = $config['bs_tour_block_settings_text'];
    $bs_tour_block_settings_type = $config['bs_tour_block_settings_type'];

    $theme_vars = array(
      'text' => $bs_tour_block_settings_text,
      'type' => $bs_tour_block_settings_type,
    );

    return array(
      '#cache' => array(
        'contexts' => array('url'),
      ),
      '#theme' => 'bs_tour_block',
      '#block_settings' => $theme_vars,
      '#attached' => array(
        'library' => array(
          'bs_tour/bootstrap.tour',
        ),
      ),
    );
  }

}
