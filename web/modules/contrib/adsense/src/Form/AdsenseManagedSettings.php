<?php

namespace Drupal\adsense\Form;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AdsenseManagedSettings.
 */
class AdsenseManagedSettings extends ConfigFormBase {

  /**
   * Request path condition plugin.
   *
   * @var \Drupal\system\Plugin\Condition\RequestPath
   */
  protected $condition;

  /**
   * Constructs a \Drupal\adsense\Form\AdsenseManagedSettings object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Component\Plugin\Factory\FactoryInterface $plugin_factory
   *   The factory for condition plugin objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FactoryInterface $plugin_factory) {
    parent::__construct($config_factory);
    try {
      $this->condition = $plugin_factory->createInstance('request_path');
    }
    catch (PluginException $e) {
      // System is badly broken if we can't get the condition plugin.
      $this->condition = NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'adsense_managed_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['adsense.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('adsense.settings');

    $form['adsense_managed_async'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use asynchronous ad code?'),
      '#default_value' => $config->get('adsense_managed_async'),
      '#description' => $this->t('This will enable the asynchronous ad code type. [@moreinfo]',
        ['@moreinfo' => Link::fromTextAndUrl($this->t('More information'), Url::fromUri('https://support.google.com/adsense/answer/3221666'))->toString()]),
    ];

    $form['adsense_managed_page_level_ads_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable auto ads?'),
      '#default_value' => $config->get('adsense_managed_page_level_ads_enabled'),
      '#description' => $this->t('This will enable Auto ads. [@moreinfo]',
        ['@moreinfo' => Link::fromTextAndUrl($this->t('More information'), Url::fromUri('https://support.google.com/adsense/answer/7478040'))->toString()]),
    ];

    // Auto ads visibility.
    $form['visibility'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Auto ads visibility'),
      '#states' => [
        'invisible' => [
          ":input[name='adsense_managed_page_level_ads_enabled']" => ['checked' => FALSE],
        ],
      ],
    ];

    if ($config->get('adsense_access_pages')) {
      $this->condition->setConfiguration($config->get('adsense_access_pages'));
    }

    $form['visibility'] += $this->condition->buildConfigurationForm($form['visibility'], $form_state);
    $form['visibility']['negate']['#type'] = 'radios';
    $form['visibility']['negate']['#default_value'] = (int) $form['visibility']['negate']['#default_value'];
    $form['visibility']['negate']['#title_display'] = 'invisible';
    $form['visibility']['negate']['#options'] = [
      $this->t('Show for the listed pages'),
      $this->t('Hide for the listed pages'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('adsense.settings');
    $form_state->cleanValues();

    $this->condition->submitConfigurationForm($form, $form_state);
    $config->set('adsense_access_pages', $this->condition->getConfiguration());

    // Don't store the condition values separately.
    $values = $form_state->getValues();
    unset($values['pages']);
    unset($values['negate']);

    foreach ($values as $key => $value) {
      $config->set($key, Html::escape($value));
    }
    $config->save();
  }

}
