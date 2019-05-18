<?php

/**
 * @file
 * Provides FilterProcessing.
 */

namespace Drupal\processing\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processing.js Filter.
 *
 * @Filter(
 *   id = "filter_processing",
 *   title = @Translation("Allow Processing code execution"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   description = @Translation("Javascript code within [processing][/processing] tags will be executed"),
 *   settings = {
 *     "blacklist" = "println print link status param",
 *     "render_mode" = "source"
 *   },
 *   weight = 0
 * )
 */
class FilterProcessing extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Initialize method.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition array.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer interface.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  static public function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['render_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Render Mode'),
      '#description' => $this->t('Choose the behavior of the input filter. If "Source first", source code will be displayed with a button to render. If "Render first", processing will render on load, and a button to display source code will be displayed. If "Render only", processing will always render and there will be no user interface to hide/view source.'),
      '#options' => [
        'source' => $this->t('Source first'),
        'render' => $this->t('Render first'),
        'only' => $this->t('Render only'),
      ],
      '#default_value' => $this->settings['render_mode'],
    ];
    $form['blacklist'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Restricted functions'),
      '#description' => $this->t('List all functions, separated by spaces, that are not allowed so that your site is secure. Lines which contain these functions will be commented out to prevent syntax errors.'),
      '#default_value' => $this->settings['blacklist'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare($text, $langcode) {
    $blacklist = [];
    $matches = [];

    // Get blacklist functions.
    if (!empty($this->settings['blacklist'])) {
      $blacklist = explode(' ', $this->settings['blacklist']);
    }

    // Find all processing code matches.
    preg_match_all('/\[processing\](?P<code>.*)\[\/processing\]/is', $text, $matches);

    if ($matches) {
      $text = $this->replaceRestricted($text, $matches['code'], $blacklist);
    }

    return $text;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $matches = [];
    $result = new FilterProcessResult($text);

    $count = preg_match_all('/\[processing\](?P<code>.*)\[\/processing\]/is', $text, $matches);

    if ($count) {
      foreach ($matches['code'] as $i => $code) {
        $args = [$code, $this->settings['render_mode']];
        $markup = $result->createPlaceholder('\Drupal\processing\Plugin\Filter\FilterProcessing::build', $args);
        $text = preg_replace('/\[processing\](?P<code>.*)\[\/processing\]/is', $markup, $text);
      }
      $result
        ->setProcessedText($text)
        ->addAttachments(['library' => ['processing/drupal.processing']])
        ->setCacheTags(['processing']);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if (!$long) {
      // @todo use Renderer service to link to processingjs.org
      return $this->t('Processing.js markup is enabled for this content.');
    }

    $render_mode = $this->settings['render_mode'] === 'only' ? $this->t('rendered sketches only') : $this->settings['render_mode'] .= ' ' . $this->t('first');
    $restricted = explode(' ', $this->settings['blacklist']);

    $items = [
      $this->t('Enclose your processing code within [processing][/processing] tags.'),
      $this->t('Your code is configured to display %mode.', ['%mode' => $render_mode]),
      [
        'data' => $this->t('The following functions are disabled, and wil not be rendered:'),
        'children' => $restricted,
      ],
    ];

    $list = [
      '#type' => 'item_list',
      '#items' => $items,
    ];

    return $this->renderer->render($list, FALSE);
  }

  /**
   * Comment out restricted JavaScript.
   *
   * @param string $text
   *   The text to replace/prepare.
   * @param array $scripts
   *   The processing code to prepare.
   * @param array $blacklist
   *   An array of functions to blacklist.
   *
   * @return string
   *   The modified string with placeholders.
   */
  protected function replaceRestricted($text, $scripts, $blacklist) {
    $i = 0;
    foreach ($scripts as $code) {
      // Make it easy to replace back the restricted code.
      $newcode = $code;
      $text = preg_replace("/\[processing\].*\[\/processing\]/is", "@processing-js-$i@", $text, 1);
      foreach ($blacklist as $func) {
        // Comment out entire line with restricted function in
        // case it would cause syntax errors.
        $newcode = preg_replace("/\b($func)\b(.*)/i", "/* Restricted. \\1\\2 */", $newcode);
      }
      // Put our new code back into the main text.
      $text = preg_replace("/\@processing-js-$i\@/", '[processing]' . $newcode . '[/processing]', $text, 1);
      $i++;
    }
    return $text;
  }

  /**
   * Create a render array for processing.js script.
   *
   * This may be called outside of the scope of the filter.
   *
   * @todo geshifilter support
   * @todo render outside of filter support
   *
   * @param string $script
   *   The JavaScript to add.
   * @param string $mode
   *   The render mode string.
   *
   * @return array
   *   A render array.
   */
  static public function build($script, $mode) {
    $id = uniqid('processing-');
    $canvas_classes = ['processing-js-canvas', 'processing__canvas'];
    if ($mode === 'source') {
      $canvas_classes[] = 'processing__canvas--hidden';
    }
    $render = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['processing-wrapper'],
      ],
      'canvas' => [
        '#theme' => 'processing_display',
        '#rendermode' => $mode,
        '#unique' => $id,
        '#code' => $script,
        '#attributes' => [
          'class' => $canvas_classes,
          'id' => $id,
        ],
        '#attached' => [
          'drupalSettings' => [
            'processing' => [
              'drupal.processing' => [
                'elementId' => $id,
                'renderMode' => $mode,
              ],
            ],
          ],
        ],
      ],
    ];
    return $render;
  }

}
