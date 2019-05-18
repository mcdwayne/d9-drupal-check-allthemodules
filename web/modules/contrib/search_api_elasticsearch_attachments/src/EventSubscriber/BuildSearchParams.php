<?php

namespace Drupal\search_api_elasticsearch_attachments\EventSubscriber;

use Drupal\elasticsearch_connector\Event\BuildSearchParamsEvent;
use Drupal\search_api\Entity\Index;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\search_api_elasticsearch_attachments\Helpers;

/**
 * {@inheritdoc}
 */
class BuildSearchParams implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[BuildSearchParamsEvent::BUILD_QUERY] = 'searchParams';
    return $events;
  }

  /**
   * Method to build Params.
   *
   * @param \Drupal\elasticsearch_connector\Event\BuildSearchParamsEvent $event
   *   The BuildSearchParamsEvent event.
   */
  public function searchParams(BuildSearchParamsEvent $event) {
    $params = $event->getElasticSearchParams();
    // Set default boost.
    $boost = 1.0;
    // We need to get the processor.
    $indexName = Helpers::getIndexName($event->getIndexName());
    $processors = Index::load($indexName)->getProcessors();
    // Try to load boost value from config form.
    if (!empty($processors['elasticsearch_attachments'])) {
      $boost = $processors['elasticsearch_attachments']->getConfiguration()['boost'];
      // Get original query.
      $originalBoolQuery = $params['body']['query']['bool']['must'];
      // Get query string.
      if (isset($originalBoolQuery['query_string'])) {
        $queryString = $originalBoolQuery['query_string']['query'];
        // Build nestedQuery.
        // @see https://www.elastic.co/guide/en/elasticsearch/guide/current/nested-query.html.
        $nestedQuery = [
          'nested' => [
            'path' => 'es_attachment',
            'query' => [
              'bool' => [
                'must' => [
                  'query_string' => [
                    'query' => $queryString,
                    'fields' => [
                      'es_attachment.attachment.content^' . $boost,
                    ],
                  ],
                ],
              ],
            ],
          ],
        ];
        // We need to change the bool query from must to should.
        // This is requried to add support for nested and string queries.
        $params['body']['query']['bool']['should'] = [];
        unset($params['body']['query']['bool']['must']);
        $params['body']['query']['bool']['should'][] = $originalBoolQuery;
        $params['body']['query']['bool']['should'][] = $nestedQuery;
        // Add min match param.
        $params['body']['query']['bool']['minimum_should_match'] = 1;
      }
    }
    // Add highlight if enabled.
    if (!empty($processors['elasticsearch_attachments_highlight'])) {
      $processorConf = $processors['elasticsearch_attachments_highlight']->getConfiguration();
      $prefix = $processorConf['prefix'];
      $suffix = $processorConf['suffix'];
      // See: https://github.com/elastic/elasticsearch-php/issues/394
      $params['body']['highlight']['fields']['es_attachment.attachment.content'] = (object) [];
      $params['body']['highlight']['pre_tags'] = [$prefix];
      $params['body']['highlight']['post_tags'] = [$suffix];
    }
    // Set updated params array.
    $event->setElasticSearchParams($params);
  }

}
