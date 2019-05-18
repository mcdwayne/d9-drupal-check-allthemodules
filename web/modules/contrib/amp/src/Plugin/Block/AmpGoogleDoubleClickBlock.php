<?php
/**
 * Provides an AMP Google DoubleClick for Publishers block
 *
 * @Block(
 *   id = "amp_google_doubleclick_block",
 *   admin_label = @Translation("AMP Google DoubleClick for Publishers block"),
 * )
 */

namespace Drupal\amp\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

class AmpGoogleDoubleClickBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {

    // Retrieve existing configuration for this block.
    $config = $this->getConfiguration();
    $doubleclick_id = $config['google_doubleclick_id'];
    $data_slot = $config['data_slot'];
    $height = $config['height'];
    $width = $config['width'];

    if (empty($doubleclick_id)) {
      return array(
        '#markup' => $this->t('This block requires a Google DoubleClick Network ID.')
      );
    }

    return [
      'inside' => [
        '#theme' => 'amp_ad',
        '#type' => 'doubleclick',
        '#attributes' => [
          'height' => $height,
          'width' => $width,
          'data-slot' => $doubleclick_id . '/' . $data_slot
        ]
      ]
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // Retrieve existing configuration for this block.
    $config = $this->getConfiguration();

    $form['google_doubleclick_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Google DoubleClick for Publishers Network ID'),
      '#default_value' => isset($config['google_doubleclick_id']) ? $config['google_doubleclick_id'] : '',
      '#maxlength' => 25,
      '#size' => 20,
      '#placeholder' => '/',
      '#description' => $this->t('The Network ID to use on this tag. This value should begin with a /.'),
    );

    $form['width'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#default_value' => isset($config['width']) ? $config['width'] : '',
      '#maxlength' => 25,
      '#size' => 20,
    );
    $form['height'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => isset($config['height']) ? $config['height'] : '',
      '#maxlength' => 25,
      '#size' => 20,
    );
    $form['data_slot'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Data-slot'),
      '#default_value' => isset($config['data_slot']) ? $config['data_slot'] : '',
      '#maxlength' => 25,
      '#size' => 20,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('google_doubleclick_id', $form_state->getValue('google_doubleclick_id'));
    $this->setConfigurationValue('width', $form_state->getValue('width'));
    $this->setConfigurationValue('height', $form_state->getValue('height'));
    $this->setConfigurationValue('data_slot', $form_state->getValue('data_slot'));
  }
}
