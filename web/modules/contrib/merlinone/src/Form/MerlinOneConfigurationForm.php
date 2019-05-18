<?php

namespace Drupal\merlinone\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\merlinone\MerlinOneApiInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * MerlinOne module configuration form.
 */
class MerlinOneConfigurationForm extends ConfigFormBase {

  /**
   * The MerlinOne API Service.
   *
   * @var \Drupal\merlinone\MerlinOneApiInterface
   */
  private $merlinOneApi;

  /**
   * Constructs a MerlinOneConfigurationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\merlinone\MerlinOneApiInterface $merlinOneApi
   *   The MerlinOne API service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MerlinOneApiInterface $merlinOneApi) {
    parent::__construct($config_factory);
    $this->merlinOneApi = $merlinOneApi;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('merlinone.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'merlinone_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'merlinone.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('merlinone.settings');

    $form['archive_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Archive URL'),
      '#description' => $this->t('For example: <em>https://your-domain.merlinone.com</em>.'),
      '#default_value' => $config->get('archive_url'),
      '#required' => TRUE,
    ];

    $form['max_image_dimension'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum Image Dimension'),
      '#default_value' => $config->get('max_image_dimension'),
      '#field_suffix' => ' ' . $this->t('pixels'),
      '#min' => 1,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $archive_url = $form_state->getValue('archive_url');
    if (!UrlHelper::isValid($archive_url, TRUE)) {
      $form_state->setErrorByName('archive_url', $this->t('Archive URL is not valid'));
    }
    elseif (!$form_state->isValueEmpty('max_image_dimension')) {
      $this->merlinOneApi->setArchiveUrl($archive_url);
      if (!$this->merlinOneApi->sodaAllowsResampling()) {
        $form_state->setErrorByName('max_image_dimension', $this->t('Maximum Image Dimension not available, Merlin SODA version is @variable and needs to be updated. Please contact Merlin support.', [
          '@variable' => $this->merlinOneApi->getSodaVersion(),
        ]));
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('merlinone.settings')
      ->set('archive_url', $form_state->getValue('archive_url'))
      ->set('max_image_dimension', $form_state->getValue('max_image_dimension'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
