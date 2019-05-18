<?php

namespace Drupal\formfactorykits\Kits\Container\Table;

use Drupal\formfactorykits\Kits\FormFactoryKit;

/**
 * Class TableKit
 *
 * @package Drupal\formfactorykits\Kits\Container\Table
 */
class TableKit extends FormFactoryKit {
  const ID = 'table';
  const TYPE = 'table';
  const CAPTION_KEY = 'caption';
  const HEADER_KEY = 'header';
  const ROWS_KEY = 'rows';
  const EMPTY_KEY = 'empty';
  const RESPONSIVE_KEY = 'responsive';
  const STICKY_KEY = 'sticky';

  /**
   * @var \Drupal\formfactorykits\Kits\Container\Table\TableRowKit[]
   */
  private $rows = [];

  /**
   * @param $caption
   *
   * @return static
   */
  public function setCaption($caption) {
    return $this->set(self::CAPTION_KEY, $caption);
  }

  /**
   * @param string $title
   *
   * @return static
   */
  public function appendHeaderColumn($title) {
    $header = $this->getHeader();
    $header[] = $title;
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
   * @param array $row
   *
   * @return TableRowKit
   */
  public function createRow($row = []) {
    return TableRowKit::create($this->kitsService)->setRow($row);
  }

  /**
   * @param array $rows
   *
   * @return $this
   */
  public function setRows(array $rows = []) {
    foreach ($rows as $key => $row) {
      if (!$row instanceof TableRowKit) {
        $rows[$key] = TableRowKit::create($this->kitsService)->setRow($row);
      }
    }
    $this->rows = $rows;
    return $this;
  }

  /**
   * @param TableRowKit|array $row
   *
   * @return static
   */
  public function appendRow($row = []) {
    if (!$row instanceof TableRowKit) {
      $row = $this->createRow($row);
    }
    $this->rows[] = $row;
    return $this;
  }

  /**
   * @inheritdoc
   */
  public function getArray() {
    $artifact = parent::getArray();
    foreach ($this->rows as $row) {
      $artifact['#rows'][] = $row->getArray();
    }
    return $artifact;
  }
}
