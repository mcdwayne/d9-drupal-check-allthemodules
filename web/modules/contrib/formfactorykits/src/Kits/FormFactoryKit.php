<?php

namespace Drupal\formfactorykits\Kits;

use Drupal\kits\Kit;
use Drupal\kits\Services\KitsInterface;

/**
 * Class FormFactoryKit
 *
 * @package Drupal\formfactorykits\Kits
 */
abstract class FormFactoryKit extends Kit implements FormFactoryKitInterface {
  const ARRAY_PARENTS_KEY = 'array_parents';
  const PARENTS_KEY = 'parents';
  const TYPE_KEY = 'type';
  const TYPE = NULL;

  const DESCRIPTION_KEY = 'description';
  const ATTRIBUTES_KEY = 'attributes';
  const AFTER_BUILD_KEY = 'after_build';
  const AJAX_KEY = 'ajax';
  const DISABLED_KEY = 'disabled';
  const ELEMENT_VALIDATE_KEY = 'element_validate';
  const FIELD_PREFIX_KEY = 'field_prefix';
  const FIELD_SUFFIX_KEY = 'field_suffix';
  const PROCESS_KEY = 'process';
  const REQUIRED_KEY = 'required';
  const STATES_KEY = 'states';
  const TITLE_KEY = 'title';
  const TITLE = NULL;
  const TITLE_DISPLAY_KEY = 'title_display';
  const TREE_KEY = 'tree';
  const VALUE_KEY = 'value';
  const VALUE_DEFAULT_KEY = 'default_value';
  const VALUE_CALLBACK_KEY = 'value_callback';

  /**
   * FormFactoryKit constructor.
   *
   * @param \Drupal\kits\Services\KitsInterface $kitsService
   * @param string $id
   * @param array $parameters
   * @param array $context
   */
  public function __construct(KitsInterface $kitsService,
                              $id = NULL,
                              array $parameters = [],
                              array $context = []) {
    if (!array_key_exists(self::TITLE_KEY, $parameters) && !empty(static::TITLE)) {
      $parameters[self::TITLE_KEY] = $kitsService->t(static::TITLE);
    }
    if (!array_key_exists(self::TYPE_KEY, $parameters) && NULL !== static::TYPE) {
      $parameters[self::TYPE_KEY] = static::TYPE;
    }
    parent::__construct($kitsService, $id, $parameters, $context);
  }

  /**
   * @return string
   */
  public function getType() {
    $type = $this->get(self::TYPE_KEY);
    if (empty($type)) {
      throw new \LogicException('Type required');
    }
    return $type;
  }

  /**
   * @return array
   */
  public function getArray() {
    $artifact = [];
    if (!in_array('parents', $this->excludedParameters)) {
      $parents = $this->getParents();
      if (!empty($parents)) {
        $artifact['#parents'] = $parents;
      }
    }
    foreach ($this->parameters as $parameter => $value) {
      if (NULL !== $value) {
        $artifact['#' . $parameter] = $value;
      }
    }
    $artifact += $this->getChildrenArray();
    return $artifact;
  }
}
