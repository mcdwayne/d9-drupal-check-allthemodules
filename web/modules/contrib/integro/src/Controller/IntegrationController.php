<?php

namespace Drupal\integro\Controller;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\integro\IntegrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles the integration overview route.
 */
class IntegrationController extends IntegroControllerBase {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(TranslationInterface $string_translation, ModuleHandlerInterface $module_handler) {
    parent::__construct($string_translation, $module_handler);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('string_translation'),
      $container->get('module_handler')
    );
  }

  /**
   * Returns the route's title.
   *
   * @param \Drupal\integro\IntegrationInterface $integration
   *   The integration.
   *
   * @return string
   */
  public function title($integration) {
    return $this->t('%label integration', [
      '%label' => $integration->getDefinition()->getLabel(),
    ]);
  }

  /**
   * Handles the route.
   *
   * @param \Drupal\integro\IntegrationInterface $integration
   *   The integration.
   *
   * @return \mixed[]|\Symfony\Component\HttpFoundation\Response
   *   A render array or a Symfony response.
   */
  public function overview(IntegrationInterface $integration) {

    $build = [
      '#empty' => $this->t('There are no available operations.'),
      '#header' => [$this->t('Operation'), $this->t('Label'), $this->t('Description'), $this->t('Provider')],
      '#type' => 'table',
    ];

    // @todo Build the list of operations.

    return $build;
  }

}
