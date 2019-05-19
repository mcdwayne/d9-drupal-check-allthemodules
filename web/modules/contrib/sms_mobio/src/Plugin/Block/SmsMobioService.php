<?php

/**
 * @file
 * Contains \Drupal\block_example\Plugin\Block\ExampleConfigurableTextBlock.
 */

namespace Drupal\sms_mobio\Plugin\Block;

use Drupal\block\Annotation\Block;
use Drupal\block\BlockBase;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Example: configurable text string' block.
 *
 * Drupal\block\BlockBase gives us a very useful set of basic functionality for
 * this configurable block. We can just fill in a few of the blanks with
 * defaultConfiguration(), blockForm(), blockSubmit(), and build().
 *
 * @Block(
 *   id = "sms_mobio_servise_1_block",
 *   subject = @Translation("SMS Mobio service_1"),
 *   admin_label = @Translation("SMS Mobio service_1")
 * )
 */
class SmsMobioService extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'sms_mobio_service1_servid_string' => 123456,
      'sms_mobio_service1_linktopage_link' => 'node/1001',
      'sms_mobio_service1_linktopage_text' => t('See more'),
      'sms_mobio_service1_usage' => t('To use the SMS service send SMS with text xxxxx to number 1851 (1.20BGN.)'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['sms_mobio_service1_usage'] = array(
      '#type' => 'textarea',
      '#title' => t('Usage'),
      '#size' => 2,
      '#description' => t('Description (short) for clients how to use your service.'),
      '#default_value' => $this->configuration['sms_mobio_service1_usage'],
    );

    $form['sms_mobio_service1_servid'] = array(
      '#type' => 'textfield',
      '#title' => t('servID'),
      '#size' => 6,
      '#description' => t('Service ID (servID) for this sms service from your mobio.bg account. You need to create service on your mobio.bg accout to obtain servId.'),
      '#default_value' => $this->configuration['sms_mobio_service1_servid_string'],
    );

    $form['sms_mobio_service1_linktopage_link'] = array(
      '#type' => 'textfield',
      '#title' => t('Help page url'),
      '#description' => t('Link to page with description for users about this service. Set the same link on service page in your mobio.bg account.'),
      '#default_value' => $this->configuration['sms_mobio_service1_linktopage_link'],
    );
    $form['sms_mobio_service1_linktopage_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Link text'),
      '#default_value' => $this->configuration['sms_mobio_service1_linktopage_text'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['sms_mobio_service1_servid_string']
      = $form_state['values']['sms_mobio_service1_servid'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    $form = \Drupal::formBuilder()->getForm('Drupal\sms_mobio\Form\SmsMobioCheckCode', $config);

    return $form;
    /*
    return array(
      '#type' => 'markup',
      '#markup' => 'TEST TEST TEST !!!',
    );*/
  }

}
