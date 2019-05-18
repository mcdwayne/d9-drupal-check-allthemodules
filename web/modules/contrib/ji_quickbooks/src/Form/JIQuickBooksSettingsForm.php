<?php

namespace Drupal\ji_quickbooks\Form;

use Drupal\ji_quickbooks\JIQuickBooksService;
use Drupal\ji_quickbooks\JIQuickBooksSupport;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\State\StateInterface;

/**
 * Manages settings and connectivity with QuickBooks.
 */
class JIQuickBooksSettingsForm extends FormBase {

  /**
   * Dependency injection.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheManager;

  /**
   * Dependency injection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $stateManager;

  /**
   * The name of our cache.
   *
   * @var string
   */
  private $company_name_cache = 'ji_quickbooks_company_name_cache';

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_manager
   *   Cache manager.
   * @param \Drupal\Core\State\StateInterface $state_manager
   *   State manager.
   */
  public function __construct(CacheBackendInterface $cache_manager, StateInterface $state_manager) {
    $this->cacheManager = $cache_manager;
    $this->stateManager = $state_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cache.default'), $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ji_quickbooks_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // If the module is not yet installed guide them to the right path.
    $ji_quickbooks_library = JIQuickBooksSupport::getLibraryPath();
    if (empty($ji_quickbooks_library)) {
      $this->messenger()
        ->addError($this->t("The QuickBooks SDK is missing. Please install that first."), FALSE);

      $form['ji_quickbooks'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('QuickBooks Settings'),
        '#collapsible' => FALSE,
      ];
      $sdk = Link::fromTextAndUrl($this->t('here'), Url::fromUri(JIQuickBooksSupport::$sdkUrl))
        ->toString();
      $list_items = [
        'Download SDK from ' . $sdk . ' and place in the libraries directory (root/libraries/).',
        'The installation of OAuth on your web server using the php.ini extension=oauth.so is required. '
        . "The module will alert you if it detects OAuth isn't installed on the web server.<br>",
        'If you need paid support, please contact us at <a href="mailto:support@joshideas.com">support@joshideas.com</a>.',
      ];

      $form['ji_quickbooks']['error_messages'] = [
        '#prefix' => '<ul><li>',
        '#markup' => implode('</li><li>', $list_items),
        '#suffix' => '</li></ul>',
      ];
      return $form;
    }

    // Won't load the screen if OAuth isn't detected.
    if (!JIQuickBooksSupport::checkForOauth(TRUE)) {
      return NULL;
    }

    $ji_quickbooks = new JIQuickBooksService();
    //$ji_quickbooks->oauthDisconnect();

    // Only show QuickBooks settings if we have an active realm.
    if (!empty($ji_quickbooks->realmId)) {
      $form['ji_quickbooks'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('QuickBooks Settings'),
        '#collapsible' => FALSE,
        '#tree' => FALSE,
        '#attached' => [
          'library' => 'ji_quickbooks/ji_quickbooks_form_css',
        ],
      ];

      $form['ji_quickbooks']['allow_editing_of_qbo_product_id'] = [
        '#type' => 'radios',
        '#title' => $this->t('Edit the QBO product ID?'),
        '#description' => $this->t("(Warning) Products which have been synced to QuickBooks will store an ID within the Drupal Commerce product. You can choose to edit this field but you must know what you're doing."),
        '#default_value' => $this->stateManager->get('ji_quickbooks_allow_editing_of_qbo_product_id', 0),
        '#options' => [
          0 => $this->t('No'),
          1 => $this->t('Yes'),
        ],
        '#ajax' => [
          'callback' => '::saveSettingsAjax',
          'event' => 'change',
          'wrapper' => '',
          'progress' => [
            'type' => 'throbber',
            'message' => $this->t('Saving...'),
          ],
        ],
      ];

      $form['ji_quickbooks']['auto_add_products'] = [
        '#type' => 'radios',
        '#title' => $this->t('When adding or updating products, sync them to QuickBooks?'),
        '#description' => $this->t("Any modifications to a product or additions of new ones will cause them to sync or be created in QuickBooks."),
        '#default_value' => $this->stateManager->get('ji_quickbooks_auto_add_products', 0),
        '#options' => [
          0 => $this->t('No'),
          1 => $this->t('Yes'),
        ],
        '#ajax' => [
          'callback' => '::saveSettingsAjax',
          'event' => 'change',
          'wrapper' => '',
          'progress' => [
            'type' => 'throbber',
            'message' => $this->t('Saving...'),
          ],
        ],
      ];

      $form['ji_quickbooks']['auto_add_products_checkout'] = [
        '#type' => 'radios',
        '#title' => $this->t("If the product doesn't exist during checkout, sync it to QuickBooks?"),
        '#description' => $this->t("We'll check if the product(s) exist during checkout and will automatically add those to QuickBooks."),
        '#default_value' => $this->stateManager->get('ji_quickbooks_auto_add_products_checkout', 0),
        '#options' => [
          0 => $this->t('No'),
          1 => $this->t('Yes'),
        ],
        '#ajax' => [
          'callback' => '::saveSettingsAjax',
          'event' => 'change',
          'wrapper' => '',
          'progress' => [
            'type' => 'throbber',
            'message' => $this->t('Saving...'),
          ],
        ],
      ];

      $form['ji_quickbooks_config_match_products'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Match products'),
        '#collapsible' => TRUE,
        '#collapsed' => !empty($this->stateManager->get('ji_quickbooks_settings_access_token', '')),
        '#tree' => FALSE,
      ];

      $form['ji_quickbooks_config_match_products']['markup'] = [
        '#markup' => "Before a product is synced we must check if that product already exists within QuickBooks. By default, we use the SKU fields to make that comparison but that can be changed here.",
      ];

      $entityManager = \Drupal::service('entity_field.manager');
      $available_drupal_fields = $entityManager->getFieldDefinitions('commerce_product_variation', 'commerce_product_variation');
      $field_options = [];
      foreach ($available_drupal_fields as $machine_name => $value) {
        $field_options[$machine_name] = $machine_name;
      }

      $form['ji_quickbooks_config_match_products']['search_before_sync_drupal'] = [
        '#type' => 'select',
        '#title' => $this->t("Drupal Commerce product field"),
        '#description' => $this->t(""),
        '#default_value' => $this->stateManager->get('ji_quickbooks_search_before_sync_drupal', 'sku'),
        '#options' => $field_options,
        '#ajax' => [
          'callback' => '::saveSettingsAjax',
          'event' => 'change',
          'wrapper' => '',
          'progress' => [
            'type' => 'throbber',
            'message' => $this->t('Saving...'),
          ],
        ],
      ];

      $form['ji_quickbooks_config_match_products']['search_before_sync_qbo'] = [
        '#type' => 'select',
        '#title' => $this->t("QuickBooks product field"),
        '#description' => $this->t(""),
        '#default_value' => $this->stateManager->get('ji_quickbooks_search_before_sync_qbo', 'Sku'),
        // Define our QBO Item fields.
        '#options' => [
          'Name' => 'Product name',
          'Sku' => 'Product SKU',
        ],
        '#ajax' => [
          'callback' => '::saveSettingsAjax',
          'event' => 'change',
          'wrapper' => '',
          'progress' => [
            'type' => 'throbber',
            'message' => $this->t('Saving...'),
          ],
        ],
      ];

      $form['ji_quickbooks']['refresh'] = [
        '#markup' => "<div>Loads fresh terms, payment types, and accounts. This won't disconnect you from QuickBooks.</div>",
      ];

      $form['ji_quickbooks']['refresh']['description'] = [
        '#type' => 'submit',
        '#value' => $this->t('Refresh'),
      ];

      $form['ji_quickbooks']['invoice'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Invoice'),
        '#collapsible' => FALSE,
      ];

      global $base_url;
      $img_path = $base_url . '/' . drupal_get_path('module', 'ji_quickbooks') . '/img/';

      $form['ji_quickbooks']['invoice']['term'] = [
        '#type' => 'select',
        '#title' => $this->t('Terms'),
        '#options' => $this->getCache('ji_quickbooks_terms_cache', 'getAllTerms', 'AccountType'),
        '#default_value' => $this->stateManager->get('ji_quickbooks_term', 0),
        //'#required' => TRUE,
        '#ajax' => [
          'callback' => '::saveSettingsAjax',
          'event' => 'change',
          'wrapper' => '',
          'progress' => [
            'type' => 'throbber',
            'message' => $this->t('Saving...'),
          ],
        ],
      ];

      $form['ji_quickbooks']['payment'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Payment'),
        '#collapsible' => FALSE,
      ];

      $form['ji_quickbooks']['payment']['payment_method'] = [
        '#type' => 'select',
        '#title' => $this->t('Payment method'),
        '#options' => $this->getCache('ji_quickbooks_payment_cache', 'getAllPaymentMethods', 'AccountType'),
        '#default_value' => $this->stateManager->get('ji_quickbooks_payment_method', 0),
        //'#required' => TRUE,
        '#ajax' => [
          'callback' => '::saveSettingsAjax',
          'event' => 'change',
          'wrapper' => '',
          'progress' => [
            'type' => 'throbber',
            'message' => $this->t('Saving...'),
          ],
        ],
      ];

      $form['ji_quickbooks']['payment']['payment_account'] = [
        '#type' => 'select',
        '#title' => $this->t('Deposit to'),
        '#options' => $this->getCache('ji_quickbooks_account_cache', 'getAccountsByType', 'AccountType', [
          'Other Current Asset',
          'Bank',
        ]),
        '#default_value' => $this->stateManager->get('ji_quickbooks_payment_account', 0),
        //'#required' => TRUE,
        '#ajax' => [
          'callback' => '::saveSettingsAjax',
          'event' => 'change',
          'wrapper' => '',
          'progress' => [
            'type' => 'throbber',
            'message' => $this->t('Saving...'),
          ],
        ],
      ];

      $form['ji_quickbooks_config_product_accounts'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Product accounts'),
        '#collapsible' => TRUE,
        '#collapsed' => !empty($this->stateManager->get('ji_quickbooks_settings_access_token', '')),
        '#tree' => FALSE,
      ];

      $form['ji_quickbooks_config_product_accounts']['markup'] = [
        '#markup' => 'QuickBooks requires we specify three accounts where your products transaction history will be saved. Please choose below.',
      ];

      $form['ji_quickbooks_config_product_accounts']['inventory_asset_account'] = $this->createAccountSelect('Inventory asset account', 'AccountSubType', [
        'Inventory',
      ]);

      $form['ji_quickbooks_config_product_accounts']['income_account'] = $this->createAccountSelect('Income account', 'AccountSubType', [
        'SalesOfProductIncome',
      ]);

      $form['ji_quickbooks_config_product_accounts']['expense_account'] = $this->createAccountSelect('Expense account', 'AccountSubType', [
        'SuppliesMaterialsCogs',
      ]);

      // Only show if the 'ji_commerce' module is enabled.
      if (\Drupal::moduleHandler()->moduleExists('ji_commerce')) {
        // Sync all products.
        $form['ji_quickbooks_config_batch_syncing'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Batch syncing'),
          '#collapsible' => TRUE,
          '#collapsed' => !empty($this->stateManager->get('ji_quickbooks_settings_access_token', '')),
          '#tree' => FALSE,
        ];

        $tax_option = $this->getTaxList();

        $form['ji_quickbooks_config_batch_syncing']['default_tax_for_old_orders'] = [
          '#type' => 'select',
          '#title' => $this->t("For the batch order functionality, should you use a default tax if one isn't found?"),
          '#options' => $tax_option,
          '#default_value' => $this->stateManager->get('ji_quickbooks_default_tax_for_old_orders', 0),
          '#ajax' => [
            'callback' => '::saveSettingsAjax',
            'event' => 'change',
            'wrapper' => '',
            'progress' => [
              'type' => 'throbber',
              'message' => $this->t('Saving...'),
            ],
          ],
        ];

        $form['ji_quickbooks_config_batch_syncing']['process_orders_newer_than'] = [
          '#type' => 'date',
          '#title' => $this->t('For batch order, process orders newer than'),
          '#default_value' => $this->stateManager->get('ji_quickbooks_process_orders_newer_than', time()),
          '#ajax' => [
            'callback' => '::saveSettingsAjax',
            'event' => 'change',
            'wrapper' => '',
            'progress' => [
              'type' => 'throbber',
              'message' => $this->t('Saving...'),
            ],
          ],
        ];

        $form['ji_quickbooks_config_batch_syncing']['process_orders_older_than'] = [
          '#type' => 'date',
          '#title' => $this->t('For batch order, process orders older than'),
          '#default_value' => $this->stateManager->get('ji_quickbooks_process_orders_older_than', time()),
          '#ajax' => [
            'callback' => '::saveSettingsAjax',
            'event' => 'change',
            'wrapper' => '',
            'progress' => [
              'type' => 'throbber',
              'message' => $this->t('Saving...'),
            ],
          ],
        ];

        $form['ji_quickbooks_config_batch_syncing']['sync_all_products'] = [
          '#type' => 'submit',
          '#value' => $this->t('Sync all products to QuickBooks'),
        ];

        $form['ji_quickbooks_config_batch_syncing']['sync_all_orders'] = [
          '#type' => 'submit',
          '#value' => $this->t('Sync all orders to QuickBooks'),
        ];


        // Sync all products.
        $form['ji_quickbooks_config_qbo_preferences'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('QuickBooks preferences'),
          '#collapsible' => TRUE,
          '#collapsed' => !empty($this->stateManager->get('ji_quickbooks_settings_access_token', '')),
          '#tree' => FALSE,
        ];

        $form['ji_quickbooks_config_qbo_preferences']['markup'] = [
          '#markup' => $this->t("Based on your QuickBooks account, the following settings are available."),
        ];

        $shipping_enabled = $this->stateManager->get('ji_quickbooks_config_qbo_preferences_shipping_field', 0);
        $form['ji_quickbooks_config_qbo_preferences']['shipping_field'] = [
          '#type' => 'radios',
          '#title' => $this->t('Shipping costs'),
          '#default_value' => $shipping_enabled,
          '#options' => [
            0 => 'Not enabled',
            1 => 'Enabled',
          ],
          '#disabled' => TRUE,
          '#description' => $this->t('If enabled withing your QuickBooks account, shipping costs are saved on the QBO invoice using the invoice shipping field. To enable this make the change within your QuickBooks online account.'),
        ];

        $discount_enabled = $this->stateManager->get('ji_quickbooks_config_qbo_preferences_discount_field', 0);
        $form['ji_quickbooks_config_qbo_preferences']['discount_field'] = [
          '#type' => 'radios',
          '#title' => $this->t('Discount field'),
          '#default_value' => $discount_enabled,
          '#options' => [
            0 => 'Not enabled',
            1 => 'Enabled',
          ],
          '#disabled' => TRUE,
          '#description' => $this->t('If enabled within your QuickBooks account, the discount field within your QBO invoice will be used.'),
        ];

        $form['ji_quickbooks_config_qbo_preferences']['discount_account'] = $this->createAccountSelect('Discount account');

        // We don't want the user to ever change this, since it should come
        // from QBO.
        $form['ji_quickbooks_config_qbo_preferences']['discount_account']['#disabled'] = TRUE;
      }

      $is_ji_commerce_taxes_enabled = \Drupal::moduleHandler()
        ->moduleExists('ji_commerce_taxes');
      $is_commerce_pos_enabled = \Drupal::moduleHandler()
        ->moduleExists('commerce_pos');
      if ($is_ji_commerce_taxes_enabled && $is_commerce_pos_enabled) {

        $form['ji_quickbooks_default_tax'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Default tax for the Commerce POS screen.'),
          '#collapsible' => TRUE,
          '#collapsed' => !empty($this->stateManager->get('ji_quickbooks_settings_access_token', '')),
          '#tree' => FALSE,
        ];

        $form['ji_quickbooks_default_tax']['markup'] = [
          '#markup' => $this->t("If you've synced taxes from QuickBooks, you'll have a list appear below with taxes prefixed with QBO. Please choose a default for use on the POS screen."),
        ];

        $tax_option = $this->getTaxList();

        $form['ji_quickbooks_default_tax']['default_tax'] = [
          '#type' => 'select',
          '#title' => $this->t('Available taxes.'),
          '#options' => $tax_option,
          '#default_value' => $this->stateManager->get('ji_quickbooks_default_tax', 0),
          '#ajax' => [
            'callback' => '::saveSettingsAjax',
            'event' => 'change',
            'wrapper' => '',
            'progress' => [
              'type' => 'throbber',
              'message' => $this->t('Saving...'),
            ],
          ],
        ];
      }

    }

    $form['ji_quickbooks_config'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('QuickBooks connectivity settings'),
      '#collapsible' => TRUE,
      '#collapsed' => !empty($this->stateManager->get('ji_quickbooks_settings_access_token', '')),
      '#tree' => TRUE,
    ];

    $connect_disabled = TRUE;
    // If realm_id is missing but consumer key and secret aren't, then
    // display 'Connect to QuickBooks' button.
    if (!isset($ji_quickbooks->dataService)) {
      $connect_disabled = FALSE;
    }

    $environments = [
      'dev' => $this->t('Development'),
      'pro' => $this->t('Production'),
    ];

    $form['ji_quickbooks_config']['environment'] = [
      '#type' => 'radios',
      '#title' => $this->t('Account type'),
      '#default_value' => $this->stateManager->get('ji_quickbooks_settings_environment', 'dev'),
      '#options' => $environments,
      '#required' => TRUE,
      '#disabled' => $connect_disabled,
    ];

    // Display which company we're connected to.
    if ($connect_disabled) {
      $form['ji_quickbooks_config']['company_info'] = [
        '#markup' => $this->t("You're connected to:") . " <br>" . $this->getCompanyNameCache() . "</b>",
        '#prefix' => '<h3>',
        '#suffix' => '</h3>',
      ];
    }

    $form['ji_quickbooks_config']['connect'] = [
      '#type' => 'submit',
      '#value' => $this->t('Connect to QuickBooks'),
      '#disabled' => $connect_disabled,
    ];

    $disconnect_disabled = FALSE;
    // If realm_id consumer key and secret are not empty then display
    // 'Disconnect from QuickBooks' button.
    if (empty($this->stateManager->get('ji_quickbooks_settings_realm_id', '')) ||
      empty($this->stateManager->get('ji_quickbooks_settings_access_token', '')) ||
      empty($this->stateManager->get('ji_quickbooks_settings_refresh_token', ''))) {
      $disconnect_disabled = TRUE;
    }

    $form['ji_quickbooks_config']['disconnect'] = [
      '#type' => 'submit',
      '#value' => $this->t('Disconnect from QuickBooks'),
      '#disabled' => $disconnect_disabled,
      // Logs the user out of their QBO account.
      '#attached' => [
        'library' => [
          'ji_quickbooks/ji_quickbooks_intuit_ipp_anywhere',
          'ji_quickbooks/ji_quickbooks_openid_logout',
        ],
      ],
    ];

    return $form;
  }

  /**
   * Return only QBO tax rates.
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getTaxList() {
    $tax_option = [];
    $tax_option[0] = '- Select tax -';
    $tax_type_storage = \Drupal::entityTypeManager()
      ->getStorage('commerce_tax_type');
    $tax_types = $tax_type_storage->loadMultiple();
    foreach ($tax_types as $tax_type) {
      // Ignore disabled rates.
      if (!$tax_type->status()) {
        continue;
      }
      $prefix = '';
      if (strpos($tax_type->id(), 'quickbooks_tax_id') !== FALSE) {
        $prefix = 'QBO - ';
      }
      else {
        continue;
      }
      $tax_option[$tax_type->id()] = (!empty($prefix)) ? $prefix . ' ' . $tax_type->label() : $tax_type->label();
    }

    ksort($tax_option);

    return $tax_option;
  }

  private function createAccountSelect($field_name, $where = 'AccountType', $filter = []) {
    $lower_case_field_name = strtolower($field_name);
    $machine_name = str_replace(' ', '_', $lower_case_field_name);
    return [
      '#type' => 'select',
      '#title' => $this->t($field_name),
      '#options' => $this->getCache('ji_quickbooks_' . $machine_name, 'getAccountsByType', $where, $filter),
      '#default_value' => $this->stateManager->get('ji_quickbooks_' . $machine_name, 0),
      //'#required' => TRUE,
      '#ajax' => [
        'callback' => '::saveSettingsAjax',
        'event' => 'change',
        'wrapper' => '',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Saving...'),
        ],
      ],
    ];
  }

  /**
   * Returns or caches data from QBO.
   *
   * Greatly improves page loads.
   *
   * @param string $name
   *   Use 'ji_quickbooks_terms_cache', 'ji_quickbooks_payment_cache',
   *   'ji_quickbooks_account_cache'.
   * @param string $function_name
   *   Can use 'getAllProducts', 'getAllTerms', 'getAllPaymentMethods',
   *   'getAccountsByType'.
   * @param string $where
   *   AccountType of FullQualifiedName for example.
   * @param array $query_options
   *   If $function_name has parameters.
   *
   * @return array | boolean | string
   *   Returns an array, string, or TRUE if there was an error.
   */
  private function getCache($name, $function_name, $where = 'AccountType', array $query_options = []) {
    $option = &drupal_static($name);
    if (!isset($option)) {
      $cache = $this->cacheManager->get($name);
      if ($cache) {
        $option = $cache->data;
      }
      else {
        $quickbooks_service = new JIQuickBooksService();
        if (isset($quickbooks_service)) {
          $response = $quickbooks_service->$function_name($where, $query_options);
          $error = $quickbooks_service->checkErrors($response);
          if (!isset($error['code'])) {
            $response_options = [];
            $response_options[0] = 'Select...';
            foreach ($response as $item) {
              $response_options[$item->Id] = $item->Name;
            }
            $this->cacheManager->set($name, $response_options);

            return $response_options;
          }
          else {
            return [
              0 => $error['message'],
            ];
          }
        }
        // Must have been an error.
        $option = [
          0 => 'Error connecting to QuickBooks.',
        ];
      }
    }

    return $option;
  }

  /**
   * Get or set QBO cache data.
   */
  private function getCompanyNameCache() {
    $company_name = &drupal_static(__FUNCTION__);
    if (!isset($company_name)) {
      $cache = $this->cacheManager->get($this->company_name_cache);
      if ($cache) {
        $company_name = $cache->data;
      }
      else {
        $quickbooks_service = new JIQuickBooksService();
        $response = $quickbooks_service->getCompanyData();
        $error = $quickbooks_service->checkErrors();
        if (!empty($error['code'])) {
          return TRUE;
        }

        $this->cacheManager->set($this->company_name_cache, $response[0]->CompanyName);

        return $response[0]->CompanyName;
      }
    }
    return $company_name;
  }

  /**
   * Ajax callback tied to the select fields.
   *
   * Uses the form field name to set() data.
   */
  public function saveSettingsAjax(array &$form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    $this->stateManager->set('ji_quickbooks_' . $element['#name'], $element['#value']);

    // Get which form element triggered the AJAX call and return it so
    // Drupal refreshes its changed state.
    $count = count($element['#array_parents']);
    if ($count) {
      $elem = NULL;
      switch ($count) {
        case 1:
          $variable0 = $element['#array_parents'][0];
          $elem = $form[$variable0];
          break;
        case 2:
          $variable0 = $element['#array_parents'][0];
          $variable1 = $element['#array_parents'][1];
          $elem = $form[$variable0][$variable1];
          break;
        case 3:
          $variable0 = $element['#array_parents'][0];
          $variable1 = $element['#array_parents'][1];
          $variable2 = $element['#array_parents'][2];
          $elem = $form[$variable0][$variable1][$variable2];
          break;
        case 4:
          $variable0 = $element['#array_parents'][0];
          $variable1 = $element['#array_parents'][1];
          $variable2 = $element['#array_parents'][2];
          $variable3 = $element['#array_parents'][3];
          $elem = $form[$variable0][$variable1][$variable2][$variable3];
          break;
        case 5:
          $variable0 = $element['#array_parents'][0];
          $variable1 = $element['#array_parents'][1];
          $variable2 = $element['#array_parents'][2];
          $variable3 = $element['#array_parents'][3];
          $variable4 = $element['#array_parents'][4];
          $elem = $form[$variable0][$variable1][$variable2][$variable3][$variable4];
          break;
      }
      if (isset($elem)) {
        return ['#markup' => \Drupal::service('renderer')->render($elem)];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Enter the void.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    $this->stateManager->set('ji_quickbooks_settings_environment', $values['ji_quickbooks_config']['environment']);

    switch ($form_state->getTriggeringElement()['#value']) {
      case 'Refresh':
        $this->refresh();
        break;

      case 'Connect to QuickBooks':
        $this->connect($form, $form_state);
        break;

      case 'Disconnect from QuickBooks':
        $this->disconnect();
        break;

      case 'Sync all products to QuickBooks':
        $batch = $this->batchSyncProducts();
        batch_set($batch);
        break;

      case 'Sync all orders to QuickBooks':
        $batch = $this->batchSyncOrders();
        batch_set($batch);
        break;
    }
  }

  private function batchSyncProducts() {
    $sync_via_field = $this->stateManager->get('ji_quickbooks_search_before_sync_drupal', 0);
    $operations[] = [
      'ji_quickbooks_op_match_by_field',
      [$this->t('(Matching existing products from QuickBooks to Drupal using the "@field_name" field)', ['@field_name' => $sync_via_field])],
    ];

    $operations[] = [
      'ji_quickbooks_op_sync_products',
      [$this->t('(Sync all active Drupal products to QuickBooks)')],
    ];

    $batch = [
      'operations' => $operations,
      'finished' => 'ji_quickbooks_op_2_finished',
      'title' => $this->t('Processing batch'),
      'init_message' => $this->t('Batch is starting.'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'error_message' => $this->t('Batch has encountered an error.'),
    ];
    return $batch;
  }

  private function batchSyncOrders() {
    //$sync_via_field = $this->stateManager->get('ji_quickbooks_search_before_sync_drupal', 0);
    $operations[] = [
      'ji_quickbooks_op_sync_customers_and_orders',
      [
        $this->t('Matching existing orders from Drupal to QuickBooks'),
      ],
    ];

    //    $operations[] = [
    //      'ji_quickbooks_op_sync_products',
    //      [$this->t('(Sync all active Drupal products to QuickBooks)')],
    //    ];

    $batch = [
      'operations' => $operations,
      'finished' => 'ji_quickbooks_op_2_finished',
      'title' => $this->t('Processing batch'),
      'init_message' => $this->t('Batch is starting.'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'error_message' => $this->t('Batch has encountered an error.'),
    ];
    return $batch;
  }

  /**
   * Clears the cache to reload new QuickBooks settings.
   */
  private function refresh() {
    // Clear the cache manager settings.
    $this->cacheManager->delete('ji_quickbooks_terms_cache');
    $this->cacheManager->delete('ji_quickbooks_payment_cache');
    $this->cacheManager->delete('ji_quickbooks_account_cache');
    $this->cacheManager->delete('ji_quickbooks_inventory_asset_account');
    $this->cacheManager->delete('ji_quickbooks_income_account');
    $this->cacheManager->delete('ji_quickbooks_expense_account');
    $this->cacheManager->delete('ji_quickbooks_discount_account');

    // Clear the state manager settings.
    $this->stateManager->delete('ji_quickbooks_config_qbo_preferences_shipping_field');
    $this->stateManager->delete('ji_quickbooks_config_qbo_preferences_discount_field');
    $this->stateManager->delete('ji_quickbooks_discount_account');

    $this->getPreferences();
  }

  public function getPreferences() {
    // Get QBO options.
    $ji_quickbooks = new JIQuickBooksService();
    $preferences = $ji_quickbooks->dataService->Query("select * from Preferences");
    if ($preferences) {
      $preferences = reset($preferences);
      /** @var \QuickBooksOnline\API\Data\IPPPreferences $preferences */
      $shipping_enabled = ($preferences->SalesFormsPrefs->AllowShipping === 'true') ? 1 : 0;
      $this->stateManager->set('ji_quickbooks_config_qbo_preferences_shipping_field', $shipping_enabled);

      $discount_enabled = ($preferences->SalesFormsPrefs->AllowDiscount === 'true') ? 1 : 0;
      $this->stateManager->set('ji_quickbooks_config_qbo_preferences_discount_field', $discount_enabled);
      $this->stateManager->set('ji_quickbooks_discount_account', $preferences->SalesFormsPrefs->DefaultDiscountAccount);
    }
  }

  /**
   * Submit handler.
   *
   * Starts the OpenID communication process with QBO.
   */
  private function connect(array &$form, FormStateInterface $form_state) {

    $host = \Drupal::request()->getSchemeAndHttpHost();
    $return_url = $host . Url::fromRoute('ji_quickbooks.saveoauthsettings')
        ->toString();
    $environment = $this->stateManager->get('ji_quickbooks_settings_environment', 'dev');
    $parameters = '?connectWithIntuitOpenId=&return_url=' . $return_url . '&environment=' . $environment;

    $myurl = JIQuickBooksSupport::$oAuthUrl . $parameters;
    $response = new TrustedRedirectResponse($myurl);

    $metadata = $response->getCacheableMetadata();
    $metadata->setCacheMaxAge(0);

    $form_state->setResponse($response);
  }

  /**
   * Disconnect from QBO.
   *
   * See openid_logout.js - logs off their QuickBooks OpenID session
   * as well.
   */
  private function disconnect() {
    // Delete QBO settings.
    $this->stateManager->delete('ji_quickbooks_term');
    $this->stateManager->delete('ji_quickbooks_payment_method');
    $this->stateManager->delete('ji_quickbooks_payment_account');
    // Clear access token.
    $this->stateManager->delete('ji_quickbooks_settings_access_token');
    $this->stateManager->delete('ji_quickbooks_settings_refresh_token');
    $this->stateManager->delete('ji_quickbooks_settings_realm_id');
    // Remove counter since user disconnected QuickBooks.
    // Added again if reconnect occurs.
    $this->stateManager->delete('ji_quickbooks_cron_started_on');
    // Delete product reference ID's.
    $this->stateManager->delete('ji_quickbooks_inventory_asset_account');
    $this->stateManager->delete('ji_quickbooks_income_account');
    $this->stateManager->delete('ji_quickbooks_expense_account');
    $this->stateManager->delete('ji_quickbooks_allow_editing_of_qbo_product_id');
    $this->stateManager->delete('ji_quickbooks_discount_account');

    // Delete cached responses.
    $this->cacheManager->delete('ji_quickbooks_terms_cache');
    $this->cacheManager->delete('ji_quickbooks_payment_cache');
    $this->cacheManager->delete('ji_quickbooks_account_cache');
    $this->cacheManager->delete('ji_quickbooks_inventory_asset_account');
    $this->cacheManager->delete('ji_quickbooks_income_account');
    $this->cacheManager->delete('ji_quickbooks_expense_account');
    $this->cacheManager->delete($this->company_name_cache);
  }

}
