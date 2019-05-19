<?php

namespace Drupal\views_vcards\Plugin\views\display;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\views\Plugin\views\display\PathPluginBase;
use Drupal\views\Plugin\views\display\ResponseDisplayPluginInterface;

/**
 * The plugin that handles one or more vCards.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "views_vcard",
 *   title = @Translation("vCard"),
 *   help = @Translation("Display the results as a vCard."),
 *   uses_route = TRUE,
 *   admin = @Translation("vCard"),
 *   returns_response = TRUE
 * )
 */
class ViewsVcardsDisplayPluginVcard extends PathPluginBase implements ResponseDisplayPluginInterface {

  /**
   * {@inheritdoc}
   */
  protected $usesAJAX = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesPager = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesMore = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesAreas = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'views_vcard';
  }

  /**
   * {@inheritdoc}
   */
  public static function buildResponse($view_id, $display_id, array $args = []) {
    $build = static::buildBasicRenderable($view_id, $display_id, $args);

    // Set up an empty response, so for example RSS can set the proper
    // Content-Type header.
    $response = new CacheableResponse('', 200);
    $build['#response'] = $response;

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $output = (string) $renderer->renderRoot($build);

    if (empty($output)) {
      throw new NotFoundHttpException();
    }

    $response->setContent($output);
    $cache_metadata = CacheableMetadata::createFromRenderArray($build);
    $response->addCacheableDependency($cache_metadata);

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    parent::execute();

    return $this->view->render();
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    $output = $this->view->render();

    if (!empty($this->view->live_preview)) {
      $output = [
        '#prefix' => '<pre>',
        '#plain_text' => \Drupal::service('renderer')->renderRoot($output),
        '#suffix' => '</pre>',
      ];
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = $this->view->style_plugin->render($this->view->result);

    $this->applyDisplayCachablityMetadata($build);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultableSections($section = NULL) {
    $sections = parent::defaultableSections($section);

    if (in_array($section, ['style', 'row'])) {
      return FALSE;
    }

    return $sections;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['displays'] = ['default' => []];

    // Overrides for standard stuff.
    $options['style']['contains']['type']['default'] = 'views_vcard_style';
    $options['row']['contains']['type']['default'] = 'views_vcard_fields';
    $options['defaults']['default']['style'] = FALSE;
    $options['defaults']['default']['row'] = FALSE;

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function newDisplay() {
    parent::newDisplay();

    // Set the default row style. Ideally this would be part of the option
    // definition, but in this case it's dependent on the view's base table,
    // which we don't know until init().
    if (empty($this->options['row']['type']) || $this->options['row']['type'] === 'views_vcard_fields') {
      $options = $this->getOption('row');
      $options['type'] = 'views_vcard_fields';
      $this->setOption('row', $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    // Since we're childing off the 'path' type, we'll still *call* our
    // category 'page' but let's override it so it says vCard settings.
    $categories['page'] = [
      'title' => t('vCard settings'),
      'column' => 'second',
      'build' => [
        '#weight' => -10,
      ],
    ];

    $displays = array_filter($this->getOption('displays'));
    if (count($displays) > 1) {
      $attach_to = $this->t('Multiple displays');
    }
    elseif (count($displays) == 1) {
      $display = array_shift($displays);
      $displays = $this->view->storage->get('display');
      if (!empty($displays[$display])) {
        $attach_to = $displays[$display]['display_title'];
      }
    }

    if (!isset($attach_to)) {
      $attach_to = $this->t('None');
    }

    $options['displays'] = [
      'category' => 'page',
      'title' => $this->t('Attach to'),
      'value' => $attach_to,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    switch ($form_state->get('section')) {
      case 'displays':
        $form['#title'] .= $this->t('Attach to');
        $displays = [];
        foreach ($this->view->storage->get('display') as $display_id => $display) {
          // @todo The display plugin should have display_title and id as well.
          if ($this->view->displayHandlers->has($display_id) && $this->view->displayHandlers->get($display_id)->acceptAttachments()) {
            $displays[$display_id] = $display['display_title'];
          }
        }
        $form['displays'] = [
          '#title' => $this->t('Displays'),
          '#type' => 'checkboxes',
          '#description' => $this->t('The vCard icon will be available only to the selected displays.'),
          '#options' => array_map('\Drupal\Component\Utility\Html::escape', $displays),
          '#default_value' => $this->getOption('displays'),
        ];
        break;
      case 'path':
        $form['path']['#description'] = $this->t('This view will be displayed by visiting this path on your site. It is recommended that the path be something like "path/%/%/vcard" or "path/%/%/vcard.vcf", putting one % in the path for each contextual filter you have defined in the view.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);
    $section = $form_state->get('section');
    switch ($section) {
      case 'displays':
        $this->setOption($section, $form_state->getValue($section));
        break;
    }
  }

  /**
   * Attach to another view.
   */
  public function attachTo(ViewExecutable $clone, $display_id, array &$build) {
    $displays = $this->getOption('displays');
    if (empty($displays[$display_id])) {
      return;
    }

    // Defer to the vCard style; it may put in meta information, and/or
    // attach a vCard icon.
    $clone->setArguments($this->view->args);
    $clone->setDisplay($this->display['id']);
    $clone->buildTitle();
    if ($plugin = $clone->display_handler->getPlugin('style')) {
      $plugin->attachTo($build, $display_id, $clone->getUrl(), $clone->getTitle());
      foreach ($clone->feedIcons as $feed_icon) {
        $this->view->feedIcons[] = $feed_icon;
      }
    }

    // Clean up.
    $clone->destroy();
    unset($clone);
  }

  public function usesLinkDisplay() {
    return TRUE;
  }

}
