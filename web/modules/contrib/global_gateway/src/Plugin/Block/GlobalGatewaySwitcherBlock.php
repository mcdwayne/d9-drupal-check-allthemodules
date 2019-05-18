<?php

namespace Drupal\global_gateway\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\global_gateway\Helper;
use Drupal\global_gateway\Form\GlobalGatewaySwitcherForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a basic global_gateway block with region only.
 *
 * @Block(
 *   id = "global_gateway_switcher_block",
 *   admin_label = @Translation("Global Gateway"),
 * )
 */
class GlobalGatewaySwitcherBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Global GatewayHelper.
   *
   * @var \Drupal\global_gateway\Helper
   */
  protected $helper;

  protected $regionDisplayDefault;

  protected $languagesDisplayDefault;

  protected $flagIconsDefault;

  protected $untranslatedContentDefault;

  protected $limitDefault;

  protected $nativeNamesDefault;

  protected $regionWeightDefault;

  protected $languagesWeightDefault;

  protected $regionDisplayOptions;

  protected $languagesDisplayOptions;

  protected $flagIconsOptions;

  protected $untranslatedContentOptions;

  protected $limitOptions;

  protected $nativeNamesOptions;

  protected $regionWeightOptions;

  protected $languagesWeightOptions;

  /**
   * Constructs an RegionLanguagesBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account interface.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\global_gateway\Helper $global_gateway_helper
   *   GlobalGateway Helper.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Request $request,
    AccountInterface $account,
    FormBuilderInterface $form_builder,
    Helper $global_gateway_helper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->request     = $request;
    $this->account     = $account;
    $this->formBuilder = $form_builder;
    $this->helper      = $global_gateway_helper;

    $this->regionDisplayOptions = [
      0 => $this->t('None'),
      1 => $this->t('Select list'),
      2 => 'select2boxes',
    ];

    $this->languagesDisplayOptions = [
      0 => $this->t('None'),
      1 => $this->t('Language switcher link'),
    ];

    $this->untranslatedContentOptions = [
      'Do not display language switcher',
      'Display language switcher',
      'Link language switcher to frontpage.',
    ];

    $this->flagIconsOptions       = [0];
    $this->limitOptions           = [1];
    $this->nativeNamesOptions     = [0];
    $this->regionWeightOptions    = [0, 1];
    $this->languagesWeightOptions = [0, 2];

    $this->assignDefaultValue('regionDisplay', 'select');
    $this->assignDefaultValue('languagesDisplay', 'select');
    $this->assignDefaultValue('untranslatedContent', 'select');
    $this->assignDefaultValue('flagIcons', 'checkbox');
    $this->assignDefaultValue('limit', 'checkbox');
    $this->assignDefaultValue('nativeNames', 'checkbox');
    $this->assignDefaultValue('regionWeight', 'select');
    $this->assignDefaultValue('languagesWeight', 'select');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('current_user')->getAccount(),
      $container->get('form_builder'),
      $container->get('global_gateway.helper')
    );
  }

  /**
   * Checks the configuration and sets default options.
   *
   * @param string $config
   *   Name of the config.
   * @param string $element
   *   Type of the element.
   */
  public function assignDefaultValue(string $config, string $element) {
    if ($element === 'select') {
      $default = array_keys($this->{$config . 'Options'})[1];
    }
    else {
      $default = $this->{$config . 'Options'}[0];
    }
    if (!isset($this->configuration[$config]) || is_null($this->configuration[$config])) {
      $this->{$config . 'Default'} = (int) $default;
    }
    else {
      $this->{$config . 'Default'} = (int) $this->configuration[$config];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    if (!\Drupal::moduleHandler()->moduleExists('language')) {
      $table_type = 'table';
    } else {
      $table_type = 'field_ui_table';
    }
    $form['global-gateway-settings'] = [
      '#type'      => $table_type,
      '#header'    => [
        $this->t('Field'),
        $this->t('Weight'),
        $this->t('Display'),
        '',
        '',
      ],
      '#tabledrag' => [
        [
          'action'       => 'order',
          'relationship' => 'sibling',
          'group'        => 'global-gateway-settings-weight',
        ],
      ],
    ];

    $form['global-gateway-settings']['region-display']['parent_wrapper']['parent'] = [
      '#type' => 'hidden',
    ];
    $form['global-gateway-settings']['region-display']['#region_callback'] = [$this, 'returnNull'];

    $form['global-gateway-settings']['languages-display']['parent_wrapper']['parent'] = [
      '#type' => 'hidden',
    ];
    $form['global-gateway-settings']['languages-display']['#region_callback'] = [$this, 'returnNull'];

    $form['global-gateway-settings']['region-display']['field'] = [
      '#plain_text' => 'Region',
    ];
    $form['global-gateway-settings']['region-display']['#attributes']['class'][] = 'draggable';

    $form['global-gateway-settings']['region-display']['#weight'] = $this->regionWeightDefault;
    $form['global-gateway-settings']['region-display']['weight'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Weight for region display'),
      '#title_display' => 'invisible',
      '#default_value' => $this->regionWeightDefault,
      '#attributes'    => ['class' => ['global-gateway-settings-weight']],
    ];

    if (!\Drupal::moduleHandler()->moduleExists('select2boxes')) {
      unset($this->regionDisplayOptions[2]);
      if (isset($this->configuration['regionDisplay']) && $this->configuration['regionDisplay'] == 2) {
        $this->regionDisplayDefault = 1;
      }
    }

    $form['global-gateway-settings']['region-display']['options'] = [
      '#type'          => 'select',
      '#options'       => $this->regionDisplayOptions,
      '#default_value' => $this->regionDisplayDefault,
    ];
    if (\Drupal::moduleHandler()->moduleExists('flags')) {
      $form['global-gateway-settings']['region-display']['flag-icons'] = [
        '#type'          => 'checkbox',
        '#title'         => 'Flag icons',
        '#default_value' => $this->flagIconsDefault,
        '#states'        => [
          'visible'   => [
            ':input[name="settings[global-gateway-settings][region-display][options]"]' => ['value' => '2'],
          ],
          'invisible' => [
            ':input[name="settings[global-gateway-settings][region-display][options]"]' => ['!value' => '2'],
          ],
        ],
      ];
    }

    $form['global-gateway-settings']['languages-display']['field'] = [
      '#plain_text' => 'Languages',
    ];
    $form['global-gateway-settings']['languages-display']['#attributes']['class'][] = 'draggable';

    $form['global-gateway-settings']['languages-display']['#weight'] = $this->languagesWeightDefault;
    $form['global-gateway-settings']['languages-display']['weight'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Weight for languages display'),
      '#title_display' => 'invisible',
      '#default_value' => $this->languagesWeightDefault,
      '#attributes'    => ['class' => ['global-gateway-settings-weight']],
    ];
    $form['global-gateway-settings']['languages-display']['options'] = [
      '#type'          => 'select',
      '#options'       => $this->languagesDisplayOptions,
      '#default_value' => $this->languagesDisplayDefault,
    ];

    $form['global-gateway-settings']['languages-display']['additional-settings'] = [
      '#type'   => 'container',
      '#states' => [
        'visible'   => [
          ':input[name="settings[global-gateway-settings][languages-display][options]"]' => ['value' => 1],
        ],
        'invisible' => [
          ':input[name="settings[global-gateway-settings][languages-display][options]"]' => ['!value' => 1],
        ],
      ],
    ];

    $form['global-gateway-settings']['languages-display']['additional-settings']['untranslated-content-label'] = [
      '#plain_text' => 'Behavior for untranslated content:',
    ];
    $form['global-gateway-settings']['languages-display']['additional-settings']['untranslated-content'] = [
      '#type'          => 'select',
      '#options'       => $this->untranslatedContentOptions,
      '#default_value' => $this->untranslatedContentDefault,
    ];
    $form['global-gateway-settings']['languages-display']['additional-settings']['limit'] = [
      '#type'          => 'checkbox',
      '#title'         => 'Limit languages to region',
      '#default_value' => $this->limitDefault,
      '#attributes'    => ['class' => ['additional-settings-checkbox']],
      '#attached'      => [
        'library' => [
          'global_gateway/global_gateway',
        ],
      ],
    ];
    $form['global-gateway-settings']['languages-display']['additional-settings']['native-names'] = [
      '#type'          => 'checkbox',
      '#title'         => 'Use native names',
      '#default_value' => $this->nativeNamesDefault,
      '#attributes'    => ['class' => ['additional-settings-checkbox']],
    ];

    if (!\Drupal::moduleHandler()->moduleExists('global_gateway_language')) {
      $form['global-gateway-settings']['languages-display']['additional-settings']['limit'] = [
        '#type'   => 'hidden',
        '#value' => 0,
      ];
    }

    if (!\Drupal::moduleHandler()->moduleExists('language')) {
      unset($form['global-gateway-settings']['languages-display']);
      unset($form['global-gateway-settings']['region-display']['weight']);
      $form['global-gateway-settings']['#header'] = [
        $this->t('Field'),
        $this->t('Display'),
        '',
      ];
    }

    return $form;
  }

  /**
   * Returns the region to which a row in the display overview belongs.
   *
   * @return null
   *   Always returns null.
   */
  public function returnNull() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['regionDisplay']       = $form_state->getValue([
      'global-gateway-settings',
      'region-display',
      'options',
    ]);
    $this->configuration['flagIcons']           = $form_state->getValue([
      'global-gateway-settings',
      'region-display',
      'flag-icons',
    ]);
    $this->configuration['regionWeight']        = $form_state->getValue([
      'global-gateway-settings',
      'region-display',
      'weight',
    ]);
    $this->configuration['languagesDisplay']    = $form_state->getValue([
      'global-gateway-settings',
      'languages-display',
      'options',
    ]);
    $this->configuration['untranslatedContent'] = $form_state->getValue([
      'global-gateway-settings',
      'languages-display',
      'additional-settings',
      'untranslated-content',
    ]);
    $this->configuration['limit']               = $form_state->getValue([
      'global-gateway-settings',
      'languages-display',
      'additional-settings',
      'limit',
    ]);
    $this->configuration['nativeNames']         = $form_state->getValue([
      'global-gateway-settings',
      'languages-display',
      'additional-settings',
      'native-names',
    ]);
    $this->configuration['languagesWeight']     = $form_state->getValue([
      'global-gateway-settings',
      'languages-display',
      'weight',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($this->request->attributes->has('exception')) {
      return [];
    }

    $region_code = \Drupal::service('global_gateway_region_negotiator')
      ->negotiateRegion();
    $items_id    = $this->request->request->get('items_id');

    if (empty($items_id)) {
      $items_id = Html::getUniqueId('switcher-items');
    }

    $form_state = new FormState();
    $form_state->set('current_region', $region_code);
    $form_state->set('items_id', $items_id);
    $form_state->set('region_display', $this->regionDisplayDefault);
    $form_state->set('languages_display', $this->languagesDisplayDefault);
    $form_state->set('flag_icons', $this->flagIconsDefault);
    $form_state->set('nativeNames', $this->nativeNamesDefault);
    $form_state->set('untranslatedContent', $this->untranslatedContentDefault);
    $form_state->set('regionWeight', $this->regionWeightDefault);
    $form_state->set('languagesWeight', $this->languagesWeightDefault);
    if (!\Drupal::moduleHandler()->moduleExists('global_gateway_language')) {
      $form_state->set('limit', 0);
    }
    else {
      $form_state->set('limit', $this->limitDefault);
    }

    return [
      'form'   => $this->formBuilder->buildForm(
        GlobalGatewaySwitcherForm::class,
        $form_state
      ),
      '#cache' => ['max-age' => 0],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
