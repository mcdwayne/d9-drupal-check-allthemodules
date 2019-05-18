<?php

namespace Drupal\integro\Controller;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\integro\IntegrationInterface;
use Drupal\integro\IntegrationManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles the integrations list route.
 */
class IntegrationListController extends IntegroControllerBase {

  /**
   * The integration manager.
   *
   * @var \Drupal\integro\IntegrationManagerInterface
   */
  protected $integrationManager;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\integro\IntegrationManagerInterface
   *   The integration manager.
   */
  public function __construct(TranslationInterface $string_translation, ModuleHandlerInterface $module_handler, IntegrationManagerInterface $integration_manager) {
    parent::__construct($string_translation, $module_handler);
    $this->integrationManager = $integration_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('string_translation'),
      $container->get('module_handler'),
      $container->get('integro_integration.manager')
    );
  }

  /**
   * Handles the collection route.
   *
   * @return mixed[]
   *   A render array.
   */
  public function collection() {
    $build = [
      '#empty' => $this->t('There are no available integrations.'),
      '#header' => [$this->t('Integration'), $this->t('Provider'), $this->t('Description')],
      '#type' => 'table',
    ];
    $integrations = $this->integrationManager->getIntegrations();
    uasort($integrations, function (IntegrationInterface $integration_a, IntegrationInterface $integration_b) {
      return strnatcasecmp($integration_a->getDefinition()->getLabel(), $integration_b->getDefinition()->getLabel());
    });
    foreach ($integrations as $integration_id => $integration) {
      $build[$integration_id]['label'] = [
        '#type' => 'link',
        '#title' => $integration->getDefinition()->getLabel(),
        '#url' => new Url('integro.integration.overview', [
          'integration' => $integration->getDefinition()->getId(),
        ]),
      ];
      $build[$integration_id]['provider'] = [
        '#markup' => $this->getProviderLabel($integration->getDefinition()->getProvider()),
      ];
      $build[$integration_id]['description'] = [
        '#markup' => $integration->getDefinition()->getDescription(),
      ];
    }

    return $build;
  }

}
