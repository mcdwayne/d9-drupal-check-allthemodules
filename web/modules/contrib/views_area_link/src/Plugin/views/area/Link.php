<?php

namespace Drupal\views_area_link\Plugin\views\area;

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\area\TokenizeAreaPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Views area Link handler.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("area_link")
 */
class Link extends TokenizeAreaPluginBase {

  use RedirectDestinationTrait;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The access manager service.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * @var \Symfony\Component\Routing\RequestContext
   */
  protected $context;

  /**
   * Constructs a new Entity instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager.
   * @param \Symfony\Component\Routing\RequestContext
   *   The request context.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager, AccessManagerInterface $access_manager, RequestContext $context) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $language_manager;
    $this->accessManager = $access_manager;
    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager'),
      $container->get('access_manager'),
      $container->get('router.request_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_text']          = ['default' => ''];
    $options['path']               = ['default' => ''];
    $options['output_as_action']   = ['default' => FALSE];
    $options['destination']        = ['default' => TRUE];
    $options['prefix']             = ['default' => ''];
    $options['suffix']             = ['default' => ''];
    $options['external']           = ['default' => FALSE];
    $options['replace_spaces']     = ['default' => FALSE];
    $options['path_case']          = ['default' => 'none'];
    $options['alt']                = ['default' => ''];
    $options['rel']                = ['default' => ''];
    $options['link_class']         = ['default' => ''];
    $options['target']             = ['default' => ''];
    $options['absolute']           = ['default' => FALSE];
    $options['rewrite_output']     = ['default' => ''];
    $options['access_denied_text'] = ['default' => ''];
    $options['language']           = ['default' => '**auto**'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#default_value' => $this->options['link_text'],
      '#description' => $this->t('The text to use for the link. May use tokens.'),
      '#required' => TRUE,
    ];
    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link path'),
      '#default_value' => $this->options['path'],
      '#description' => $this->t('The Drupal path, Drupal URI, or absolute URL for this link. Drupal URIs include the entity: and route: schemes. You may append a query string and fragment. You may use token replacements.'),
      '#maxlength' => 255,
    ];
    $form['output_as_action'] = [
      '#title' => $this->t('Output as action'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['output_as_action'],
      '#description' => $this->t('Outputs the link as an "action button", themed like the links in the "Primary admin actions" block.'),
    ];
    $form['destination'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include destination'),
      '#default_value' => $this->options['destination'],
      '#description' => $this->t('Include a "destination" parameter in the link to return the user to the original view upon completing the link action.'),
    ];
    $form['replace_spaces'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Replace spaces with dashes'),
      '#default_value' => $this->options['replace_spaces'],
    ];
    $form['external'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('External server URL'),
      '#default_value' => $this->options['external'],
      '#description' => $this->t("Links to an external server using a full URL: e.g. 'http://www.example.com' or 'www.example.com'."),
    ];
    $form['path_case'] = [
      '#type' => 'select',
      '#title' => $this->t('Transform the case'),
      '#description' => $this->t('When printing URL paths, how to transform the case of the filter value.'),
      '#options' => [
        'none' => $this->t('No transform'),
        'upper' => $this->t('Upper case'),
        'lower' => $this->t('Lower case'),
        'ucfirst' => $this->t('Capitalize first letter'),
        'ucwords' => $this->t('Capitalize each word'),
      ],
      '#default_value' => $this->options['path_case'],
    ];
    $form['link_class'] = [
      '#title' => $this->t('Link class'),
      '#type' => 'textfield',
      '#default_value' => $this->options['link_class'],
      '#description' => $this->t('The CSS class to apply to the link. May use tokens.'),
    ];
    $form['alt'] = [
      '#title' => $this->t('Title text'),
      '#type' => 'textfield',
      '#default_value' => $this->options['alt'],
      '#description' => $this->t('Text to place as "title" text which most browsers display as a tooltip when hovering over the link. May use tokens.'),
    ];
    $form['rel'] = [
      '#title' => $this->t('Rel Text'),
      '#type' => 'textfield',
      '#default_value' => $this->options['rel'],
      '#description' => $this->t('Include Rel attribute for use in lightbox2 or other javascript utility. May use tokens.'),
    ];
    $form['prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix'),
      '#default_value' => $this->options['prefix'],
      '#description' => $this->t('Any text to display before this link. You may include HTML and tokens.'),
    ];
    $form['suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Suffix'),
      '#default_value' => $this->options['suffix'],
      '#description' => $this->t('Any text to display after this link. You may include HTML and tokens.'),
    ];
    $form['target'] = [
      '#title' => $this->t('Target'),
      '#type' => 'textfield',
      '#default_value' => $this->options['target'],
      '#description' => $this->t("Target of the link, such as _blank, _parent or an iframe's name. This field is rarely used. May use tokens."),
    ];

    $form['advanced_opts'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced Options'),
      '#weight' => 50,
    ];
    $form['absolute'] = [
      '#fieldset' => 'advanced_opts',
      '#type' => 'checkbox',
      '#title' => $this->t('Use absolute path'),
      '#default_value' => $this->options['absolute'],
      '#description' => $this->t('Whether to force the output to be an absolute link (beginning with http:). Useful for links that will be displayed outside the site, such as in an RSS feed.'),
    ];
    $form['rewrite_output'] = [
      '#fieldset' => 'advanced_opts',
      '#type' => 'textarea',
      '#title' => $this->t('Rewrite output'),
      '#default_value' => $this->options['rewrite_output'],
      '#description' => $this->t('Use this to output whatever HTML you want with the link created usable via the token {{views_area_link}}. Global tokens available as well.'),
    ];
    $form['access_denied_text'] = [
      '#fieldset' => 'advanced_opts',
      '#type' => 'textarea',
      '#title' => $this->t('Content when access is denied'),
      '#default_value' => $this->options['access_denied_text'],
      '#description' => $this->t('Use this to output whatever you want to display when access to the link is denied to the user. Tokens allowed.'),
    ];
    $form['language'] = [
      '#fieldset' => 'advanced_opts',
      '#type' => 'radios',
      '#title' => $this->t('Language'),
      '#default_value' => $this->options['language'],
      '#options' => ['**auto**' => $this->t('Current language')] + $this->listLanguages(
        LanguageInterface::STATE_ALL | LanguageInterface::STATE_SITE_DEFAULT,
        [$this->options['language']]
      ),
    ];

    if (!$this->languageManager->isMultilingual()) {
      $form['language']['#description'] = $this->t('This will not take effect until you turn on multilingual capabilities');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $options = $form_state->getValue('options');

    // @todo validate like \Drupal\link\Plugin\Field\FieldWidget\LinkWidget

    $form_state->setValue('options', $options);
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    // Note: Method adapted from the renderAsLinkmethod from
    // Drupal\views\Plugin\views\field\FieldPluginBase.
    if ($empty && empty($this->options['empty'])) {
      return [];
    }

    $text = $this->options['base_info']['link_text'];
    $options = array(
      'absolute' => !empty($this->options['absolute']) ? TRUE : FALSE,
      'alias' => FALSE,
      'entity' => NULL,
      'entity_type' => NULL,
      'fragment' => NULL,
      'language' => NULL,
      'query' => [],
    );

    $path = $this->options['path'];
    if ($path != '<front>') {
      // Use strip_tags as there should never be HTML in the path.
      // However, we need to preserve special characters like " that were
      // removed by SafeMarkup::checkPlain().
      $path = Html::decodeEntities($this->tokenizeValue($path));

      // Tokens might contain <front>, so check for <front> again.
      if ($path != '<front>') {
        $path = strip_tags($path);
      }

      // Tokens might have resolved URL's, as is the case for tokens provided by
      // Link fields, so all internal paths will be prefixed by the base url
      // from the request context. For proper further handling reset this to
      // internal:/.
      $base_path = $this->context->getBaseUrl() . '/';
      if (strpos($path, $base_path) === 0) {
        $path = 'internal:/' . substr($path, strlen($base_path));
      }

      // If we have no $path and no $url_info['url'], we have nothing to work
      // with, so we just return the text.
      if (empty($path)) {
        return $text;
      }

      // If no scheme is provided in the $path, assign the default 'http://'.
      // This allows a url of 'www.example.com' to be converted to
      // 'http://www.example.com'.
      // Only do this when flag for external has been set, $path doesn't contain
      // a scheme and $path doesn't have a leading /.
      if ($this->options['external'] && !parse_url($path, PHP_URL_SCHEME) && strpos($path, '/') !== 0) {
        // There is no scheme, add the default 'http://' to the $path.
        $path = "http://" . $path;
      }
    }

    if (!parse_url($path, PHP_URL_SCHEME)) {
      $url = Url::fromUserInput('/' . ltrim($path, '/'));
    }
    else {
      $url = Url::fromUri($path);
    }

    $options = $url->getOptions() + $options;

    $path = $url->setOptions($options)->toUriString();

    if (!empty($this->options['path_case']) && $this->options['path_case'] != 'none' && !$url->isRouted()) {
      $path = str_replace($this->options['path'], $this->caseTransform($this->options['path'], $this->options['path_case']), $path);
    }

    if (!empty($url_info['replace_spaces'])) {
      $path = str_replace(' ', '-', $path);
    }

    // Parse the URL and move any query and fragment parameters out of the path.
    $url_parts = UrlHelper::parse($path);

    // Seriously malformed URLs may return FALSE or empty arrays.
    if (empty($url_parts)) {
      return $text;
    }

    // If the path is empty do not build a link around the given text and return
    // it as is.
    if (empty($url_parts['path']) && empty($url_parts['fragment']) && empty($url_parts['url'])) {
      return $text;
    }

    // If we get to here we have a path from the url parsing. So assign that to
    // $path now so we don't get query strings or fragments in the path.
    $path = $url_parts['path'];

    if (isset($url_parts['query'])) {
      // Remove query parameters that were assigned a query string replacement
      // token for which there is no value available.
      foreach ($url_parts['query'] as $param => $val) {
        if ($val == '%' . $param) {
          unset($url_parts['query'][$param]);
        }
        // Replace any empty query params from URL parsing with NULL. So the
        // query will get built correctly with only the param key.
        // @see \Drupal\Component\Utility\UrlHelper::buildQuery().
        if ($val === '') {
          $url_parts['query'][$param] = NULL;
        }
      }

      $options['query'] = $url_parts['query'];
    }

    if (isset($url_parts['fragment'])) {
      $path = strtr($path, ['#' . $url_parts['fragment'] => '']);
      // If the path is empty we want to have a fragment for the current site.
      if ($path == '') {
        $options['external'] = TRUE;
      }
      $options['fragment'] = $url_parts['fragment'];
    }

    $alt = $this->tokenizeValue($url_info['alt']);
    // Set the title attribute of the link only if it improves accessibility.
    if ($alt && $alt != $text) {
      $options['attributes']['title'] = Html::decodeEntities($alt);
    }

    $class = $this->tokenizeValue($url_info['link_class']);
    if ($class) {
      $options['attributes']['class'] = [$class];
    }

    if (!empty($this->options['rel']) && $rel = $this->tokenizeValue($this->options['rel'])) {
      $options['attributes']['rel'] = $rel;
    }

    $target = trim($this->tokenizeValue($this->options['target']));
    if (!empty($target)) {
      $options['attributes']['target'] = $target;
    }

    if (!empty($this->options['destination'])) {
      $options['query'] += \Drupal::destination()->getAsArray();
    }

    if ($this->languageManager->isMultilingual() && $this->options['language'] !== '**auto**') {
      $options['language'] = $this->languageManager->getLanguage($this->options['language']);
    }
    return $this->renderUrl(Url::fromUri($path, $options));
  }

  /**
   * Takes an input \Drupal\Core\Url object and outputs it as needed.
   *
   * @param \Drupal\Core\Url $url
   *   Standard Drupal URL object.
   *
   * @return array
   *   Render array containing the content of this area.
   */
  protected function renderUrl(Url $url) {
    $options = $url->getOptions();



    $url->setOptions($options);

    if ($this->checkUrlAccess($url) == FALSE) {
      return [
        '#markup' => $this->sanitizeValue($this->tokenizeValue($this->options['access_denied_text'])),
      ];
    }

    $link_text = $this->tokenizeValue($this->options['link_text']);

    $link = [
      '#type' => 'link',
      '#url' => $url,
      '#title' => strip_tags(Html::decodeEntities($link_text)),
    ];

    if (($prefix = $this->options['prefix']) !== '') {
      $link['#prefix'] = $this->tokenizeValue($prefix);
    }
    if (($suffix = $this->options['suffix']) !== '') {
      $link['#suffix'] = $this->tokenizeValue($suffix);
    }

    if ($this->options['output_as_action']) {
      $link = $this->outputAsActionLink($link);
    }

    if ($this->options['rewrite_output'] !== '') {
      $rewritten_output = $this->viewsTokenReplace(
        $this->options['rewrite_output'],
        ['views_area_link' => $link]
      );
      return [
        '#markup' => $this->sanitizeValue($this->tokenizeValue($rewritten_output), 'xss_admin'),
      ];
    }

    return $link;
  }

  /**
   * Checks access to the link route.
   *
   * @param \Drupal\Core\Url $url
   *   Standard Drupal URL object.
   *
   * @return bool
   *   Whether the current user has access to the URL.
   */
  protected function checkUrlAccess(Url $url) {
    if ($url->isRouted() == FALSE) {
      return TRUE;
    }
    return $this->accessManager->checkNamedRoute($url->getRouteName(), $url->getRouteParameters());
  }

  /**
   * Takes the generated link ant transforms it into a 'local action' link.
   *
   * @param array $link
   *   The link to transform.
   *
   * @return array
   *   The render array for the generated action link.
   */
  protected function outputAsActionLink(array $link) {
    $action_link = [
      '#theme' => 'menu_local_action',
      '#link' => [
        'url' => $link['#url'],
        'title' => $link['#title'],
      ],
    ];

    $prefix = isset($link['prefix']) ? $link['prefix'] : '';
    $suffix = isset($link['suffix']) ? $link['suffix'] : '';
    $action_link['#prefix'] = $prefix . '<ul class="action-links">';
    $action_link['#suffix'] = '</ul>' . $suffix;

    return $action_link;
  }

}
