<?php

/**
 * @file
 */

namespace Drupal\smsc\Form;


use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\smsc\Smsc\DrupalSmscInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class SmscConfigForm.
 */
class SmscConfigForm extends ConfigFormBase {

  /**
   * @var \Drupal\smsc\Smsc\DrupalSmsc
   */
  protected $drupalSmsc;

  /**
   * @var null|\Smsc\Settings\Settings
   */
  protected $settings;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $smscConfig;

  /**
   * Has account settings.
   *
   * @var \Drupal\smsc\Smsc\DrupalSmsc
   */
  protected $hasSettings;

  /**
   * Constructs a new SmscConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\smsc\Smsc\DrupalSmscInterface      $drupalSmsc
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    DrupalSmscInterface $drupalSmsc
  ) {

    parent::__construct($config_factory);

    $this->drupalSmsc  = $drupalSmsc;
    $this->settings    = $this->drupalSmsc->getSettings();
    $this->smscConfig  = $this->drupalSmsc->getConfig();
    $this->hasSettings = $this->settings->valid();
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('smsc')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'smsc.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smsc_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->smscConfig;

    /* <-- Authorization --> */

    $form['authorization'] = [
      '#type'        => 'details',
      '#open'        => !$this->hasSettings,
      '#description' => t('Authorization data on SMSC service.'),
      '#title'       => $this->t('Authorization'),
      '#required'    => TRUE,
    ];

    $form['authorization']['login'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('SMSC Login'),
      '#description'   => $this->t('Your login on SMSC service.'),
      '#default_value' => $config->get('login'),
    ];

    $form['authorization']['psw'] = [
      '#type'        => 'password',
      '#title'       => $this->t('SMSC Password'),
      '#description' => $this->t('Your password on SMSC service.'),
      '#attributes'  => ['autocomplete' => 'off'],
    ];

    $form['authorization']['host'] = [
      '#type'          => 'select',
      '#options'       => $this->drupalSmsc->getHosts(),
      '#title'         => $this->t('API Host'),
      '#description'   => $this->t('SMSC API host.'),
      '#default_value' => $config->get('host'),
    ];

    /* <-- /Authorization --> */

    /* <-- Settings --> */

    $form['settings'] = [
      '#type'  => 'details',
      '#open'  => $this->hasSettings,
      '#title' => $this->t('Default settings'),
    ];

    $senders = $this->drupalSmsc->getSenders();

    $default_sender = '';

    if ($this->hasSettings && $senders && count($senders)) {
      $default_sender = $config->get('sender');
    }

    $default_sender = in_array($default_sender, $senders) ? $default_sender : '';

    $form['settings']['sender'] = [
      '#type'          => 'select',
      '#options'       => $senders,
      '#title'         => $this->t('Sender ID'),
      '#description'   => $this->t('Default sender ID.'),
      '#default_value' => $default_sender,
    ];

    $form['settings']['translit'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Translit'),
      '#description'   => $this->t('Transliterate messages by default.'),
      '#default_value' => $config->get('translit'),
    ];

    /* <-- /Settings --> */

    return parent::buildForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * Compare data.
   *
   * @param $unsaved
   * @param $new
   *
   * @return bool
   */
  protected function compareConfigData($unsaved, $new) {
    return (
      $unsaved['login'] != $new['login'] ||
      (!empty($new['psw']) && $unsaved['psw'] != $new['psw'])
    );
  }

  /**
   * Invalidate SMSC caches.
   *
   * @param $cid
   */
  protected function invalidateCachedData($cid) {
    \Drupal::cache()->invalidate($cid);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $form_state->cleanValues();

    $unsavedData = $this->config('smsc.config')->getOriginal();
    $savedData   = $form_state->getValues();

    if ($this->compareConfigData($unsavedData, $savedData)) {
      $this->invalidateCachedData('smsc:senders');
    }

    $config = $this->config('smsc.config');

    $config->set('login', $form_state->getValue('login'));

    if (!empty($form_state->getValue('psw'))) {
      $config->set('psw', trim($form_state->getValue('psw')));
    }

    if (!empty($form_state->getValue('host'))) {
      $config->set('host', trim($form_state->getValue('host')));
    }

    if ($form_state->getValue('sender') !== NULL) {
      $config->set('sender', trim($form_state->getValue('sender')));
    }

    if ($form_state->getValue('translit') !== NULL) {
      $config->set('translit', trim($form_state->getValue('translit')));
    }

    $this->config('smsc.config')->save();
  }
}
