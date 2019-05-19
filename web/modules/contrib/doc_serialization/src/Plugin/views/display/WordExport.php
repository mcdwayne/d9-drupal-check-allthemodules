<?php

namespace Drupal\doc_serialization\Plugin\views\display;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rest\Plugin\views\display\RestExport;

/**
 * Provides an Word export display plugin.
 *
 * This overrides the REST Export display to make labeling clearer on the admin
 * UI, and add specific Excel-related functionality.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "word_export",
 *   title = @Translation("Word export"),
 *   help = @Translation("Export the view results to a Word file."),
 *   uses_route = TRUE,
 *   admin = @Translation("Word export"),
 *   returns_response = TRUE
 * )
 */
class WordExport extends RestExport {

  /**
   * Overrides the content type of the data response, if needed.
   *
   * @var string
   */
  protected $contentType = 'docx';

  /**
   * {@inheritdoc}
   *
   * @throws \UnexpectedValueException
   * @throws \LogicException
   * @throws \InvalidArgumentException
   */
  public static function buildResponse($view_id, $display_id, array $args = []) {
    // Do not call the parent method, as it makes the response harder to alter.
    // @see https://www.drupal.org/node/2779807
    $build = static::buildBasicRenderable($view_id, $display_id, $args);

    // Setup an empty response, so for example, the Content-Disposition header
    // can be set.
    $response = new CacheableResponse('', 200);
    $build['#response'] = $response;

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $output = (string) $renderer->renderRoot($build);

    $response->setContent($output);
    $cache_metadata = CacheableMetadata::createFromRenderArray($build);
    $response->addCacheableDependency($cache_metadata);

    $response->headers->set('Content-type', $build['#content_type']);

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    // Add the content disposition header if a custom filename has been used.
    if (($response = $this->view->getResponse()) && $this->getOption('filename')) {
      $response->headers->set('Content-Disposition', 'attachment; filename="' . $this->generateFilename($this->getOption('filename')) . '"');
    }

    return parent::render();
  }

  /**
   * Given a filename and a view, generate a filename.
   *
   * @param string $filename_pattern
   *   The filename, which may contain replacement tokens.
   *
   * @return string
   *   The filename with any tokens replaced.
   */
  protected function generateFilename($filename_pattern) {
    return $this->globalTokenReplace($filename_pattern);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['filename'] = ['default' => []];

    // Set the default style plugin, and default to fields.
    $options['style']['contains']['type']['default'] = 'word_export';
    $options['row']['contains']['type']['default'] = 'data_field';

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    // Add filename to the summary if set.
    if ($this->getOption('filename')) {
      $options['path']['value'] .= $this->t(': (@filename)', ['@filename' => $this->getOption('filename')]);
    }

    // Display the selected format from the style plugin if available.
    $style_options = $this->getOption('style')['options'];
    if (!empty($style_options['formats'])) {
      $options['style']['value'] .= $this->t(': (@export_format)', ['@export_format' => reset($style_options['formats'])]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    switch ($form_state->get('section')) {
      case 'style':
        // Remove the 'serializer', 'excel_export' and 'data_export'
        // (if available) options to avoid confusion.
        unset($form['style']['type']['#options']['serializer'], $form['style']['type']['#options']['data_export'], $form['style']['type']['#options']['excel_export']);
        break;

      case 'path':
        $form['filename'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Filename'),
          '#default_value' => $this->options['filename'],
          '#description' => $this->t('The filename that will be suggested to the browser for downloading purposes. You may include replacement patterns from the list below.'),
        ];
        // Support tokens.
        $this->globalTokenForm($form, $form_state);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);

    $section = $form_state->get('section');
    switch ($section) {
      case 'path':
        $this->setOption('filename', $form_state->getValue('filename'));
        break;
    }
  }

  /**
   * {@inheritdoc}
   *
   * The DisplayPluginBase preview method assumes we will be returning a render
   * array. The data plugin will already return the serialized string.
   */
  public function preview() {
    return [
      '#markup' => '<p>' . $this->t('This display does not use preview') . '</p>',
    ];
  }

}
