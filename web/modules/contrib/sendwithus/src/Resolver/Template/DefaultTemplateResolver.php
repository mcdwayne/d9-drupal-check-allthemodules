<?php

declare(strict_types = 1);

namespace Drupal\sendwithus\Resolver\Template;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sendwithus\Context;
use Drupal\sendwithus\Resolver\Variable\VariableCollector;
use Drupal\sendwithus\Template;

/**
 * Defines the default template resolver.
 */
final class DefaultTemplateResolver extends BaseTemplateResolver {

  /**
   * The storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\sendwithus\Resolver\Variable\VariableCollector $collector
   *   The variable resolver.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(VariableCollector $collector, EntityTypeManagerInterface $entityTypeManager) {
    $this->storage = $entityTypeManager->getStorage('sendwithus_template');

    parent::__construct($collector);
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(Context $context) : ? Template {
    /** @var \Drupal\sendwithus\Entity\Template[] $templates */
    if (!$templates = $this->storage->loadMultiple()) {
      return NULL;
    }

    $selected_template = NULL;
    foreach ($templates as $entity) {
      // Module name must always match.
      if ($context->getModule() !== $entity->getModule()) {
        continue;
      }
      $selected_template = $entity->id();

      if ($context->getKey() === $entity->getKey()) {
        // Can't find better match than key+template match.
        break;
      }
    }

    if ($selected_template) {
      $template = new Template($selected_template);
      // Populate template variables.
      parent::doCollectVariables($template, $context);

      return $template;
    }
    return NULL;
  }

}
