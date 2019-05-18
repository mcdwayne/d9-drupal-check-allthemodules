<?php

namespace Drupal\haystack\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\State\StateInterface;
use Drupal\haystack\HaystackCore;
use Symfony\Component\DependencyInjection\ContainerInterface;

class HaystackSettings extends FormBase {

  /**
   * The Haystack core service.
   *
   * @var \Drupal\haystack\HaystackCore
   */
  protected $haystack;

  /**
   * The state interface.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The DateTime service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * LanguageManager Service.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * HaystackSettings constructor.
   *
   * @param \Drupal\haystack\HaystackCore $haystack
   *   Haystack Core Service.
   * @param \Drupal\Core\State\StateInterface $state
   *   StateInterface Service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   FateFormatter Service.
   * @param \Drupal\Core\Language\LanguageManager $languageManager
   *   LanguageManager Service.
   */
  public function __construct(
    HaystackCore $haystack,
    StateInterface $state,
    DateFormatterInterface $dateFormatter,
    LanguageManager $languageManager
  ) {
    $this->haystack = $haystack;
    $this->state = $state;
    $this->dateFormatter = $dateFormatter;
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('haystack.core'),
      $container->get('state'),
      $container->get('date.formatter'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'haystack_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['haystack.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $form['#attached']['library'][] = 'haystack/styles';

    $health = $this->haystack->healthCheck();
    $api_key = $this->haystack->getSetting('api_key');

    $form['original_api_key'] = [
      '#type' => 'value',
      '#value' => $api_key,
    ];

    // API Key.
    $form['api'] = [
      '#type' => 'details',
      '#tree' => FALSE,
      '#title' => $this->t('Server Settings'),
      '#open' => empty($api_key) ? TRUE : FALSE,
      '#description' => $this->t('Please enter your API key.  
        To get an API key, register at <a href="@register_link" target="_blank">Haystack</a>; 
        after logging-in, click "Add a New Site".',
        ['@register_link' => 'https://haystack.menu/register']),
    ];

    $form['api']['api_key'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('API Key'),
      '#default_value' => $api_key,
    ];

    $form['api']['dev_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Read-Only Mode'),
      '#description' => $this->t('If enabled no data will be send to the Haystack server.'),
      '#default_value' => $this->haystack->getSetting('dev_mode'),
    ];

    if ($health == 0) {
      // Init.
      $form['actions']['save_config'] = [
        '#type' => 'submit',
        '#title' => $this->t('Save Configuration'),
        '#default_value' => $this->t('Save Configuration'),
      ];
    }
    else {
      // Ready to init.
      // $haystack_status = $this->haystack->searchStatus();
      $search_id_key = $this->haystack->getSetting('search_id_key');
      $haystack_quick_links = $this->haystack->getSetting('quick_links');
      $haystack_quick_links_title = $this->haystack->getSetting('quick_links_title');

      // $status = $this->t('Cron last ran: %time ago.';
      // ['%time' => $this->dateFormatter->formatTimeDiffSince($this->state->get('system.cron_last'))]);
      if ($health > 1) {
        $process_total = $this->haystack->getSetting('process_total', 0);
        $process_pos = $this->haystack->getSetting('process_pos', 0);

        // Search Update Status.
        $form['status'] = [
          '#type' => 'fieldset',
          '#tree' => FALSE,
          '#title' => $this->t('Search Indexer Status'),
          '#collapsible' => TRUE,
          '#collapsed' => FALSE,
        ];

        $haystack_total = $this->haystack->haystackTotalItems();

        $form['status']['#description'] = $this->t('<p>Your Haystack index contains @haystack_total items.', ['@haystack_total' => $haystack_total]);
        if ($process_total != 0) {
          // $percent = round(($process_pos / $process_total) * 100);
          $form['status']['#description'] .= ' There are ' . ($process_total - $process_pos) . ' items left in the queue. Please note that the queue may be larger than the total of items depending on when you last ran Cron.';
        }
        $form['status']['#description'] .= '</p>';
      }

      // Id for Search Field.
      $form['searchId'] = [
        '#type' => 'details',
        '#tree' => FALSE,
        '#title' => $this->t('Search Field'),
        '#open' => FALSE,
      ];

      $form['searchId']['search_id_key'] = [
        '#type' => 'textfield',
        '#required' => FALSE,
        '#title' => $this->t('Search ID Field'),
        '#default_value' => $search_id_key,
        '#description' => $this->t('The ID of the search field on your site (e.g. #search_id). If left blank the Haystack footer will be added to the website.'),
      ];

      $form['searchId']['search_page_url'] = [
        '#type' => 'textfield',
        '#required' => FALSE,
        '#title' => $this->t('Search Page URL'),
        '#default_value' => $this->haystack->getSetting('search_page_url', ''),
        '#description' => $this->t('The URL for the full site search. The current search phrase will be appended to the URL entered here.'),
      ];

      // Links.
      $form['ql'] = [
        '#type' => 'details',
        '#tree' => FALSE,
        '#title' => $this->t('Quick Links'),
        '#open' => empty($haystack_quick_links) || empty($haystack_quick_links_title) ? TRUE : FALSE,
        '#description' => $this->t('Please enter the top links and titles for your website. These will appear as the top links when Haystack is launched. If left empty the results will show up blank.'),
      ];

      $form['ql']['quick_links_menu'] = [
        '#type' => 'select',
        '#title' => $this->t('Menu'),
        '#description' => $this->t('Chose where quick links will be defined.'),
        '#default_value' => $this->haystack->getSetting('quick_links_menu', 'none'),
        '#options' => [
          'none' => $this->t('None'),
          'menu' => $this->t('Haystack Menu'),
          'custom' => $this->t('Custom'),
        ],
      ];

      $form['ql']['quick_links_title'] = [
        '#type' => 'textfield',
        '#required' => FALSE,
        '#title' => $this->t('Title for the quick links field.'),
        '#default_value' => $haystack_quick_links_title,
        '#states' => [
          'invisible' => [
            'select[name="quick_links_menu"]' => ['value' => 'none'],
          ],
        ],
      ];

      $form['ql']['quick_links'] = [
        '#type' => 'textarea',
        '#required' => FALSE,
        '#title' => $this->t('Custom Links'),
        '#description' => $this->t('<p>Additional links that can be added to the menu selected above. Add one link per line and use the following format:</p><pre>&lt;li&gt;&lt;a href="LINK"&gt;TITLE&lt;/a&gt;&lt;/li&gt;</pre>'),
        '#default_value' => $haystack_quick_links,
        '#states' => [
          'visible' => [
            'select[name="quick_links_menu"]' => ['value' => 'custom'],
          ],
        ],
      ];

      // Content Types.
      $form['ct'] = [
        '#type' => 'details',
        '#tree' => FALSE,
        '#title' => $this->t('Content Types'),
        '#open' => TRUE,
      ];

      // Types.
      $types = $this->haystack->getContentTypes(TRUE);
      if ($health == 1) {
        $types_value = [];
        // array_keys($types);
      }
      else {
        $types_value = $this->haystack->getContentTypes();
      }
      $form['ct']['content_types'] = [
        '#type' => 'checkboxes',
        '#required' => FALSE,
        '#title' => $this->t('Include the following content types in the search index:'),
        '#default_value' => $types_value,
        '#options' => $types,
      ];

      $form['menu'] = [
        '#type' => 'details',
        '#tree' => FALSE,
        '#title' => $this->t('Menus'),
        '#open' => TRUE,
      ];

      // Menus.
      $menus = menu_ui_get_menus();
      if ($health == 1) {
        $menus_value = [];
        // array_keys($menus);
      }
      else {
        $menus_value = $this->haystack->getSetting('menus');
      }

      $form['menu']['menus'] = [
        '#type' => 'checkboxes',
        '#required' => FALSE,
        '#title' => $this->t('Include the following menus in the search index:'),
        '#default_value' => $menus_value,
        '#options' => $menus,
      ];

      /** @var \Drupal\Core\Language\Language[] $systemLanguages */
      $systemLanguages = $this->languageManager->getLanguages();
      /** @var \Drupal\Core\Language\Language $defaultLanguage */
      $defaultLanguage = $this->languageManager->getDefaultLanguage();

      if (count($systemLanguages) > 1) {

        $languageOptions = [];
        foreach ($systemLanguages as $id => $systemLanguage) {
          if ($id != $defaultLanguage->getId()) {
            $languageOptions[$systemLanguage->getId()] = $systemLanguage->getName();
          }
        }

        $form['lang'] = [
          '#type' => 'details',
          '#tree' => FALSE,
          '#title' => $this->t('Languages'),
          '#open' => TRUE,
          '#description' => $this->t('%default content is indexed by default.', ['%default' => $defaultLanguage->getName()]),
        ];

        $form['lang']['languages'] = [
          '#type' => 'checkboxes',
          '#required' => FALSE,
          '#title' => $this->t('Include the following additional languages in the search index:'),
          '#default_value' => $this->haystack->getSetting('languages', []),
          '#options' => $languageOptions,
        ];

      }

      $form['advanced'] = [
        '#type' => 'details',
        '#tree' => FALSE,
        '#title' => $this->t('Advanced Settings'),
        '#open' => FALSE,
      ];
      $form['advanced']['decay_factor'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Decay Factor'),
        '#description' => $this->t('This option allows to have older posts ranked lower. 
        Enter the time after which posts should lose importance. Format: <pre>7d = 7 days <br>2w = 2 weeks <br>1m = 1 month</pre>'),
        '#required' => FALSE,
        '#default_value' => $this->haystack->getSetting('decay_factor', ''),
      ];

      $form['actions']['save_config'] = [
        '#type' => 'submit',
        '#title' => $this->t('Save Configuration'),
        '#default_value' => $this->t('Save Configuration'),
      ];

      if ($health > 1) {
        $index_title = $this->t('Re-Index Website');
        $form['actions']['re_index'] = [
          '#type' => 'submit',
          '#title' => $index_title,
          '#default_value' => $index_title,
          '#submit' => ['::reindexSubmit'],
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $decayFactor = $form_state->getValue('decay_factor');
    if (!empty($decayFactor) && !preg_match('/^[0-9]+[w|d|m]$/', $decayFactor)) {
      $form_state->setErrorByName('decay_factor', 'Decay factor format is  incorrect. Please see the help text below the field.');
    }
    // @codingStandardsIgnoreStart
    //  if ($form_state->getValue(['op']) == 'Save Configuration') {
    //    _haystack_update_config($form, $form_state);
    //     drupal_set_message('Haystack save config.');
    //  }
    //  elseif ($form_state->getValue(['op']) == 'Re-Index Website') {
    //    drupal_set_message('Haystack rebuild');
    //    _haystack_submit_reindex($form, $form_state);
    //  }
    // @codingStandardsIgnoreEnd
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $ori_key = $form_state->getValue('original_api_key');
    $new_key = $form_state->getValue('api_key');
    $health = $this->haystack->healthCheck();

    $original_types = [
      'content_types' => $this->haystack->getSetting('content_types'),
      'menus' => $this->haystack->getSetting('menus'),
    ];

    $this->saveDefaultFields($form_state->cleanValues()->getValues());

    // Clear out client link and token if something is off with the API key.
    if (empty($ori_key) || $ori_key != $new_key) {
      $this->haystack->setSetting('client_hash', '');

      $success = $this->haystack->getCredentials($new_key);
      if (!$success) {
        $this->haystack->setSetting('api_key', '');
        drupal_set_message($this->t('The API Key you are using is not valid.'),
          'error');
        return;
      }

      // Else reprocess all content and send to the new index.
      $package = [
        'api_token' => $new_key,
      ];
      $this->haystack->apiCall($package, 'index', 'delete_all');
      $this->haystack->setSetting('content_types', []);
      $this->haystack->setSetting('menus', []);

    }

    if ($health == 1) {
      $this->haystack->setSetting('first_index', TRUE);
    }
    if ($health > 0) {
      $this->processContentMenuChanges($form_state, $original_types);
    }

    drupal_set_message($this->t('Haystack configuration saved.'));
  }

  /**
   * Completely reindex the site.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state.
   */
  public function reindexSubmit(array &$form, FormStateInterface $form_state) {
    $new_key = $form_state->getValue('api_key');

    // Delete all on server.
    $package = [
      'api_token' => $new_key,
    ];
    $this->haystack->apiCall($package, 'index', 'delete_all');

    // Clear.
    $this->haystack->resetMeterTotal();

    $original_types = [
      'content_types' => [],
      'menus' => [],
    ];

    // Process new contents against empty initial results.
    $this->processContentMenuChanges($form_state, $original_types);
  }

  /**
   * Handel changes to menu settings.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state.
   * @param array $original_types
   *   The original values.
   */
  private function processContentMenuChanges(FormStateInterface $form_state, array $original_types) {

    $new_key = $form_state->getValue('api_key');

    // Check if content types have changed.
    $c_name = 'content_types';
    $m_name = 'menus';

    $types = [
      $c_name => $form_state->getValue('content_types'),
      $m_name => $form_state->getValue('menus'),
    ];

    $diff_add = [$c_name => [], $m_name => []];
    $diff_sub = [$c_name => [], $m_name => []];

    foreach ($types as $key => $val) {
      // Checks the difference and adds them, subtract or add.
      foreach ($val as $k => $v) {
        $ori = isset($original_types[$key][$k]) && $original_types[$key][$k] != '0';
        $new = $v != '0';

        if ($new && !$ori) {
          $diff_add[$key][$k] = $v;
        }
        elseif ($ori && !$new) {
          $diff_sub[$key][$k] = $v;
        }
      }
    }

    $package = [
      'api_token' => $new_key,
    ];

    // Delete Array.
    foreach ($diff_sub as $key => $val) {
      // We use keys as the val may be set 0.
      if (!empty($val)) {
        // Delete by Post Type.
        if ($key == $c_name) {
          // Content.
          foreach ($val as $k => $v) {
            // Delete each type individually.
            $this->haystack->apiCall($package, $k, 'delete_type');
          }
        }

        // Delete by Menu Name.
        if ($key == $m_name) {
          // Delete is second param.
          $this->haystack->indexMenus(array_keys($val), TRUE);
        }
      }
    }

    // Add Array.
    $tmp_total = 0;
    foreach ($diff_add as $key => $val) {
      // We use keys as the val may be set 0.
      if (!empty($val)) {
        drupal_set_message($this->t('Haystack has added the following @key: @keys',
          ['@key' => $key, '@keys' => implode(', ', array_keys($val))]
        ));

        // Update by Post Type.
        if ($key == $c_name) {
          foreach ($val as $k => $v) {
            $nids = $this->haystack->getNodes($k);
            $tmp_total += count($nids);

            foreach ($nids as $nid) {
              // Mark node for reindexing.
              node_reindex_node_search($nid);
            }
          }
        }

        // Update by Menu Name.
        elseif ($key == $m_name) {
          // Reindex menu.
          $this->haystack->indexMenus(array_keys($val));
        }
      }
    }

    $this->haystack->addMeterTotal($tmp_total);
  }

  /**
   * Helper function to save all default values.
   *
   * @param array $values
   *   Field values.
   */
  private function saveDefaultFields(array $values) {
    foreach ($values as $key => $field) {
      $this->haystack->setSetting($key, $field);
    }
  }

}
