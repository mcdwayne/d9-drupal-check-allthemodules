<?php

namespace Drupal\toolshed\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ThemeHandler;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\breakpoint\BreakpointManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create configuration form for Tinkered JS events.
 */
class ThemeBreakpointSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * Service to fetch and manage breakpoints for various themes.
   *
   * @var Drupal\breakpoint\BreakpointManager
   */
  protected $bpsManager;

  /**
   * Service handler for managing and loading themes.
   *
   * @var Drupal\Core\Extension\ThemeHandler
   */
  protected $themeHandler;

  /**
   * Create a new instance of a configuration form for managing JS breakpoints.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ThemeHandler $theme_handler, BreakpointManager $breakpoint_manager) {
    parent::__construct($config_factory);

    $this->themeHandler = $theme_handler;
    $this->bpsManager = $breakpoint_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('theme_handler'),
      $container->get('breakpoint.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tinkered_theme_breakpoint_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['toolshed.breakpoints.*'];
  }

  /**
   * Get the available JS Events which are triggered for this media query.
   *
   * The available set of events is limited so that JS event handlers can have
   * a consistent set of event names to watch for. These event names should
   * be reviewed to ensure that they make sense to other developers.
   *
   * @return array
   *   An array that can be used as the select form elements '#options' value.
   */
  public function getAvailableEvents() {
    return [
      'mobile' => $this->t('onMobile'),
      'screenMedium' => $this->t('onScreenMedium'),
      'screenLarge' => $this->t('onScreenLarge'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $theme = NULL) {
    if (!isset($theme) || !is_string($theme)) {
      $theme = $this->themeHandler->getDefault();
    }

    $form['theme'] = [
      '#type' => 'value',
      '#value' => $theme,
    ];

    // List the breakpoints settings for the themes.
    $form['breakpoints'] = [
      '#type' => 'table',
      '#empty' => $this->t('There are currently not active themes with known breakpoints'),
      '#header' => [
        $this->t('Breakpoint'),
        $this->t('Media query'),
        $this->t('Inverted'),
        $this->t('Event'),
        $this->t('Sort order'),
        $this->t('Actions'),
      ],
      '#attributes' => ['id' => 'breakpoints-manage-table'],
      '#tabledrag' => [
        'options' => [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'bp-sort-weight',
        ],
      ],
    ];

    // Fetch the correct breakpoints for the current theme.
    $bps = $this->bpsManager->getBreakpointsByGroup($theme);
    $configs = $this->configFactory->get("toolshed.breakpoints.{$theme}")->get('settings');

    $configBps = [];
    if (!empty($configs)) {
      foreach ($configs as $configuredBp) {
        $configBps[$configuredBp['name']] = $configuredBp;
      }
    }

    foreach (array_intersect_key($configBps, $bps) as $bp => $bpConfig) {
      $bpInfo = $bps[$bp];
      $form['breakpoints'][$bp] = [
        '#attributes' => [
          'id' => "bp-{$bp}",
          'class' => ['draggable'],
        ],

        'breakpoint' => ['#plain_text' => $bpInfo->getLabel()],
        'mediaQuery' => [
          '#type' => 'value',
          '#plain_text' => $bpInfo->getMediaQuery(),
        ],
        'inverted' => [
          '#type' => 'checkbox',
          '#default_value' => $bpConfig['inverted'],
        ],
        'event' => [
          '#type' => 'select',
          '#options' => $this->getAvailableEvents(),
          '#default_value' => $bpConfig['event'],
        ],
        'weight' => [
          '#type' => 'number',
          '#value' => $bpConfig['weight'],
          '#attributes' => ['class' => ['bp-sort-weight']],
        ],
        'actions' => [
          '#type' => 'submit',
          '#bp_key' => $bp,
          '#name' => 'delete_' . Html::cleanCssIdentifier($bp),
          '#value' => $this->t('Remove'),
          '#attributes' => [
            'class' => ['button', 'button--cancel'],
          ],
          '#submit' => [
            [$this, 'submitRemoveBreakpoint'],
            [$this, 'submitForm'],
          ],
        ],
      ];
    }

    $bpDiff = array_diff_key($bps, $configBps);
    if (!empty($bpDiff)) {
      foreach ($bpDiff as $bp => $bpInfo) {
        $bpOpts[$bp] = $bpInfo->getLabel() . ' -- ' . $bpInfo->getMediaQuery();
      }

      $form['breakpoints']['__add_breakpoint'] = [
        '#attributes' => [
          'id' => "add-new-breapoint",
          'class' => ['draggable'],
        ],

        'breakpoint' => [
          '#type' => 'select',
          '#field_prefix' => $this->t('<strong>Add:</strong>'),
          '#options' => $bpOpts,
          '#wrapper_attributes' => ['colspan' => 2],
        ],
        'inverted' => [
          '#type' => 'checkbox',
          '#default_value' => FALSE,
        ],
        'event' => [
          '#type' => 'select',
          '#options' => $this->getAvailableEvents(),
        ],
        'weight' => [
          '#type' => 'number',
          '#value' => 99,
          '#attributes' => ['class' => ['bp-sort-weight']],
        ],
        'actions' => [
          '#type' => 'submit',
          '#value' => $this->t('Add'),
          '#submit' => [
            [$this, 'submitAddBreakpoint'],
            [$this, 'submitForm'],
          ],
        ],
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',

      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $theme = $form_state->getValue('theme');
    $values = $form_state->getValue('breakpoints');

    // This table row is only for adding new breakpoints, and shouldn't be
    // removed before cleaning the breakpoints for this theme configuration.
    unset($values['__add_breakpoint']);

    $breakpoints = [];
    foreach ($values as $bp => $bpVal) {
      $breakpoints[] = [
        'name' => $bp,
        'event' => $bpVal['event'],
        'inverted' => $bpVal['inverted'],
        'weight' => $bpVal['weight'],
      ];
    }

    // Save the changes to the theme setup.
    $this->configFactory->getEditable("toolshed.breakpoints.{$theme}")
      ->set('settings', $breakpoints)
      ->save();
  }

  /**
   * Form submit callback to add a breakpoint into the settings.
   *
   * @param array $form
   *   Reference to the complete form structure.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The current state and submission values for the form.
   */
  public function submitAddBreakpoint(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('breakpoints');

    $addValues = $values['__add_breakpoint'];
    $values[$addValues['breakpoint']] = [
      'name' => $addValues['breakpoint'],
      'event' => $addValues['event'],
      'inverted' => $addValues['inverted'],
      'weight' => $addValues['weight'],
    ];

    $form_state->setValue('breakpoints', $values);
  }

  /**
   * Form submit callback to remove a breakpoint from the settings.
   *
   * @param array $form
   *   Reference to the complete form structure.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The current state and submission values for the form.
   */
  public function submitRemoveBreakpoint(array &$form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    $values = $form_state->getValue('breakpoints');

    if (!empty($element['#bp_key']) && isset($values[$element['#bp_key']])) {
      unset($values[$element['#bp_key']]);
      $form_state->setValue('breakpoints', $values);
    }
  }

}
