<?php

namespace Drupal\onlyone\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\onlyone\OnlyOneInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Validates the OnlyOne constraint.
 */
class OnlyOneConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The onlyone service.
   *
   * @var \Drupal\onlyone\OnlyOneInterface
   */
  protected $onlyone;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\onlyone\OnlyOneInterface $onlyone
   *   The onlyone service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, OnlyOneInterface $onlyone, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->onlyone = $onlyone;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'), $container->get('onlyone'), $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($node, Constraint $constraint) {
    // Getting the configured content types.
    $onlyone_content_types = $this->configFactory->get('onlyone.settings')->get('onlyone_node_types');
    // If the content type is configured we need to check if exits a node
    // created in the node language.
    // @see https://www.drupal.org/project/onlyone/issues/2962186
    // @see https://www.drupal.org/project/onlyone/issues/2969293
    if (in_array($node->getType(), $onlyone_content_types)) {
      // If we have a node in the current language we should not insert a new
      // one.
      $nid = $this->onlyone->existsNodesContentType($node->getType(), $node->language()->getId());
      // If the existing node have the same id that the node that is being saved
      // then is the same node that is being updated.
      if ($nid && $nid != $node->id()) {
        $existing_node = $this->entityTypeManager->getStorage('node')->load($nid);

        $values = [
          '%content_type' => $node->getType(),
          ':href' => $existing_node->toUrl()->toString(),
          '@title' => $existing_node->getTitle(),
          '%language' => $node->language()->getName(),
        ];

        $this->context->buildViolation($constraint->nodeExists, $values)->atPath('langcode')->addViolation();
      }
    }
  }

}
