<?php
/**
 * @file
 * Contains \Drupal\solr_qb_Form\SolrQbBuilderForm.
 */

namespace Drupal\solr_qb\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SolrQbBuilderForm extends FormBase {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * SolrQbDriver plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * Drupal module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, PluginManagerInterface $plugin_manager, ModuleHandlerInterface $module_handler) {
    $this->configFactory = $config_factory;
    $this->pluginManager = $plugin_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.solr_qb_driver'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'solr_qb_builder_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['qt'] = [
      '#title' => 'qt',
      '#description' => $this->t('Request-Handler'),
      '#type' => 'textfield',
      '#default_value' => '/select',
    ];
    $form['common'] = [
      '#title' => 'common',
      '#type' => 'fieldset',
      '#tree' => TRUE,
    ];
    $form['common']['q'] = [
      '#title' => 'q',
      '#description' => $this->t('The query string'),
      '#type' => 'textarea',
      '#default_value' => '*:*',
    ];
    $form['common']['fq'] = [
      '#title' => 'fq',
      '#description' => $this->t('Filter query'),
      '#type' => 'textfield',
    ];
    $form['common']['sort'] = [
      '#title' => 'sort',
      '#description' => $this->t('Sort field or function with asc|desc'),
      '#type' => 'textfield',
    ];
    $form['common']['start'] = [
      '#title' => 'start',
      '#description' => $this->t('Number of leading documents to skip. (Integer)'),
      '#type' => 'textfield',
    ];
    $form['common']['rows'] = [
      '#title' => 'rows',
      '#description' => $this->t('Number of documents to return after "start". (Integer)'),
      '#type' => 'textfield',
    ];
    $form['common']['fl'] = [
      '#title' => 'fl',
      '#description' => $this->t('Field list, comma separated'),
      '#type' => 'textfield',
    ];
    $form['common']['df'] = [
      '#title' => 'df',
      '#description' => $this->t('Default search field'),
      '#type' => 'textfield',
    ];
    $form['common']['raw'] = [
      '#title' => $this->t('Raw Query Parameters'),
      '#type' => 'textfield',
    ];
    $form['common']['indent'] = [
      '#title' => 'indent',
      '#description' => $this->t('Indent results'),
      '#type' => 'checkbox',
    ];
    $form['common']['debug_query'] = [
      '#title' => 'debugQuery',
      '#description' => $this->t('Show timing and diagnostics.'),
      '#type' => 'checkbox',
    ];

    // Dismax.
    $form['dismax'] = [
      '#title' => 'dismax',
      '#type' => 'checkbox',
    ];
    $form['dismax_wrapper'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="dismax"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['dismax_wrapper']['q.alt'] = [
      '#title' => 'q.alt',
      '#description' => $this->t('Alternate query when "q" is absent.'),
      '#type' => 'textfield',
    ];
    $form['dismax_wrapper']['qf'] = [
      '#title' => 'qf',
      '#description' => $this->t('Query fields with optional boosts.'),
      '#type' => 'textfield',
    ];
    $form['dismax_wrapper']['mm'] = [
      '#title' => 'mm',
      '#description' => $this->t('Min-should-match expression.'),
      '#type' => 'textfield',
    ];
    $form['dismax_wrapper']['pf'] = [
      '#title' => 'pf',
      '#description' => $this->t('Phrase boosted fields.'),
      '#type' => 'textfield',
    ];
    $form['dismax_wrapper']['ps'] = [
      '#title' => 'ps',
      '#description' => $this->t('Phrase boost slop.'),
      '#type' => 'textfield',
    ];
    $form['dismax_wrapper']['qs'] = [
      '#title' => 'qs',
      '#description' => $this->t('Query string phrase slop.'),
      '#type' => 'textfield',
    ];
    $form['dismax_wrapper']['tie'] = [
      '#title' => 'tie',
      '#description' => $this->t('Score tie-breaker. Try 0.1.'),
      '#type' => 'textfield',
    ];
    $form['dismax_wrapper']['bq'] = [
      '#title' => 'bq',
      '#description' => $this->t('Boost query.'),
      '#type' => 'textfield',
    ];
    $form['dismax_wrapper']['bf'] = [
      '#title' => 'bf',
      '#description' => $this->t('Boost function (added).'),
      '#type' => 'textfield',
    ];

    // eDismax.
    $form['edismax'] = [
      '#title' => 'edismax',
      '#type' => 'checkbox',
    ];
    $form['edismax_wrapper'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="edismax"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['edismax_wrapper']['q.alt'] = [
      '#title' => 'q.alt',
      '#description' => $this->t('Alternate query when "q" is absent.'),
      '#type' => 'textfield',
    ];
    $form['edismax_wrapper']['qf'] = [
      '#title' => 'qf',
      '#description' => $this->t('Query fields with optional boosts.'),
      '#type' => 'textfield',
    ];
    $form['edismax_wrapper']['mm'] = [
      '#title' => 'mm',
      '#description' => $this->t('Min-should-match expression.'),
      '#type' => 'textfield',
    ];
    $form['edismax_wrapper']['pf'] = [
      '#title' => 'pf',
      '#description' => $this->t('Phrase boosted fields.'),
      '#type' => 'textfield',
    ];
    $form['edismax_wrapper']['ps'] = [
      '#title' => 'ps',
      '#description' => $this->t('Phrase boost slop.'),
      '#type' => 'textfield',
    ];
    $form['edismax_wrapper']['qs'] = [
      '#title' => 'qs',
      '#description' => $this->t('Query string phrase slop.'),
      '#type' => 'textfield',
    ];
    $form['edismax_wrapper']['tie'] = [
      '#title' => 'tie',
      '#description' => $this->t('Score tie-breaker. Try 0.1.'),
      '#type' => 'textfield',
    ];
    $form['edismax_wrapper']['bq'] = [
      '#title' => 'bq',
      '#description' => $this->t('Boost query.'),
      '#type' => 'textfield',
    ];
    $form['edismax_wrapper']['bf'] = [
      '#title' => 'bf',
      '#description' => $this->t('Boost function (added).'),
      '#type' => 'textfield',
    ];
    $form['edismax_wrapper']['uf'] = [
      '#title' => 'uf',
      '#type' => 'textfield',
    ];
    $form['edismax_wrapper']['pf2'] = [
      '#title' => 'pf2',
      '#type' => 'textfield',
    ];
    $form['edismax_wrapper']['pf3'] = [
      '#title' => 'pf3',
      '#type' => 'textfield',
    ];
    $form['edismax_wrapper']['ps2'] = [
      '#title' => 'ps2',
      '#type' => 'textfield',
    ];
    $form['edismax_wrapper']['ps3'] = [
      '#title' => 'ps3',
      '#type' => 'textfield',
    ];
    $form['edismax_wrapper']['boost'] = [
      '#title' => 'boost',
      '#type' => 'textfield',
    ];
    $form['edismax_wrapper']['stopwords'] = [
      '#title' => 'stopwords',
      '#description' => $this->t('Remove stopwords from mandatory "matching" component'),
      '#type' => 'checkbox',
    ];
    $form['edismax_wrapper']['lowercaseOperators'] = [
      '#title' => 'lowercaseOperators',
      '#description' => $this->t('Enable lower-case "and" and "or" as operators'),
      '#type' => 'checkbox',
    ];

    // hl.
    $form['hl'] = [
      '#title' => 'hl',
      '#type' => 'checkbox',
    ];
    $form['hl_wrapper'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="hl"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['hl_wrapper']['hl.fl'] = [
      '#title' => 'hl.fl',
      '#description' => $this->t('Fields to highlight on.'),
      '#type' => 'textfield',
    ];
    $form['hl_wrapper']['hl.simple.pre'] = [
      '#title' => 'hl.simple.pre',
      '#description' => $this->t('Score tie-breaker. Try 0.1.'),
      '#type' => 'textfield',
    ];
    $form['hl_wrapper']['hl.simple.post'] = [
      '#title' => 'hl.simple.post',
      '#description' => $this->t('Score tie-breaker. Try 0.1.'),
      '#type' => 'textfield',
    ];
    $form['hl_wrapper']['hl.requireFieldMatch'] = [
      '#title' => 'hl.requireFieldMatch',
      '#type' => 'checkbox',
    ];
    $form['hl_wrapper']['hl.usePhraseHighlighter'] = [
      '#title' => 'hl.usePhraseHighlighter',
      '#type' => 'checkbox',
    ];
    $form['hl_wrapper']['hl.highlightMultiTerm'] = [
      '#title' => 'hl.highlightMultiTerm',
      '#type' => 'checkbox',
    ];

    // Facet.
    $form['facet'] = [
      '#title' => 'facet',
      '#type' => 'checkbox',
    ];
    $form['facet_wrapper'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="facet"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['facet_wrapper']['facet.query'] = [
      '#title' => 'facet.query',
      '#type' => 'textarea',
    ];
    $form['facet_wrapper']['facet.field'] = [
      '#title' => 'facet.field',
      '#type' => 'textfield',
    ];
    $form['facet_wrapper']['facet.prefix'] = [
      '#title' => 'facet.prefix',
      '#type' => 'textfield',
    ];

    // Spatial.
    $form['spatial'] = [
      '#title' => 'spatial',
      '#type' => 'checkbox',
    ];
    $form['spatial_wrapper'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="spatial"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['spatial_wrapper']['pt'] = [
      '#title' => 'pt',
      '#type' => 'textfield',
    ];
    $form['spatial_wrapper']['sfield'] = [
      '#title' => 'sfield',
      '#type' => 'textfield',
    ];
    $form['spatial_wrapper']['d'] = [
      '#title' => 'd',
      '#type' => 'textfield',
    ];

    // Spellcheck.
    $form['spellcheck'] = [
      '#title' => 'spellcheck',
      '#type' => 'checkbox',
    ];
    $form['spellcheck_wrapper'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="spellcheck"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['spellcheck_wrapper']['spellcheck.build'] = [
      '#title' => 'spellcheck.build',
      '#type' => 'checkbox',
    ];
    $form['spellcheck_wrapper']['spellcheck.reload'] = [
      '#title' => 'spellcheck.reload',
      '#type' => 'checkbox',
    ];
    $form['spellcheck_wrapper']['spellcheck.q'] = [
      '#title' => 'spellcheck.q',
      '#type' => 'textfield',
    ];
    $form['spellcheck_wrapper']['spellcheck.dictionary'] = [
      '#title' => 'spellcheck.dictionary',
      '#type' => 'textfield',
    ];
    $form['spellcheck_wrapper']['spellcheck.count'] = [
      '#title' => 'spellcheck.count',
      '#type' => 'textfield',
    ];
    $form['spellcheck_wrapper']['spellcheck.onlyMorePopular'] = [
      '#title' => 'spellcheck.onlyMorePopular',
      '#type' => 'checkbox',
    ];
    $form['spellcheck_wrapper']['spellcheck.extendedResults'] = [
      '#title' => 'spellcheck.extendedResults',
      '#type' => 'checkbox',
    ];
    $form['spellcheck_wrapper']['spellcheck.collate'] = [
      '#title' => 'spellcheck.collate',
      '#type' => 'checkbox',
    ];
    $form['spellcheck_wrapper']['spellcheck.maxCollations'] = [
      '#title' => 'spellcheck.maxCollations',
      '#type' => 'textfield',
    ];
    $form['spellcheck_wrapper']['spellcheck.maxCollationTries'] = [
      '#title' => 'spellcheck.maxCollationTries',
      '#type' => 'textfield',
    ];
    $form['spellcheck_wrapper']['spellcheck.accuracy'] = [
      '#title' => 'spellcheck.accuracy',
      '#type' => 'textfield',
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Execute Query'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $plugin_id = $this->configFactory->get('solr_qb.settings')->get('active_plugin');
    $plugin = $this->pluginManager->createInstance($plugin_id);

    $result = json_decode($plugin->query($form_state->getValues())->getContents());

    dsm($result);
  }

}
