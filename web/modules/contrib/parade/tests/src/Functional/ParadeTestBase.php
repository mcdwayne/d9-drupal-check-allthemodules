<?php

namespace Drupal\Tests\parade\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\BrowserTestBase;

/**
 * Class ParadeTestBase.
 *
 * @group parade
 */
abstract class ParadeTestBase extends BrowserTestBase {

  /**
   * Return the 'Manage fields' table as an array.
   *
   * Does not include the header.
   *
   * @param string $tableXPath
   *   The xpath of the table.
   *
   * @return array
   *   The table (without header) as array.
   */
  protected function getFieldsTableBodyAsArray($tableXPath) {
    $headers = $this->xpath($tableXPath . '//thead//th');
    $columnToSkip = -1;

    foreach ($headers as $delta => $header) {
      if (
        strpos(strtolower($header->getText()), 'operations') !== FALSE
      ) {
        $columnToSkip = $delta;
      }
    }

    $rowsXPath = $tableXPath . '//tbody//tr';

    /** @var \Behat\Mink\Element\NodeElement[] $fieldMachineNames */
    $rows = $this->xpath($rowsXPath);
    $tableData = [];
    foreach ($rows as $row) {
      /** @var \Behat\Mink\Element\NodeElement[] $columns */
      $columns = $row->findAll('css', 'td');

      $columnData = [];
      foreach ($columns as $delta => $column) {
        if ($delta === $columnToSkip) {
          continue;
        }
        // Trim &nbsp; as well.
        $columnData[] = trim($column->getText(), " \t\n\r\0\x0B\xA0\xC2");
      }

      $tableData[] = $columnData;
    }

    return $tableData;
  }

  /**
   * Return the 'Manage display' table as an array.
   *
   * Does not include the header.
   *
   * @param string $tableXPath
   *   The xpath of the table.
   *
   * @return array
   *   The table (without header) as array.
   */
  protected function getViewsTableAsArray($tableXPath) {
    $headers = $this->xpath($tableXPath . '//thead//th');
    $columnsToSkip = [];

    $currentDelta = 0;
    foreach ($headers as $delta => $header) {
      $headerText = strtolower($header->getText());
      if (
        $header->hasClass('tabledrag-hide')
        || strpos($headerText, 'weight') !== FALSE
        || strpos($headerText, 'parent') !== FALSE
        || strpos($headerText, 'region') !== FALSE
      ) {
        $columnsToSkip[] = $currentDelta;
      }

      if ($header->hasAttribute('colspan')) {
        $colspan = ((int) $header->getAttribute('colspan')) - 1;
        ++$currentDelta;
        for ($i = 0; $i < $colspan; ++$i) {
          $columnsToSkip[] = $currentDelta;
          ++$currentDelta;
        }
        --$currentDelta;
      }

      ++$currentDelta;
    }

    $rowsXPath = $tableXPath . '//tbody//tr';

    /** @var \Behat\Mink\Element\NodeElement[] $fieldMachineNames */
    $rows = $this->xpath($rowsXPath);
    $tableData = [];
    foreach ($rows as $row) {
      if (
        $row->hasClass('region-populated')
        || $row->hasClass('region-hidden-title')
      ) {
        continue;
      }

      /** @var \Behat\Mink\Element\NodeElement[] $columns */
      $columns = $row->findAll('css', 'td');

      $columnData = [];
      foreach ($columns as $delta => $column) {
        if (in_array($delta, $columnsToSkip, FALSE)) {
          continue;
        }
        // Trim &nbsp; as well.
        $columnData[] = trim($column->getText(), " \t\n\r\0\x0B\xA0\xC2");
      }

      $tableData[] = $columnData;
    }

    return $tableData;
  }

  /**
   * Assert if two arrays are equal or not.
   *
   * @param array $expectedArray
   *   The expected array.
   * @param array $actualArray
   *   The actual array.
   * @param string $infoMessage
   *   Optionally info message for fail result report.
   */
  protected function assertArraysAreEqual(array $expectedArray, array $actualArray, $infoMessage = NULL) {
    if (!empty($infoMessage)) {
      $infoMessage = ' at: ' . $infoMessage;
    }

    self::assertCount(count($expectedArray), $actualArray, "Arrays don't have the same amount of values." . $infoMessage);

    /** @var array $row */
    foreach ($expectedArray as $rInd => $row) {
      foreach ($row as $cInd => $column) {

        $areEquals = $column === $actualArray[$rInd][$cInd];
        $isSubstring = (!empty($column) && strpos($actualArray[$rInd][$cInd], $column) !== FALSE);

        if ($areEquals || $isSubstring) {
          continue;
        }

        self::fail(new FormattableMarkup('The expected value "@expVal" does not exist in row @rind. Actual value is "@actVal"@infoMessage', [
          '@expVal' => $column,
          '@rind' => $rInd,
          '@actVal' => $actualArray[$rInd][$cInd],
          '@infoMessage' => $infoMessage,
        ]));
      }
    }
  }

}
