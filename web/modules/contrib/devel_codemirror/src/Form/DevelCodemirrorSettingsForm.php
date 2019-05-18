<?php

namespace Drupal\devel_codemirror\Form;

use Drupal\Core\Cache\CacheCollectorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DevelCodemirrorSettingsForm.
 */
class DevelCodemirrorSettingsForm extends ConfigFormBase {

  const THEMES = [
    '3024-day' => '3024 day',
    '3024-night' => '3024 night',
    'abcdef' => 'ABCDEF',
    'ambiance' => 'Ambiance',
    'ambiance-mobile' => 'Ambiance mobile',
    'base16-dark' => 'Base16 dark',
    'base16-light' => 'Base16 light',
    'bespin' => 'Bespin',
    'blackboard' => 'Blackboard',
    'cobalt' => 'Cobalt',
    'colorfort' => 'Colorforth',
    'dracula' => 'Dracula',
    'duotone-dark' => 'DuoTone-Dark',
    'duotone-light' => 'DuoTone-Light',
    'eclipse' => 'Eclipse',
    'elegant' => 'Elegant',
    'erlang-dark' => 'Erlang dark',
    'hopscotch' => 'Hopscotch',
    'icecoder' => 'ICEcoder',
    'isotope' => 'Isotope',
    'lesser-dark' => 'Less CSS dark',
    'liquibyte' => 'Liquibyte',
    'material' => 'Material',
    'mbo' => 'Mbo',
    'mdn-like' => 'MDN-LIKE Theme - Mozilla',
    'midnight' => 'Midnight',
    'monokai' => 'Monokai',
    'neat' => 'Neat',
    'neo' => 'Neo',
    'night' => 'Night',
    'panda-syntax' => 'Panda Syntax',
    'paraiso-dark' => 'Paraíso (Dark)',
    'paraiso-light' => 'Paraíso (Light)',
    'pastel-on-dark' => 'Pastel On Dark',
    'railscasts' => 'Railscasts',
    'rubyblue' => 'Rubyblue',
    'seti' => 'Seti',
    'solarized' => 'Solarized',
    'the-matrix' => 'The matrix',
    'tomorrow-night-bright' => 'Tomorrow Night - Bright',
    'tomorrow-night-eighties' => 'Tomorrow Night - Eighties',
    'ttcn' => 'TTCN',
    'twilight' => 'Twilight',
    'vibrant-ink' => 'Vibrant ink',
    'xq-dark' => 'XQ dark',
    'xq-light' => 'XQ light',
    'yeti' => 'Yeti',
    'zenburn' => 'Zenburn',
  ];

  /**
   * The cache collector service.
   *
   * @var \Drupal\Core\Cache\CacheCollectorInterface
   */
  protected $cacheCollector;

  /**
   * Constructs a DevelCodemirrorSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Cache\CacheCollectorInterface $cache_collector
   *   The cache collector service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheCollectorInterface $cache_collector) {
    parent::__construct($config_factory);

    $this->cacheCollector = $cache_collector;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('library.discovery.collector')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'devel_codemirror_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('devel_codemirror.settings');
    $form['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Theme'),
      '#description' => $this->t('The theme to style the editor with.'),
      '#options' => self::THEMES,
      '#empty_value' => 'default',
      '#default_value' => $config->get('theme'),
    ];
    $form['lineWrapping'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Line wrapping'),
      '#description' => $this->t('Whether CodeMirror should scroll or wrap for long lines.'),
      '#default_value' => $config->get('lineWrapping'),
    ];
    $form['lineNumbers'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Line number'),
      '#description' => $this->t('Whether to show line numbers to the left of the editor.'),
      '#default_value' => $config->get('lineNumbers'),
    ];
    $form['matchBrackets'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Match brackets'),
      '#description' => $this->t('When set to true or, causes matching brackets to be highlighted whenever the cursor is next to them.'),
      '#default_value' => $config->get('matchBrackets'),
    ];
    $form['autoCloseBrackets'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto close brackets'),
      '#description' => $this->t('Auto-close brackets and quotes when typed.'),
      '#default_value' => $config->get('autoCloseBrackets'),
    ];
    $form['styleActiveLine'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Style active line'),
      '#default_value' => $config->get('styleActiveLine'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $form_state->cleanValues();

    $this->config('devel_codemirror.settings')
      ->setData($form_state->getValues())
      ->save();

    $this->cacheCollector->clear();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'devel_codemirror.settings',
    ];
  }

}
