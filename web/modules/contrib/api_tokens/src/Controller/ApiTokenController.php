<?php

namespace Drupal\api_tokens\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\api_tokens\ApiTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for API tokens routes.
 */
class ApiTokenController extends ControllerBase {

  /**
   * The API token manager.
   *
   * @var \Drupal\api_tokens\ApiTokenManagerInterface
   */
  protected $apiTokenManager;

  /**
   * Constructs a new ApiTokenController.
   *
   * @param \Drupal\api_tokens\ApiTokenManagerInterface $api_token_manager
   *   The API token manager.
   */
  public function __construct(ApiTokenManagerInterface $api_token_manager) {
    $this->apiTokenManager = $api_token_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.api_token')
    );
  }

  /**
   * Displays the API tokens overview page.
   *
   * @return array
   *   A renderable array.
   */
  public function adminOverview() {
    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Token'),
        $this->t('Synopsis'),
        $this->t('Provider'),
      ],
      '#empty' => $this->t('There are no API tokens registered.'),
    ];
    foreach ($this->apiTokenManager->getSortedDefinitions() as $id => $definition) {
      $row = &$build['table'][$id];
      $row['token'] = [
        '#type' => 'item',
        '#title' => $definition['label'],
        '#description' => $definition['description'],
        '#description_display' => 'after',
      ];
      $row['synopsis'] = $this->buildSynopsis($id);
      $row['provider'] = [
        '#type' => 'item',
        '#title' => $definition['category'],
        '#description' => $this->t('Machine name: @provider', [
          '@provider' => $definition['provider'],
        ]),
        '#description_display' => 'after',
      ];
    }

    return $build;
  }

  /**
   * Renders the API token synopsis.
   *
   * @param string $id
   *   The API token plugin ID.
   *
   * @return array
   *   A renderable array.
   */
  protected function buildSynopsis($id) {
    $plugin = $this->apiTokenManager->createInstance($id);
    $parts = [];
    foreach ($plugin->reflector()->getParameters() as $param) {
      $name = $param->getName();
      if (!$param->isOptional()) {
        $name = "<strong>$name</strong>";
      }
      $parts[] = "<em>$name</em>";
    }
    $params = $parts ? '[' . implode(', ', $parts) . ']' : '';
    $build = [
      '#type' => 'html_tag',
      '#tag' => 'code',
      '#value' => "[api:$id$params/]",
    ];

    return $build;
  }

}
