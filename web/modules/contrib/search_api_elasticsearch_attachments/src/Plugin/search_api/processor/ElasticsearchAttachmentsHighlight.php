<?php

namespace Drupal\search_api_elasticsearch_attachments\Plugin\search_api\processor;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Query\ResultSetInterface;

/**
 * Adds a highlighted excerpt to results and highlights returned fields.
 *
 * This processor won't run for queries with the "basic" processing level set.
 *
 * @SearchApiProcessor(
 *   id = "elasticsearch_attachments_highlight",
 *   label = @Translation("Elasticsearch Attachment Highlight"),
 *   description = @Translation("Adds a highlighted excerpt to Elasticsearch Attachment results and highlights returned fields."),
 *   stages = {
 *     "pre_index_save" = 0,
 *     "postprocess_query" = 0,
 *   }
 * )
 */
class ElasticsearchAttachmentsHighlight extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'prefix' => '<strong>',
      'suffix' => '</strong>',
      'excerpt' => TRUE,
      'highlight' => 'yes',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['highlight'] = [
      '#type' => 'select',
      '#title' => $this->t('Highlight returned field data'),
      '#description' => $this->t('Select whether returned fields should be highlighted.'),
      '#options' => [
        'yes' => $this->t('Yes'),
        'no' => $this->t('No'),
      ],
      '#default_value' => $this->configuration['highlight'],
    ];

    $form['excerpt'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create excerpt'),
      '#description' => $this->t('When enabled, an excerpt will be created for searches with keywords, containing all occurrences of keywords in a fulltext field.'),
      '#default_value' => $this->configuration['excerpt'],
    ];

    $form['prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Highlighting prefix'),
      '#description' => $this->t('Text/HTML that will be prepended to all occurrences of search keywords in highlighted text'),
      '#default_value' => $this->configuration['prefix'],
    ];

    $form['suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Highlighting suffix'),
      '#description' => $this->t('Text/HTML that will be appended to all occurrences of search keywords in highlighted text'),
      '#default_value' => $this->configuration['suffix'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function postprocessSearchResults(ResultSetInterface $results) {
    if (!$results->hasExtraData('elasticsearch_response')) {
      return;
    }

    // Get the results from ES.
    $elasticsearchResponse = $results->getExtraData('elasticsearch_response');

    // If excerpt is enabled, lets add it.
    if ($this->configuration['excerpt']) {
      $result_items = $results->getResultItems();
      $this->addExcerpts($result_items, $elasticsearchResponse);
    }

  }

  /**
   * Adds excerpts to all results, if possible.
   *
   * @param \Drupal\search_api\Item\ItemInterface[] $results
   *   The result items to which excerpts should be added.
   * @param array $elasticsearchResponse
   *   Elasticsearch response.
   */
  protected function addExcerpts(array $results, array $elasticsearchResponse) {
    foreach ($elasticsearchResponse['hits']['hits'] as $item) {
      $itemId = $item['_id'];
      $highlightsString = '';

      if (isset($item['highlight']) && isset($item['highlight']['es_attachment.attachment.content'])) {
        $highlights = $item['highlight']['es_attachment.attachment.content'];
        // There can be multiple highlights.
        foreach ($highlights as $highlight) {
          $highlightsString .= $highlight;
        }
        $results[$itemId]->setExcerpt($this->createExcerpt($highlightsString));
      }
    }
  }

  /**
   * Returns snippets from a piece of text, with certain keywords highlighted.
   *
   * @param string $text
   *   The text to extract fragments from.
   *
   * @return string|null
   *   A string containing HTML for the excerpt. Or NULL if no excerpt could be
   *   created.
   */
  protected function createExcerpt($text) {
    // Prepare text by stripping HTML tags and decoding HTML entities.
    if ($this->configuration['highlight'] == 'no') {
      // TODO revisit this.
      $text = strip_tags(str_replace(['<', '>'], [' <', '> '], $text));
    }

    $text = Html::decodeEntities($text);
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text, ' ');

    return $text;
  }

}
