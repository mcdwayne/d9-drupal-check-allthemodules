<?php

namespace Drupal\imgix\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure file system settings for this site.
 */
class ImgixSettingsForm extends ConfigFormBase {

  /**
   * EntitTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Construct.
   *
   * {@inheritDoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Create.
   *
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Formid.
   *
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'imgix_settings_form';
  }

  /**
   * Config names.
   *
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['imgix.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('imgix.settings');

    $form['enable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Imgix Integration'),
      '#description' => $this->t('Enabling this option make it the default image processing and CDN.'),
      '#default_value' => $config->get('enable'),
    );

    $form['settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Settings'),
      '#open' => TRUE,
      '#states' => array(
        'visible' => array(
          ':input[name="enable"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['settings']['source'] = array(
      '#type' => 'details',
      '#title' => $this->t('Source'),
      '#collapsible' => FALSE,
      '#open' => TRUE,
    );

    $form['settings']['source']['source_domain'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Domain'),
      '#default_value' => $config->get('source_domain'),
    );

    $form['settings']['mapping'] = array(
      '#type' => 'details',
      '#title' => $this->t('Mapping'),
      '#collapsible' => FALSE,
      '#open' => TRUE,
    );

    $form['settings']['mapping']['mapping_type'] = array(
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => $this->t('Type'),
      '#options' => array(
        'webfolder' => 'Web Folder',
        'webproxy' => 'Web Proxy',
        's3' => 'Amazon S3',
      ),
      '#default_value' => $config->get('mapping_type'),
    );

    $form['settings']['mapping']['mapping_url'] = array(
      '#type' => 'textfield',
      '#description' => $this->t('Leave blank to get the current base URL.'),
      '#title' => $this->t('Base URL'),
      '#default_value' => $config->get('mapping_url'),
    );

    $form['settings']['security'] = array(
      '#type' => 'details',
      '#title' => $this->t('Security'),
      '#collapsible' => FALSE,
      '#open' => TRUE,
    );

    $form['settings']['security']['secure_url_token'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Secure URL Token'),
      '#default_value' => $config->get('secure_url_token'),
    );

    $form['settings']['security']['https'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('HTTPS support'),
      '#default_value' => $config->get('https'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('imgix.settings');

    $form_state->cleanValues();

    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
