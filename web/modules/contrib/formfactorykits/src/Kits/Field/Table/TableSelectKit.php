<?php

namespace Drupal\formfactorykits\Kits\Field\Table;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\formfactorykits\Kits\Traits\DescriptionTrait;
use Drupal\formfactorykits\Kits\Traits\TitleTrait;
use Drupal\formfactorykits\Kits\Traits\ValueTrait;
use Drupal\kits\Services\KitsInterface;

/**
 * Class TableSelectKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Table
 */
class TableSelectKit extends FormFactoryKit {
  use DescriptionTrait;
  use TitleTrait;
  use ValueTrait;
  const ID = 'tableselect';
  const TYPE = 'tableselect';
  const HEADER_KEY = 'header';
  const OPTIONS_KEY = 'options';
  const EMPTY_KEY = 'empty';
  const JS_SELECT_KEY = 'js_select';

  /**
   * {@inheritdoc}
   */
  public function __construct(KitsInterface $kitsService,
                              $id = NULL,
                              array $parameters = [],
                              array $context = []) {
    if (!array_key_exists(self::OPTIONS_KEY, $parameters)) {
      $parameters[self::OPTIONS_KEY] = [];
    }
    parent::__construct($kitsService, $id, $parameters, $context);
  }

  /**
   * @param string $column
   * @param string $title
   *
   * @return static
   */
  public function appendHeaderColumn($column, $title) {
    $header = $this->getHeader();
    $header[$column] = $title;
    $this->setHeader($header);
    return $this;
  }

  /**
   * @param array $default
   *
   * @return array
   */
  public function getHeader(array $default = []) {
    return $this->get(self::HEADER_KEY, $default);
  }

  /**
   * @param array $columns
   *
   * @return static
   */
  public function setHeader(array $columns) {
    return $this->set(self::HEADER_KEY, $columns);
  }

  /**
   * @param string $option
   * @param array $row
   * @return static
   */
  public function appendOption($option, array $row) {
    $this->parameters['options'][$option] = $row;
    return $this;
  }

  /**
   * @param array $options
   *
   * @return static
   */
  public function setOptions(array $options = []) {
    $this->parameters['options'] = $options;
    return $this;
  }
}
