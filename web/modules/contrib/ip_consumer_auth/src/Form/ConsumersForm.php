<?php

/**
 * @file
 * Contains Drupal\ip_consumer_auth\Form\ConsumersForm.
 */

namespace Drupal\ip_consumer_auth\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form builder for the IP consumer authentication settings page.
 */
class ConsumersForm extends ConfigFormBase {

  /**
   * The available serializer formats.
   *
   * @var array
   */
  protected $formats;

  /**
   * Constructs a new ConsumersForm.
   *
   * @param array $formats
   *   The available serializer formats.
   */
  public function __construct(array $formats) {
    $this->formats = array_combine($formats, $formats);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->getParameter('serializer.formats')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ip_consumer_auth_settings_form';
  }
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ip_consumer_auth.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ip_consumer_auth.settings');

    $form['ip_consumers'] = [
      '#type' => 'textarea',
      '#title' => $this->t('IPs to whitelist / blacklist'),
      '#description' => $this->t('Specify the IP addresses to whitelist / blacklist. Place each IP address on a separate line.'),
      '#default_value' => $config->get('ip_consumers'),
    ];

    $options = array(0 => t('Blacklist'), 1 => t('Whitelist'));
    $form['list_type'] = array(
      '#type' => 'radios',
      '#title' => t('Type of IP list'),
      '#default_value' => $config->get('list_type'),
      '#options' => $options,
      '#description' => t('Define the behaviour to use when applying the IP list as authentication method for REST resources. A whitelist will only allow access to the specified IPs. A blacklist will allow access to the all IPs, except the specified ones.'),
      '#required' => TRUE,
    );

    $form['format'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Format'),
      '#description' => $this->t("Select the acceptable formats to apply authentication to."),
      '#default_value' => $config->get('format'),
      '#options' => $this->formats,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('ip_consumer_auth.settings')
      ->set('ip_consumers', $form_state->getValue('ip_consumers'))
      ->set('list_type', $form_state->getValue('list_type'))
      ->set('format', array_filter($form_state->getValue('format')))
      ->save();
  }

}
