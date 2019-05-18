<?php

namespace Drupal\tr_rulez\Plugin\TypedDataFilter;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\Type\StringInterface;
use Drupal\tr_rulez\DataFilterBase;

/**
 * A data filter which marks string data as sanitized.
 *
 * @DataFilter(
 *   id = "raw",
 *   label = @Translation("The raw filter prevents HTML-encoding of the input string."),
 * )
 */
class RawFilter extends DataFilterBase {

  /**
   * {@inheritdoc}
   */
  public function canFilter(DataDefinitionInterface $definition) {
    return is_subclass_of($definition->getClass(), StringInterface::class);
  }

  /**
   * {@inheritdoc}
   */
  public function filtersTo(DataDefinitionInterface $definition, array $arguments) {
    return DataDefinition::create('string');
  }

  /**
   * {@inheritdoc}
   */
  public function filter(DataDefinitionInterface $definition, $value, array $arguments, BubbleableMetadata $bubbleable_metadata = NULL) {
    $value = Xss::filterAdmin($value);
    return Markup::create($value);
  }

}
