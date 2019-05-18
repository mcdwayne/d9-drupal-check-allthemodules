<?php

namespace Drupal\gridstack_ui\Form;

use Drupal\Core\Url;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the GridStack admin settings form.
 */
class GridStackSettingsForm extends ConfigFormBase {

  /**
   * Drupal\Core\Asset\LibraryDiscoveryInterface definition.
   *
   * @var Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LibraryDiscoveryInterface $library_discovery, Messenger $messenger) {
    parent::__construct($config_factory);
    $this->libraryDiscovery = $library_discovery;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('library.discovery'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gridstack_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['gridstack.settings'];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('gridstack.settings');

    $form['debug'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Debug'),
      '#description'   => $this->t('Only enable for debugging purposes. Disable at production. Currently only showing INDEX numbering for each box to know/ adjust stamp placements. Or grey outline.'),
      '#default_value' => $config->get('debug'),
    ];

    $form['dev'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Use non-minified GridStack library'),
      '#description'   => $this->t('Only enable for debugging purposes at <strong>admin pages</strong>. This will replace <code>gridstack.all.js</code> with <code>gridstack.js</code> and <code>gridstack.jQueryUI.js</code>.'),
      '#default_value' => $config->get('dev'),
    ];

    $form['customized'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Use customized GridStack library'),
      '#description'   => $this->t('This is deprecated as per v0.3.0 which already decouples jQuery UI for its static grid. This is still useful to reduce JS size from 34KB to 20KB. <br><strong>Old warning!</strong> This _was a proof of concept that GridStack can work without jQuery UI for the static grid at frontend. Be sure to disable this when jQuery UI related issues are resolved. This customized library is meant temporary, and may not always stay updated! <br><strong>Until then, use at your own risk.</strong>'),
      '#default_value' => $config->get('customized'),
    ];

    $form['framework'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Grid framework'),
      '#options'       => [
        'bootstrap3' => 'Bootstrap 3',
        'bootstrap'  => 'Bootstrap 4',
        'foundation' => 'Foundation',
      ],
      '#empty_option' => '- None -',
      '#description'   => $this->t("By default GridStack supports dynamic magazine layouts -- js-driven. Choose a grid framework to also support static grids -- css-driven.<br>This will be used as a replacement for GridStack JS whenever provided/ overriden <strong>per optionset</strong>. This means no GridStack JS/ CSS assets are loaded for the active optionset. Your Bootstrap/ Foundation grid framework will take over. GridStack acts more like a layout builder for those static grids. Yet still usable as original dynamic magazine layouts as well, <strong>per optionset</strong>. <br>GridStack doesn't load the Bootstrap/Foundation library for you. Have a theme, or module, which does it."),
      '#default_value' => $config->get('framework'),
    ];

    $form['library'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Grid library'),
      '#description'   => $this->t('Specify CSS grid library to load at admin pages such as for core layout builder pages, e.g.: <code>bootstrap_library/bootstrap, my_theme/bootstrap</code>, etc.'),
      '#default_value' => $config->get('library'),
    ];

    $form['optimized'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Optimize CSS grid classes'),
      '#description'   => $this->t('<b>Experimental!</b> Check to optimize CSS classes by removing duplicate grid rules, mobile first. E.g.:<br><code>col col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12</code> becomes <code>col col-12</code> <br><code>col col-12 col-sm-6 col-md-6 col-lg-4 col-xl-4</code> becomes <code>col col-12 col-sm-6 col-lg-4</code> <br>Uncheck if any issue.'),
      '#default_value' => $config->get('optimized'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('gridstack.settings');

    foreach (['debug', 'dev', 'customized', 'framework', 'library', 'optimized'] as $key) {
      $config->set($key, $form_state->getValue($key));
    }

    $config->save();

    // Invalidate the library discovery cache to update new assets.
    $this->libraryDiscovery->clearCachedDefinitions();
    $this->configFactory->clearStaticCache();

    // If anything fails, notice to clear the cache.
    $this->messenger->addMessage($this->t('Be sure to <a href=":clear_cache">clear the cache</a> <strong>ONLY IF</strong> trouble to see the updated libraries.', [':clear_cache' => Url::fromRoute('system.performance_settings')->toString()]));

    parent::submitForm($form, $form_state);
  }

}
