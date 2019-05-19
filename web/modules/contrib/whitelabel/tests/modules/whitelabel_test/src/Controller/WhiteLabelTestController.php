<?php

namespace Drupal\whitelabel_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\whitelabel\WhiteLabelProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for switching white label via URL. Used for testing.
 *
 * @package Drupal\whitelabel_test\Controller
 */
class WhiteLabelTestController extends ControllerBase {

  /**
   * Holds the white label provider.
   *
   * @var \Drupal\whitelabel\WhiteLabelProviderInterface
   */
  protected $whiteLabelProvider;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * WhiteLabelPathProcessor constructor.
   *
   * @param \Drupal\whitelabel\WhiteLabelProviderInterface $white_label_provider
   *   The white label provider.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(WhiteLabelProviderInterface $white_label_provider, EntityTypeManagerInterface $entity_type_manager) {
    $this->whiteLabelProvider = $white_label_provider;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('whitelabel.whitelabel_provider'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Sets the white label to the white label of the provided token.
   *
   * @param string $test_value
   *   The value to set.
   *
   * @return array
   *   A render array.
   */
  public function set($test_value) {
    if ($test_value) {
      if ($test_value === 'reset') {
        $this->whiteLabelProvider->resetWhiteLabel();
      }

      if ($whitelabels = $this->entityTypeManager->getStorage('whitelabel')->loadByProperties(['token' => $test_value])) {
        $this->whiteLabelProvider->setWhiteLabel(reset($whitelabels));
      }
    }

    return ['#markup' => $this->t('The current value of the stored session variable has been set to %val', ['%val' => $test_value])];
  }

}
