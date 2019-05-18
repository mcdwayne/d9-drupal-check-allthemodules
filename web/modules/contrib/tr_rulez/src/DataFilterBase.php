<?php

namespace Drupal\tr_rulez;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataTrait;
use Drupal\typed_data\DataFilterInterface;

/**
 * Base class for data filters.
 */
abstract class DataFilterBase extends PluginBase implements DataFilterInterface {

  use TypedDataTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getNumberOfRequiredArguments() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function allowsNullValues() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function suggestArgument(DataDefinitionInterface $definition, array $arguments, $input = '') {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateArguments(DataDefinitionInterface $definition, array $arguments) {
    $errors = [];
    if (count($arguments) < $this->getNumberOfRequiredArguments()) {
      $errors[] = $this->t('Missing arguments for filter %filter_id', ['%filter_id' => $this->getPluginId()]);
    }
    return $errors;
  }

}
