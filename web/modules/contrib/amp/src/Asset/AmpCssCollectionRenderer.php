<?php

namespace Drupal\amp\Asset;

use Drupal\Core\Asset\CssCollectionRenderer;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\amp\Service\AMPService;
use Drupal\Core\State\StateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\RendererInterface;
use \Drupal\Core\Asset\CssOptimizer;

/**
 * Renders CSS assets.
 *
 * This class retrieves all local css and renders it inline in the head of the
 * page. Neither style links nor @import are allowed in AMP, except for a few
 * whitelisted font providers.
 */
class AmpCssCollectionRenderer extends CssCollectionRenderer {

  /**
   * Whitelist of allowed external style links.
   *
   * @see https://www.ampproject.org/docs/design/responsive/custom_fonts
   */
  protected $link_domain_whitelist = [
    'cloud.typography.com',
    'fast.fonts.net',
    'fonts.googleapis.com',
    'use.typekit.net',
    'maxcdn.bootstrapcdn.com',
  ];

  /**
   * The inner service that we are decorating.
   *
   * @var \Drupal\Core\Asset\CssCollectionRenderer
   */
  protected $cssCollectionRenderer;

  /**
   * @var \Drupal\amp\Service\AMPService
   */
  protected $ampService;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * @var \Drupal\Core\Asset\CssOptimizer
   */
  protected $optimizer;

  /**
   * Constructs a CssCollectionRenderer.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key/value store.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Asset\CssOptimizer
   *   The CSS optimizer.
   */
  public function __construct(
  CssCollectionRenderer $cssCollectionRenderer,
  StateInterface $state,
  AmpService $ampService,
  RendererInterface $renderer,
  CssOptimizer $css_optimizer) {
    $this->cssCollectionRenderer = $cssCollectionRenderer;
    $this->state = $state;
    $this->ampService = $ampService;
    $this->renderer = $renderer;
    $this->optimizer = $css_optimizer;
    parent::__construct($state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(array $css_assets) {
    // Retrieve the normal css render array.
    $elements = parent::render($css_assets);
    // Intervene only if this is an AMP page.
    if (!$this->ampService->isAmpRoute()) {
      return $elements;
    }

    // For tracking the size and contents of the inlined css:
    $size = 0;
    $files = [];

    // Rewrite relative css asset paths in css since they won't work correctly
    // when css is rendered inline instead of as attachments.
    // 1) Identify the path to where this CSS file originated from. This, when
    //    passed through rewriteFileURI() will iteratively remove ../ values
    //    within the css to rewrite the url relative to the web root.
    // 2) Prepend that path with the absolute theme path. This will ensure
    //    we end up with an absolute path to the asset instead of to the top
    //    level of the site.
    $config = \Drupal::config('system.theme');
    $theme = $config->get('default');
    $theme_path = drupal_get_path('theme', $theme) . '/';
    $this->optimizer->rewriteFileURIBasePath = $theme_path . '/'. base_path() . dirname($theme_path) . '/';

    foreach ($elements as $key => $element) {
      // This is a files that contains @import values for the css files.
      if ($element['#tag'] == 'style' && array_key_exists('#value', $element)) {

        // Now split all the @imports into individual items so we can count,
        // load, and concatenate them.
        $urls = preg_match_all('/@import url\("(.+)\?/', $element['#value'], $matches);
        $all_css = [];
        foreach ($matches[1] as $url) {
          // Skip empty and missing files.
          if ($css = @file_get_contents(DRUPAL_ROOT . $url)) {
            $css = $this->minify($css);
            $css = $this->strip($css);
            $size += strlen($css);
            $all_css[] = $css;
            $files[] = [$this->format(strlen($css)), $url];
          }
        }
        // Implode, wrap in @media, and minify results.
        $value = implode("", $all_css);
        $value = '@media ' . $element['#attributes']['media'] . " {\n" . $value . "\n}\n";
        $value = $this->minify($value);
        $value = $this->strip($value);

        $element['#value'] = $value;
        $elements[$key] = $element;
        $elements[$key]['#merged'] = TRUE;
      }
      // This is a file that contains links to css files.
      elseif ($element['#tag'] == 'link' && array_key_exists('href', $element['#attributes'])) {
        $url = $element['#attributes']['href'];
        $provider = parse_url($url, PHP_URL_HOST);
        if (!empty($provider)) {
          // External files rendered as links only if they are on the whitelist.
          if (!in_array($provider, $this->link_domain_whitelist)) {
            unset($elements[$key]);
          }
        }
        else {
          // Strip any querystring off the url.
          list($url, $query) = explode('?', $url);
          $css = file_get_contents(DRUPAL_ROOT . $url);
          $css = $this->minify($css);
          $css = $this->strip($css);
          $size += strlen($css);
          $all_css[] = $css;
          $files[] = [$this->format(strlen($css)), $url];
          $element['#value'] = $css;
          $elements[$key] = $element;
          $elements[$key]['#merged'] = TRUE;
        }
      }
    }
    // Merge the inline results into a single style element with an
    // "amp-custom" attribute, using the amp_custom_style #type.
    $merged = '';
    $replacement_key = NULL;
    foreach ($elements as $key => $element) {
      if (isset($element['#merged'])) {
        $merged .= $element['#value'];
        unset ($elements[$key]);
        // The first key found will become the new element's key.
        if (empty($replacement_key)) {
          $replacement_key = $key;
        }
      }
    }

    // 3) Prefix all relative paths within this CSS file with the theme path.
    $merged = preg_replace_callback('/url\\([\'"]?(?![a-z]+:|\\/+)([^\'")]+)[\'"]?\\)/i', array(
      $this->optimizer,
      'rewriteFileURI',
    ), $merged);

    $elements[$replacement_key] = [
      '#tag' => 'style',
      '#type' => 'amp_custom_style',
      '#value' => $merged,
    ];

    // Display info about inline css if #development=1 is appended to url.
    if ($this->ampService->isDevPage()) {
      $title = 'CSS Filesize';
      $difference = ($size - 50000);
      $over = $difference > 0 ? t('so your css is :difference too big', [':difference' => $this->format(abs($difference))]) : '';
      $under = $difference <= 0 ? t('so you have :difference to spare', [':difference' => $this->format(abs($difference))]) : '';
      $output = t('The size of the css on this page is :size. The AMP limit is :limit, :overunder. The included css files and their sizes are listed for ease in finding large files to optimize. For the best information about individual file sizes, visit this page while optimization is turned off.', [':size' => $this->format($size), ':limit' => $this->format(50000), ':overunder' => $over . $under]);

      $build = [
        '#type' => 'table',
        '#header' => ['Size', 'File'],
        '#rows' => $files,
      ];
      $table = $this->renderer->renderRoot($build);

      if ($difference > 0) {
        $this->ampService->devMessage($title, 'addError');
        $this->ampService->devMessage($output, 'addError');
        $this->ampService->devMessage($table, 'addError');
      }
      else {
        $this->ampService->devMessage($title);
        $this->ampService->devMessage($output);
        $this->ampService->devMessage($table);
      }
    }
    return $elements;
  }

  /**
   * Minify and optimize css.
   *
   * Some code copied from color_scheme_form_submit() which rewrites css to
   * replace colors in css files.
   *
   * @param string $value
   *   The css to minify.
   *
   * @return string
   *   The minified css.
   */
  public function minify($style) {
    // Remove comments
    $style = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $style);
    // Remove space after colons
    $style = str_replace(': ', ':', $style);
    // Remove whitespace
    $style = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $style);
    return $style;
  }

  /**
   * Strip css which won't validate as AMPHTML.
   *
   * @param string $value
   *   The css to strip.
   *
   * @return string
   *   The stripped css.
   */
  public function strip($value) {
    // Remove css that won't validate as AMPHTML.
    $invalid = [
      '!important',
    ];
    $value = str_replace($invalid, '', $value);
    return $value;
  }

  /**
   * Format values consistently.
   *
   * @param string $value
   *   The number to minify.
   *
   * @return string
   *   The formatted number.
   */
  public function format($value) {
    return number_format($value, 0);
  }
}
