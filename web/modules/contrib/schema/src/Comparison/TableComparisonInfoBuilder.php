<?php
/**
 * @file
 * Contains Drupal\schema\Comparison\TableComparisonInfoBuilder.
 */

namespace Drupal\schema\Comparison;

use Drupal\schema\Comparison\Result\DifferentColumn;
use Drupal\schema\Comparison\Result\DifferentIndex;
use Drupal\schema\Comparison\Result\ExtraColumn;
use Drupal\schema\Comparison\Result\ExtraIndex;
use Drupal\schema\Comparison\Result\MissingColumn;
use Drupal\schema\Comparison\Result\MissingIndex;
use Drupal\schema\Comparison\Result\TableComparison;


class TableComparisonInfoBuilder {
  protected $o;

  public function __construct(TableComparison $comparison) {
    $this->o = $comparison;
  }

  public function getInfoArray() {
    $reasons = array();
    $notes = array();

    if ($this->o->isTableCommentDifferent()) {
      $reasons[] = array(
        '#markup' => 'Table comment is different.' .
          '<br/>declared: ' . $this->o->getDeclaredTableComment() .
          '<br/>actual: ' . $this->o->getActualTableComment()
      );
    }

    /** @var MissingColumn $column */
    foreach ($this->o->getMissingColumns() as $column) {
      $reasons[] = sprintf("%s: not in database", $column->getColumnName());
    }

    /** @var DifferentColumn $column */
    foreach ($this->o->getDifferentColumns() as $column) {
      $colname = $column->getColumnName();
      $kdiffs = $column->getDifferentKeys();
      $reasons[] = array(
        '#markup' => "column $colname - difference" .
          (count($kdiffs) > 1 ? 's' : '') . " on: " .
          implode(', ', $kdiffs) .
          "<br/>declared: " . schema_phpprint_column($column->getDeclaredSchema()) .
          '<br/>actual: ' . schema_phpprint_column($column->getActualSchema())
      );
    }

    /** @var ExtraColumn $column */
    foreach ($this->o->getExtraColumns() as $column) {
      $reasons[] = sprintf("%s: unexpected column in database", $column->getColumnName());
    }

    /** @var MissingIndex $index */
    foreach ($this->o->getMissingIndexes() as $index) {
      $keyname = $index->getIndexName();
      $type = $index->getType();
      if ($index->isPrimary()) {
        $reasons[] = "primary key: missing in database";
      }
      else {
        $reasons[] = "$type $keyname: missing in database";
      }
    }

    /** @var DifferentIndex $index */
    foreach ($this->o->getDifferentIndexes() as $index) {
      $type = $index->getType();
      $keyname = $index->getIndexName();
      if ($index->isPrimary()) {
        $reasons[] = array(
          '#markup' => "$type $keyname:<br />declared: " .
            schema_phpprint_key($index->getDeclaredSchema()) . '<br />actual: ' .
            schema_phpprint_key($index->getActualSchema())
        );
      }
      else {
        $reasons[] = array(
          '#markup' => "primary key:<br />declared: " .
            schema_phpprint_key($index->getDeclaredSchema()) . '<br />actual: ' .
            schema_phpprint_key($index->getActualSchema())
        );
      }
    }

    /** @var ExtraIndex $index */
    foreach ($this->o->getExtraIndexes() as $index) {
      $keyname = $index->getIndexName();
      $type = $index->getType();
      if ($index->isPrimary()) {
        $reasons[] = "primary key: missing in schema";
      }
      else {
        $notes[] = "$type $keyname: unexpected (not an error)";
      }
    }

    return array(
      'status' => $this->o->isStatusDifferent() ? "different" : "same",
      'reasons' => $reasons,
      'notes' => $notes,
    );
  }
}
