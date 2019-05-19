<?php

namespace Drupal\Toolshed\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Asset\LibraryDiscovery;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create configuration form for Tinkered JS events.
 */
class JsEventsConfigForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The Drupal service that manages resolves references to libraries.
   *
   * @var Drupal\Core\Asset\LibraryDiscovery
   */
  protected $libraryDiscovery;

  /**
   * Create a new instance of a configuration form for managing libraries.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LibraryDiscovery $library_discovery) {
    parent::__construct($config_factory);

    $this->libraryDiscovery = $library_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('library.discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'toolshed_js_events_config';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['toolshed.assets.config'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('toolshed.assets.config');
    $form['develop'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use development version of assets.'),
      '#default_value' => $config->get('develop'),
    ];

    // Configuration for debounce with JavaScript events (e.g. resize, scroll).
    $form['events'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('JavaScript Event Configuration'),
      '#tree' => TRUE,
    ];

    $events = $config->get('events');
    $form['events']['debounce'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Debounce settings'),
      '#tree' => TRUE,
      '#description' => $this->t(
        'Debounce is a method to throttle actions that maybe expensive by
        adding a delay before triggering them. Expensive events like
        reacting to window resizes can be deferred until the time after
        the last resize event exceeds the specified timeout delay.'
      ),

      'enabled' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Debouce enabled'),
        '#default_value' => $events['debounce']['enabled'],
      ],
      'delay' => [
        '#type' => 'number',
        '#title' => $this->t('Delay in milliseconds'),
        '#field_suffix' => 'ms',
        '#size' => 10,
        '#min' => 0,
        '#default_value' => $events['debounce']['delay'],
      ],
    ];

    $accordConfig = $config->get('accordions');
    $form['accordions'] = [
      '#type' => 'details',
      '#title' => $this->t('Accordions Behavior Settings'),
      '#tree' => TRUE,
      '#collapsible' => TRUE,
      '#description' => $this->t(
        'These options are for working with the <em>toolshed/behavior.accordions</em>
        library and allow your site to specify the selectors to use when activating
        accordions behaviors on HTML elements.'
      ),

      'itemSelector' => [
        '#type' => 'textfield',
        '#title' => $this->t('Selector for accordion items'),
        '#default_value' => $accordConfig['itemSelector'],
        '#description' => $this->t('The jQuery selector to use when searching for accordion items.'),
      ],
      'toggleSelector' => [
        '#type' => 'textfield',
        '#title' => $this->t('Selector for the toggle control'),
        '#default_value' => $accordConfig['toggleSelector'],
        '#description' => $this->t('The jQuery selector to identify the control when clicked which expands and collapses the accordion.'),
      ],
      'bodySelector' => [
        '#type' => 'textfield',
        '#title' => $this->t('Selector for collapsible portion of the item'),
        '#default_value' => $accordConfig['bodySelector'],
        '#description' => $this->t('The portion of the accordion item that collapses.'),
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',

      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
      ],
      'cancel' => [
        '#type' => 'link',
        '#url' => NULL,
        '#attributes' => ['class' => ['button', 'button--cancel']],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $editableConfig = $this->configFactory->getEditable('toolshed.assets.config');
    $editableConfig->set('develop', (bool) $form_state->getValue('develop'));

    $eventVal = $form_state->getValue('events');
    $editableConfig->set('events', [
      'debounce' => [
        'enabled' => (bool) $eventVal['debounce']['enabled'],
        'delay' => intval($eventVal['debounce']['delay']),
      ],
    ]);

    $accordVal = $form_state->getValue('accordions');
    $editableConfig->set('accordions', [
      'itemSelector' => $accordVal['itemSelector'],
      'toggleSelector' => $accordVal['toggleSelector'],
      'bodySelector' => $accordVal['bodySelector'],
    ]);

    // Save the changes to the configuration.
    $editableConfig->save();

    // Clear cache so that the updated settings are applied.
    $this->libraryDiscovery->clearCachedDefinitions();
  }

}
