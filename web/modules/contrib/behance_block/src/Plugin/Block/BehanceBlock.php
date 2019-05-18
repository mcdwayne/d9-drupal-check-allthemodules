<?php

namespace Drupal\behance_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

/**
 * @file
 * Contains \Drupal\behance_block\Plugin\Block\BehanceBlock.
 */

/**
 * Provides a 'Behance Block' Block.
 *
 * @Block(
 *   id = "behance_block",
 *   admin_label = @Translation("Behance Block"),
 *   category = @Translation("Integrations"),
 * )
 */
class BehanceBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\config_factoryInterface
   */
  protected $configFactory;

  protected $apiKey;
  protected $userID;
  protected $newTab;

  /**
   * Class Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configFactory
      ->get('behance_block.settings');

    $this->apiKey = $config->get('api_key');
    $this->userID = $config->get('user_id');
    $this->newTab = $config->get('new_tab');

    $is_api_key_set = (isset($this->apiKey) && !empty($this->apiKey));
    $is_user_id_set = (isset($this->userID) && !empty($this->userID));

    // API key and User ID are set - show Behance projects.
    if ($is_api_key_set && $is_user_id_set) {
      return [
        '#theme' => 'behance_block',
        '#projects' => $this->projects(),
        '#tags' => $this->tags(),
        '#new_tab' => $this->newTab == 0 ? 'target=_self' : 'target=_blank',
        '#cache' => [
          'max-age' => 0
        ],
        '#attached' => [
          'library' => [
            'behance_block/behance_block'
          ],
        ],
      ];
    }
    // Show error if required values are missing.
    else {
      return [
        '#markup' => 'You must set an API key and the username in the module settings. <a href="/admin/config/services/behance">Click here</a> to go the module settings.',
        '#cache' => [
          'max-age' => 0
        ],
        '#attached' => [
          'library' => [
            'behance_block/behance_block'
          ],
        ],
      ];
    }
  }

  /**
   * Returns array with all projects.
   */
  private function projects() {
    // Get projects from the cache.
    $cache = \Drupal::cache();
    $cached_data = $cache->get('behance_block:projects');
    if ($cached_data) {
      return unserialize($cached_data->data);
    }

    $projects = $this->fetchProjects();

    // Store data to the cache.
    $cache->set(
      'behance_block:projects',
      serialize($projects),
      time() + 86400
    );

    return $projects;
  }

  /**
   * Get Behance projects from API endpoint.
   */
  private function fetchProjects() {
    $i = 1;
    $loop_through = TRUE;
    $all_projects = [];

    $client = new Client();

    // Loop while you get not empty JSON response.
    while ($loop_through) {
      try {
        $response = $client->get('http://api.behance.net/v2/users/' .
          $this->userID . '/projects?api_key=' . $this->apiKey . '&per_page=24&page=' . $i);
        $response_code = $response->getStatusCode();
        $projects_json_page = $response->getBody();
        $projects_json = json_decode($projects_json_page, TRUE);
      } catch (ClientException $e) {
        $response = $e->getResponse();
        $response_code = $response->getStatusCode();
        watchdog_exception('behance_block', $e);
      }

      if ($response_code == 200) {
        if ($projects_json['projects']) {
          foreach ($projects_json['projects'] as $projects) {
            $all_projects[] = $projects;
          }
          $i++;
        }
        else {
          $loop_through = FALSE;
        }
      }
      else {
        $loop_through = FALSE;
      }

    }

    return $all_projects ? $all_projects : [];
  }

  /**
   * Returns all Behance tags in array.
   */
  private function tags() {
    // Get tags from the cache.
    $cache = \Drupal::cache();
    $cached_data = $cache->get('behance_block:tags');
    if ($cached_data) {
      return unserialize($cached_data->data);
    }

    $tags = $this->fetchTags();

    // Store data to the cache.
    $cache->set(
      'behance_block:tags',
      serialize($tags),
      time() + 86400
    );

    return $tags;
  }

  /**
   * Get Behance field names (tags) and store them in JSON file.
   */
  private function fetchTags() {
    // Get response from endpoint and save it.
    $client = new Client();

    try {
      $response = $client->get('http://api.behance.net/v2/fields?api_key='
        . $this->apiKey);
      $behance_fields_json = $response->getBody();
      $behance_fields = json_decode($behance_fields_json, TRUE);

      $tags = [];
      foreach ($behance_fields['fields'] as $key => $behance_field) {
        $tags[$behance_field['name']] = $behance_field['id'];
      }
      return $tags;
    } catch (ClientException $e) {
      watchdog_exception('behance_block', $e);
      return [];
    }
  }

}
