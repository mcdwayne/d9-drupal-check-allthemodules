<?php

namespace Drupal\third_party_wrappers\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\third_party_wrappers\ThirdPartyWrappersInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Third Party Wrappers settings.
 */
class ThirdPartyWrappersAdminForm extends ConfigFormBase {

  /**
   * The Third Party Wrappers service.
   *
   * @var \Drupal\third_party_wrappers\ThirdPartyWrappersInterface
   */
  protected $thirdPartyWrappers;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('third_party_wrappers'),
      $container->get('messenger')
    );
  }

  /**
   * Builds the admin settings form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\third_party_wrappers\ThirdPartyWrappersInterface $third_party_wrappers
   *   The Third Party Wrappers service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ThirdPartyWrappersInterface $third_party_wrappers, MessengerInterface $messenger) {
    parent::__construct($config_factory);
    $this->thirdPartyWrappers = $third_party_wrappers;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['third_party_wrappers.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'third_party_wrappers_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['expire_age'] = [
      '#type'          => 'textfield',
      '#size'          => 20,
      '#default_value' => $this->thirdPartyWrappers->getMaxAge(),
      '#title'         => $this->t('File expiration'),
      '#description'   => $this->t('Files which are saved will be deleted when their last access time + this value (in seconds) is less than the current time. 0 means do not delete files. There are 86400 seconds in a day.'),
    ];

    $form['split_on'] = [
      '#type'          => 'textfield',
      '#size'          => 100,
      '#default_value' => $this->thirdPartyWrappers->getSplitOn(),
      '#title'         => $this->t('Content marker'),
      '#description'   => $this->t('Enter the string that should be used to split the page into headers and footers here. It will be matched case sensitively.'),
      '#required'      => TRUE,
    ];

    $form['css_js_dir'] = [
      '#type'          => 'textfield',
      '#default_value' => $this->thirdPartyWrappers->getDir(),
      '#title'         => $this->t('CSS/JS storage directory'),
      '#description'   => $this->t('Third Party Wrappers copies aggregated files to this directory, so the normal Drupal caching mechanisms will not prematurely remove files still in use by the wrappers.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Ensure that the JS/CSS storage exists.
    $uri = $this->thirdPartyWrappers->getUri();
    $uri_js = $uri . '/js';
    $uri_css = $uri . '/css';
    $dir = file_prepare_directory($uri, FILE_CREATE_DIRECTORY);
    if (empty($dir)) {
      $this->messenger->addError($this->t('There was an error preparing the Third Party Wrappers directory.'));
    }
    $dir_js = file_prepare_directory($uri_js, FILE_CREATE_DIRECTORY);
    if (empty($dir_js)) {
      $this->messenger->addError($this->t('There was an error preparing the Third Party Wrappers JavaScript directory.'));
    }
    $dir_css = file_prepare_directory($uri_css, FILE_CREATE_DIRECTORY);
    if (empty($dir_css)) {
      $this->messenger->addError($this->t('There was an error preparing the Third Party Wrappers CSS directory.'));
    }

    // Store the form values in config.
    $keys = [
      'expire_age',
      'split_on',
      'css_js_dir',
    ];

    foreach ($keys as $key) {
      $this->config('third_party_wrappers.settings')
        ->set($key, $form_state->getValue($key))
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

}
