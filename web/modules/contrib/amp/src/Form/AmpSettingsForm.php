<?php

namespace Drupal\amp\Form;

use Drupal\amp\EntityTypeInfo;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the configuration export form.
 */
class AmpSettingsForm extends ConfigFormBase {

  /**
   * The theme handler service.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The array of valid theme options.
   *
   * @array $themeOptions
   */
  private $themeOptions;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $tagInvalidate;

  /**
   * Information about AMP-enabled content types.
   *
   * @var \Drupal\amp\EntityTypeInfo
   */
  protected $entityTypeInfo;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'amp_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['amp.settings', 'amp.theme'];
  }

  /*
   * Helper function to get available theme options.
   *
   * @return array
   *   Array of valid themes.
   */
  private function getThemeOptions() {
    // Get all available themes.
    $themes = $this->themeHandler->rebuildThemeData();
    uasort($themes, 'system_sort_modules_by_info_name');
    $theme_options = [];

    foreach ($themes as $theme) {
      if (!empty($theme->info['hidden'])) {
        continue;
      }
      elseif (!empty($theme->status)) {
        $theme_options[$theme->getName()] = $theme->info['name'];
      }
    }

    return $theme_options;
  }

  /**
   * Constructs a AmpSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $tag_invalidate
   *   The cache tags invalidator.
   * @param \Drupal\amp\EntityTypeInfo $entity_type_info
   *   Information about AMP-enabled content types.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ThemeHandlerInterface $theme_handler, CacheTagsInvalidatorInterface $tag_invalidate, EntityTypeInfo $entity_type_info) {
    parent::__construct($config_factory);

    $this->themeHandler = $theme_handler;
    $this->themeOptions = $this->getThemeOptions();
    $this->tagInvalidate = $tag_invalidate;
    $this->entityTypeInfo = $entity_type_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('theme_handler'),
      $container->get('cache_tags.invalidator'),
      $container->get('amp.entity_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $amp_config = $this->config('amp.settings');
    $module_handler = \Drupal::moduleHandler();

    $page_prefix = $this->t('<p>This page contains configuration for AMP ' .
      'pages. Review <a href=":doclink1">Drupal Documentation</a> for the ' .
      '<a href=":doclink2">Drupal AMP module</a> and the <a href=":doclink3">' .
      'AMP Project Page</a> for more information.</p>', [
        ':doclink1' => 'https://www.drupal.org/docs/8/modules/accelerated-mobile-pages-amp/amp-version-83',
        ':doclink2' => 'https://www.drupal.org/project/amp',
        ':doclink3' => 'https://www.ampproject.org',
      ]);
    $page_prefix .= '<ul>';
    if (!$module_handler->moduleExists('schema_metatag')) {
      $page_prefix .= '<li>';
      $page_prefix .= $this->t('Valid AMP requires Schema.org markup, which ' .
        'can be provided by the <a href=":doclink2">Schema.org Metatag ' .
        'module</a>.', [
          ':doclink2' => 'https://www.drupal.org/project/schema_metatag'
        ]);
      $page_prefix .= '</li>';
    }
    if ($module_handler->moduleExists('toolbar') && !$module_handler->moduleExists('amp_toolbar')) {
      $page_prefix .= '<li>';
      $page_prefix .=  $this->t('If you have the Toolbar module enabled, ' .
        'enable the <a href=":doclink3">AMP Toolbar</a> module.', [
          ':doclink3' => '/admin/modules'
        ]);
      $page_prefix .= '</li>';
    }
    if ($module_handler->moduleExists('rdf') && !$module_handler->moduleExists('amp_rdf')) {
      $page_prefix .= '<li>';
      $page_prefix .=  $this->t('If you have the RDF module enabled, enable ' .
        'the <a href=":doclink4">AMP RDF</a> module.', [
          ':doclink4' => '/admin/modules'
        ]);
      $page_prefix .= '</li>';
    }
    $page_prefix .= '</ul>';

    $amptheme_config = $this->config('amp.theme');
    $description = $this->t('Choose a theme to use for AMP pages. Themes must ' .
      'be installed (but not necessarily set as the default theme) before ' .
      'they will appear in this list and be usable by AMP. You can choose ' .
      'between AMP Base, an installed subtheme of AMP Base, such as the ' .
      'ExAMPle Subtheme, or any theme that complies with AMP rules. See ' .
      '<a href=":link">AMPTheme</a> for examples and pre-configured themes.', [
        ':link' => 'https://www.drupal.org/project/amptheme'
      ]);

    $form['theme'] = [
      '#type' => 'details',
      '#title' => $this->t('Theme'),
      '#prefix' => $page_prefix,
      '#open' => TRUE,
    ];

    $form['theme']['amptheme'] = [
      '#type' => 'select',
      '#options' => $this->themeOptions,
      '#required' => TRUE,
      '#title' => $this->t('AMP theme'),
      '#description' => $description,
      '#default_value' => $amptheme_config->get('amptheme'),
    ];

    $prefix = $this->t('<p>Select the content types you want to enable for ' .
      'AMP in the list below. Enable them by turning on the AMP view mode ' .
      'for that type. Once enabled, links are provided so you can configure ' .
      'the fields and formatters for the AMP display of each one. For ' .
      'instance, replace the normal text formatter for the body field with ' .
      'the AMP text formatter, and replace the normal image formatter with ' .
      'the AMP image formatter on the AMP view mode.</p>', [
        ':doclink1' => 'https://www.ampproject.org',
      ]);

    $form['types'] = [
      '#type' => 'details',
      '#title' => $this->t('Content types'),
      '#open' => TRUE,
      '#description' => $prefix,
    ];

    if ($module_handler->moduleExists('field_ui')) {
      $form['types']['amp_content_amp_status'] = [
        '#title' => $this->t('AMP Status by Content Type'),
        '#theme' => 'table',
        '#header' => [t('Content type'), t('Enabled'), t('Configure'), t('Enable/Disable')],
        '#rows' => $this->entityTypeInfo->getFormattedAmpEnabledTypes(),
      ];
    }
    else {
      $form['amp_content_amp_status'] = [
        '#type' => 'item',
        '#title' => $this->t('AMP Status by Content Type'),
        '#markup' => $this->t('(In order to enable and disable AMP content ' .
          'types in the UI, the Field UI module must be enabled.)'),
      ];
    }

    $page_suffix = $this->t('This code uses the ' .
      '<a href="https://github.com/Lullabot/amp-library">AMP Library</a>. '.
      'This library will be installed by Composer if the AMP module is ' .
      'installed by Composer as follows:</p><p><code>composer require ' .
      'drupal/amp</code></p><p>Update the module using this:</p><p><code>' .
      'composer update drupal/amp --with-dependencies</code></p>');
    $page_suffix .= $this->t('Test that the AMP library is <a href=":url">' .
      'configured properly</a>. Look for the words <strong>The Library is ' .
      'working.</strong> at the top of the page. You will see that the ' .
      'library detected markup that fails AMP standards. If the library is ' .
      'not detected, retry adding the AMP module using Composer, as indicated ' .
      'above.', [
        ':url' => Url::fromRoute('amp.test_library_hello')->toString()
      ]);
    $page_suffix .= '</p><p>';
    $page_suffix .= $this->t('If you want to see AMP debugging information ' .
      'for any node add "&debug#development=1" at end of the AMP node url, ' .
      'e.g. <em>node/12345?amp&debug#development=1</em>. This will provide ' .
      'Drupal messages on the page and AMP messages in the javascript ' .
      'console. Check the AMP Project documentation for more information.</p>');
    $page_suffix .= '</p>';

    $form['library'] = [
      '#type' => 'details',
      '#title' => $this->t('AMP Library'),
      '#description' => $page_suffix,
      '#open' => TRUE,
    ];

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced/Experimental Options'),
      '#open' => TRUE,
    ];
    $form['advanced']['process_full_html'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Advanced option (Not recommended)' .
        '</strong>: Run the page body through the AMP library'),
      '#default_value' => $amp_config->get('process_full_html'),
      '#description' => $this->t('The AMP PHP library will fix some AMP HTML ' .
        'non-compliance issues by removing disallowed attributes, tags ' .
        'and property values. This is an option for fixing stubborn ' .
        'AMP-unfriendly HTML. This feature can be problematic, the library ' .
        'is often over-aggressive and removes some code you may still want, '.
        'so test carefully.')
    ];

    $form['advanced']['amp_everywhere'] = [
      '#type' => 'checkbox',
      '#default_value' => $amp_config->get('amp_everywhere'),
      '#title' => $this->t('<strong>Experimental option</strong>: Generate all ' .
        'pages as AMP pages'),
      '#description' => $this->t('This is a new, experimental, option to '.
        'display your whole site as AMP pages. This assumes you understand ' .
        'what is required to comply with AMP rules and are using an AMP-' .
        'friendly theme as your primary theme, and using AMP formatters and ' .
        'blocks in your primary theme. Leave unset if you want AMP pages ' .
        'displayed as an alternative to your normal pages, on a different ' .
        'path, the traditional way of deploying AMP. Check the box if your ' .
        'normal pages <em>ARE</em> AMP pages, and serve as both the canonical ' .
        'page and the AMP page. If you are not sure what what this means, ' .
        'leave it unchecked.'),
    ];

    $form['show_extra_save_buttons'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add extra save options to node edit pages'),
      '#default_value' => $amp_config->get('show_extra_save_buttons'),
      '#description' => $this->t('Adds convenient buttons for viewing the AMP version of a node after saving it (if the content type has AMP enabled).'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // AMP theme settings.
    $amptheme = $form_state->getValue('amptheme');
    $amptheme_config = $this->config('amp.theme');
    $amptheme_config->setData(['amptheme' => $amptheme]);
    $amptheme_config->save();

    $amp_config = $this->config('amp.settings');
    $amp_config->set('process_full_html', $form_state->getValue('process_full_html'))->save();

    $amp_config->set('amp_everywhere', $form_state->getValue('amp_everywhere'))->save();

    $amp_config->set('show_extra_save_buttons', $form_state->getValue('show_extra_save_buttons'))->save();

    parent::submitForm($form, $form_state);
  }
}
