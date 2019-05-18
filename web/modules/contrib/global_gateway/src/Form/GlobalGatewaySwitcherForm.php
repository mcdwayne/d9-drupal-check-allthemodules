<?php

namespace Drupal\global_gateway\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\global_gateway\Helper;
use Drupal\global_gateway\RegionNegotiatorInterface;
use Drupal\global_gateway\SwitcherData\SwitcherDataPluginCollection;
use Drupal\global_gateway\SwitcherData\SwitcherDataPluginInterface;
use Drupal\global_gateway\SwitcherData\SwitcherDataPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides different switchers for region.
 */
class GlobalGatewaySwitcherForm extends FormBase {

  protected $limit;

  protected $nativeNames;

  protected $untranslatedContent;

  protected $languageList;

  /**
   * Region code.
   *
   * @var string
   */
  protected $regionCode;

  /**
   * Global Gateway Helper.
   *
   * @var \Drupal\global_gateway\Helper
   */
  protected $helper;

  /**
   * Region Negotiator Interface.
   *
   * @var \Drupal\global_gateway\RegionNegotiatorInterface
   */
  protected $negotiator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.global_gateway.switcher_data'),
      $container->get('global_gateway.helper'),
      $container->get('global_gateway_region_negotiator')
    );
  }

  /**
   * GlobalGatewaySwitcherForm constructor.
   *
   * @param \Drupal\global_gateway\SwitcherData\SwitcherDataPluginManager $data_manager
   *   The Switcher Plugin Manager object.
   * @param \Drupal\global_gateway\Helper $helper
   *   Global Gateway Helper.
   */
  public function __construct(SwitcherDataPluginManager $data_manager, Helper $helper, RegionNegotiatorInterface $negotiator, $providers = []) {
    $this->helper            = $helper;
    $this->switcherProviders = [['id' => 'global_gateway_language_switcher_data']];
    $this->switcherData      = new SwitcherDataPluginCollection($data_manager, $this->switcherProviders);
    $this->negotiator        = $negotiator;
    $this->languageList      = \Drupal::languageManager()->getStandardLanguageList();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'global_gateway_switcher_form';
  }

  /**
   * Returns language native name by langcode.
   *
   * @param string $langcode
   *   The language code, for example 'en'.
   *
   * @return array
   *   The string with language native name.
   */
  public function getLanguageNativeName(string $langcode = 'en') {
    return $this->languageList[$langcode][1];
  }

  /**
   * Add links to links container.
   *
   * @param string $container
   *   The links container.
   */
  public function addItems(&$container) {
    $entities = [];
    $entity = NULL;
    $entity_type = NULL;
    foreach (\Drupal::routeMatch()->getParameters() as $key => $param) {
      if ($param instanceof \Drupal\Core\Entity\EntityInterface) {
        $entities = [$key => $param];
      }
    }
    if (!empty($entities)) {
      $entity_type = array_keys($entities)[0];
      $entity = \Drupal::routeMatch()->getParameter($entity_type);
    }
    if (!is_null($entity) && !is_null($entity_type)) {
      $translations = $entity->getTranslationLanguages();
    }
    else {
      $translations = [];
    }

    $nativeNames = (int) $this->nativeNames;
    $untranslatedContent = (int) $this->untranslatedContent;

    if ($this->limit === 0) {
      $this->regionCode = 'none';
    }

    foreach ($this->switcherData as $item) {
      if ($item instanceof SwitcherDataPluginInterface) {
        $output = $item->getOutput($this->regionCode);
      }
      else {
        // Get Language Switch Links from language manager
        // if global_gateway_language is disabled.
        $links = \Drupal::languageManager()
          ->getLanguageSwitchLinks('language_interface', Url::fromRoute('<current>'));
        if ($links) {
          $links = (array) $links;
          foreach ($links["links"] as &$link) {
            if (!empty($link['query']['ajax_form'])) {
              unset($link['query']);
            }
          }
          unset($link);
          $output = [
            '#theme'            => 'links__language_block',
            '#links'            => $links["links"],
            '#attributes'       => [
              'class' => [
                "language-switcher-{$links["method_id"]}",
              ],
            ],
            '#set_active_class' => TRUE,
          ];
        } else {
          $output = NULL;
        }
      };
    }
    // Check for native names settings.
    if ($nativeNames == 1) {
      // Load every language switch link.
      foreach ($output['#links'] as $langcode => $link) {
        // Check if there is native name for language.
        if (!is_null($this->getLanguageNativeName($langcode))) {
          // Change language link title to native.
          $output['#links'][$langcode]['title'] = $this->getLanguageNativeName($langcode);
        }
      }
    }
    if (!is_null($output['#links'])) {
      // Common lang codes between entity translations and switch links.
      $available_translations_links = array_intersect_key($output['#links'], $translations);
      // Different lang codes between entity translations and switch links.
      $unavailable_translations_links = array_diff_key($output['#links'], $translations);
    }
    // Deletes links for untranslated content.
    if ($untranslatedContent == 0 && !is_null($translations) && !is_null($output['#links'])) {
      $output['#links'] = $available_translations_links;
    }

    // Sets URLs to front page for untranslated content.
    if ($untranslatedContent == 2 && !is_null($translations) && !is_null($output['#links'])) {
      $modified_translations_links = $unavailable_translations_links;
      foreach ($modified_translations_links as $langcode => $link) {
        $modified_translations_links[$langcode]['url'] = Url::fromRoute('<front>');
      }
      $output['#links'] = array_merge($modified_translations_links, $available_translations_links);
    }
    if (!is_null($output)) {
      $container['global_gateway_language_switcher_data'] = $output;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->limit               = $form_state->get('limit');
    $this->regionCode          = $form_state->get('current_region');
    $this->nativeNames         = $form_state->get('nativeNames');
    $this->untranslatedContent = $form_state->get('untranslatedContent');
    $regions                   = $this->getAvailableRegions();

    /*
    Available Region select displays:
    0 - None
    1 - Select list
    2 - select2boxes
     */
    $region_displays   = ['hidden', 'select', 'select'];
    $region_display    = $form_state->get('region_display');
    $languages_display = $form_state->get('languages_display');
    $flag_icons        = $form_state->get('flag_icons');

    /*
    Sets the 'Select list' for Region select display
    if there are no settings for <select> display
    or Region select display is set to select2boxes,
    but select2boxes module doesn't exist.
     */
    if (is_null($region_display) || ($region_display == 2 && !\Drupal::moduleHandler()
      ->moduleExists('select2boxes'))) {
      $region_display = 1;
    }
    $region = [
      '#type'          => $region_displays[$region_display],
      '#title'         => $this->t('Region'),
      '#title_display' => 'invisible',
      '#options'       => $regions,
      '#default_value' => $this->regionCode,
      '#empty_value'   => 'none',
      '#empty_option'  => '- Select Region -',
      '#ajax'          => [
        'callback' => '::updateBlock',
        'wrapper'  => 'new-region-list',
        'effect'   => 'fade',
        'event'    => 'change',
      ],
      '#attributes'    => [
        'class' => ['global-gateway-region'],
      ],
      '#weight'        => $form_state->get('regionWeight'),
    ];

    // Enable select2boxes for selected forms.
    if ($region_display == 2) {
      $region['#attributes'] = [
        'class' => [
          'global-gateway-region',
          'select2-widget',
        ],
        'data-jquery-once-autocomplete'         => 'true',
        'data-select2-autocomplete-list-widget' => 'true',
      ];
      $form['#attached']['library'][] = 'select2boxes/widget';
    }

    if (Helper::softDependenciesMeet()) {
      $region['#type']                = 'select_icons';
      $region['#options_attributes']  = Helper::getOptionAttributes($regions);
      $form['#attached']['library'][] = 'flags/flags';
      $form['#attached']['library'][] = 'select_icons/select_icons';
    }
    $form['region'] = $region;

    $items_id = $form_state->get('items_id');

    $form['items'] = [
      '#type'       => 'container',
      '#attributes' => ['data-replace-id' => $items_id],
      '#weight'     => $form_state->get('languagesWeight'),
    ];

    $form['items_id'] = [
      '#type'  => 'hidden',
      '#value' => $items_id,
    ];
    $form['limit'] = [
      '#type'  => 'hidden',
      '#value' => $this->limit,
    ];
    $form['nativeNames'] = [
      '#type'  => 'hidden',
      '#value' => $this->nativeNames,
    ];
    $form['untranslatedContent'] = [
      '#type'  => 'hidden',
      '#value' => $this->untranslatedContent,
    ];

    if (\Drupal::moduleHandler()->moduleExists('language')) {
      $this->addItems($form['items']);
    }


    if ($flag_icons == 1 && $region_display == 2) {
      $form['region']['#attributes']['class'][]            = 'flag-icon';
      $form['#attached']['library'][]                      = 'flags/flags';
      $form['#attached']['drupalSettings']['flagsClasses'] = $this->getFlagsClasses();
    }
    // Disable Languages display if it is set to None.
    if ($languages_display == 0) {
      $form['items'] = [];
    }
    $form_state->setCached(FALSE);

    return $form;
  }

  /**
   * Ajax callback for $form['region'].
   *
   * Rebuild switcher items.
   *
   * @param array $form
   *   The entire form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function updateBlock(array $form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    $data          = new \stdClass();

    $this->limit = Xss::filter($form_state->getUserInput()['limit']);
    $this->nativeNames = Xss::filter($form_state->getUserInput()['nativeNames']);
    $this->untranslatedContent = Xss::filter($form_state->getUserInput()['untranslatedContent']);

    if ($this->limit == 0) {
      $current_code = 'none';
    }
    else {
      $current_code = $form_state->getValue('region');
    }

    $data->region = $form_state->getValue('region');
    $this->regionCode = $current_code;
    $negotiator = $this->negotiator->getNegotiator('session');
    $negotiator->persist($form_state->getValue('region'));

    $this->addItems($form['items']);

    $items_id = $form_state->getValue('items_id');
    $selector = ".global-gateway-switcher-form [data-replace-id='" . $items_id . "']";

    $ajax_response->addCommand(new ReplaceCommand($selector, $form['items']));
    $ajax_response->addCommand(new InvokeCommand(NULL, 'globalGatewayEmmitRegionChange', [$data]));

    return $ajax_response;
  }

  /**
   * Build a list of regions.
   *
   * @return array
   *   The sorted regions.
   */
  protected function getAvailableRegions() {
    // @todo: Should we show here only regions that has some mapping.
    $regions = $this->helper->getRegionsList();

    uasort($regions, function ($a, $b) {
      return strnatcasecmp($a, $b);
    });

    return $regions;
  }

  /**
   * Build a list flags' CSS classes.
   *
   * @return array
   *   The sorted regions.
   */
  protected function getFlagsClasses() {
    $regions = $this->helper->getRegionsList();

    uasort($regions, function ($a, $b) {
      return strnatcasecmp($a, $b);
    });

    $regions_codes = array_keys($regions);
    $flags_classes = [];

    foreach ($regions_codes as $code) {
      $flags_classes[strtoupper($code)] = [
        'flag',
        'flag-' . strtolower($code),
        'country-flag',
      ];
    }

    return $flags_classes;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
