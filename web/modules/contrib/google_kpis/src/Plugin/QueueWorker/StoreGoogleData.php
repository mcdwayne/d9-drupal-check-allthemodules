<?php

namespace Drupal\google_kpis\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\google_kpis\GoogleKpisFetchAndStore;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process a queue of google_kpis items to store them.
 *
 * @QueueWorker(
 *   id = "google_kpis_queue",
 *   title = @Translation("Store data")
 * )
 */
class StoreGoogleData extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The google kpis fetch and store service.
   *
   * @var \Drupal\google_kpis\GoogleKpisFetchAndStore
   */
  protected $fetchAndStore;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\google_kpis\GoogleKpisFetchAndStore $fetch_and_store
   *   Google Kpis fetch and store service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, GoogleKpisFetchAndStore $fetch_and_store) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->fetchAndStore = $fetch_and_store;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('google_kpis.fetch_and_store')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (isset($data['nid']) && isset($data['data'])) {
      $node = $this->entityTypeManager->getStorage('node')->load($data['nid']);
      if ($node instanceof Node) {
        $this->entityTypeManager->getStorage('node')->resetCache([$node->id()]);
        /** @var \Drupal\google_kpis\Entity\GoogleKpis $google_kpi */
        $google_kpi = $this->fetchAndStore->linkGoogleKpisWithNode($node);
        if (isset($data['data']['google_analytics_data']) || isset($data['data']['search_analytics_data'])) {
          if (isset($data['data']['google_analytics_data'])) {
            $sessions_last_30 = NULL;
            $users_summary = NULL;
            $pageviews_summary = NULL;
            $ogsearches_summary = NULL;
            $ga = $data['data']['google_analytics_data'];
            $ga_pageviews = (int) $ga['pageviews'];
            $ga_users = (int) $ga['users'];
            $ga_sessions = (int) $ga['sessions'];
            $ga_organicsearches = (int) $ga['organicsearches'];
            // Get Storage value.
            $ga_sessions_storage = $google_kpi->field_sessions_storage->getValue();
            $ga_users_storage = $google_kpi->field_users_storage->getValue();
            $ga_pageviews_storage = $google_kpi->field_page_views_storage->getValue();
            $ga_ogsearches_storage = $google_kpi->field_og_searches_storage->getValue();
            // Add new value to array.
            $ga_sessions_storage[]['value'] = $ga_sessions;
            $ga_users_storage[]['value'] = $ga_users;
            $ga_pageviews_storage[]['value'] = $ga_pageviews;
            $ga_ogsearches_storage[]['value'] = $ga_organicsearches;
            // Summary of elements.
            $max_storage = $this->fetchAndStore->googleKpisSettings->get('max_storage');
            if (!$max_storage || empty($max_storage) || $max_storage == 0) {
              $max_storage = 29;
            }
            foreach ($ga_sessions_storage as $delta => $value) {
              if ($delta > $max_storage) {
                unset($ga_sessions_storage[0]);
                array_values($ga_sessions_storage);
              }
              $sessions_last_30 = $sessions_last_30 + $value['value'];
            }
            foreach ($ga_users_storage as $delta => $value) {
              if ($delta > $max_storage) {
                unset($ga_users_storage[0]);
                array_values($ga_users_storage);
              }
              $users_summary = $users_summary + $value['value'];
            }
            foreach ($ga_pageviews_storage as $delta => $value) {
              if ($delta > $max_storage) {
                unset($ga_pageviews_storage[0]);
                array_values($ga_pageviews_storage);
              }
              $pageviews_summary = $pageviews_summary + $value['value'];
            }
            foreach ($ga_ogsearches_storage as $delta => $value) {
              if ($delta > $max_storage) {
                unset($ga_ogsearches_storage[0]);
                array_values($ga_ogsearches_storage);
              }
              $ogsearches_summary = $ogsearches_summary + $value['value'];
            }
            // Set storage values.
            $google_kpi->set('field_sessions_storage', $ga_sessions_storage);
            $google_kpi->set('field_users_storage', $ga_users_storage);
            $google_kpi->set('field_page_views_storage', $ga_pageviews_storage);
            $google_kpi->set('field_og_searches_storage', $ga_ogsearches_storage);
            // Set values for 1 day.
            $google_kpi->set('field_sessions_yesterday', $ga_sessions);
            $google_kpi->set('field_users_yesterday', $ga_users);
            $google_kpi->set('field_page_views_yesterday', $ga_pageviews);
            $google_kpi->set('field_og_searches_yesterday', $ga_organicsearches);
            // Set storage summary.
            $google_kpi->set('field_sessions_summary', $sessions_last_30);
            $google_kpi->set('field_users_summary', $users_summary);
            $google_kpi->set('field_page_views_summary', $pageviews_summary);
            $google_kpi->set('field_og_searches_summary', $ogsearches_summary);
          }
          else {
            $google_kpi = $this->fetchAndStore->setDefaultsAnalyticsData($google_kpi);
          }
          if (isset($data['data']['search_analytics_data'])) {
            $gsc_row = $data['data']['search_analytics_data'];
            $gsc_ctr = $gsc_row->getCtr() * 100;
            $google_kpi->set('field_clicks', $gsc_row->getClicks());
            $google_kpi->set('field_impressions', $gsc_row->getImpressions());
            $google_kpi->set('field_ctr', $gsc_ctr);
            $google_kpi->set('field_position', $gsc_row->getPosition());
          }
          else {
            $google_kpi = $this->fetchAndStore->setDefaultsSearchData($google_kpi);
          }
        }
        else {
          $google_kpi = $this->fetchAndStore->setDefaultsAnalyticsData($google_kpi);
          $google_kpi = $this->fetchAndStore->setDefaultsSearchData($google_kpi);
        }
        if ($google_kpi->isNew() && $node->hasField('field_google_kpis')) {
          $google_kpi->save();
          $this->entityTypeManager->getStorage('google_kpis')->resetCache([$google_kpi->id()]);
          $node->set('field_google_kpis', $google_kpi->id());
          $node->setNewRevision(FALSE);
          $node->save();
        }
        else {
          $this->entityTypeManager->getStorage('google_kpis')->resetCache([$google_kpi->id()]);
          $google_kpi->save();
        }

      }
    }
  }

}
