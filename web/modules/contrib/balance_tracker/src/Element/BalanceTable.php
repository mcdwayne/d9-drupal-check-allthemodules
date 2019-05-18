<?php

namespace Drupal\balance_tracker\Element;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Render\Element\Table;

/**
 * Provides a render element for a balance tracker balance table.
 *
 * Properties:
 * - #header: An array of table header labels.
 * - #rows: An array of the rows to be displayed. Each row is either an array
 *   of cell contents or an array of properties as described in table.html.twig
 *   Alternatively specify the data for the table as child elements of the table
 *   element. Table elements would contain rows elements that would in turn
 *   contain column elements.
 * - #empty: Text to display when no rows are present.
 * - #responsive: Indicates whether to add the drupal.responsive_table library
 *   providing responsive tables.  Defaults to TRUE.
 * - #sticky: Indicates whether to add the drupal.tableheader library that makes
 *   table headers always visible at the top of the page. Defaults to FALSE.
 * - #size: The size of the input element in characters.
 *
 * Usage example:
 * @code
 * $build['balance_table'] = [
 *   '#type' => 'balance_table',
 *   '#caption' => $this->t('My balance'),
 *   '#date_from' => strtotime('2017-05-23 12:05:34'),
 *   '#date_to' => strtotime('2017-08-23 12:05:34'),
 *   '#per_page' => 25,
 * ];
 * @endcode
 *
 * A pager can also be added:
 * @code
 * $build['pager'] = [
 *  '#type' => 'pager',
 *  '#tags' => [],
 * ];
 * @endcode
 *
 * @RenderElement("balance_table")
 */
class BalanceTable extends Table {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#theme' => 'table',
      '#per_page' => 25,
      '#user' => NULL,
      '#date_from' => NULL,
      '#date_to' => NULL,
      '#header' => [],
      '#rows' => [],
      '#caption' => "User's balance",
      '#empty' => t('There are no balance items for this user.'),
      '#attached' => [
        'library' => ['balance_tracker/balance_table'],
      ],

      '#tableselect' => FALSE,
      '#sticky' => FALSE,
      '#responsive' => TRUE,
      '#process' => [
        [$class, 'processTable'],
      ],
      // Render properties.
      '#pre_render' => [
        [$class, 'preRenderTable'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderTable($element) {
    if ($element['#user'] === NULL) {
      $element['#user'] = \Drupal::currentUser();
    }
    $uid = $element['#user']->id();
    $from = $element['#date_from'];
    $to = $element['#date_to'];
    $per_page = $element['#per_page'];
    $records = \Drupal::service('balance_tracker.storage')->getItemsRange($uid, $per_page, $from, $to);
    // Format the records from the database before we display them.
    $rows = [];
    $date_type = \Drupal::config('balance_tracker.settings')->get('date_format');
    $custom_format = \Drupal::config('balance_tracker.settings')->get('custom_date_format');
    foreach ($records as $record) {
      $row = [];
      // Get our date and time settings.
      $row['timestamp'] = \Drupal::service('date.formatter')->format($record->timestamp, $date_type, $custom_format);

      $row['message'] = new FormattableMarkup(Xss::filter($record->message), []);

      // Add class names to credits and debits so we can color them.
      if ($record->type === 'debit') {
        $row['amount'] = new FormattableMarkup('<span class="debit">-@amount</span>', ['@amount' => static::formatCurrency($record->amount)]);
      }
      elseif ($record->type === 'credit') {
        $row['amount'] = new FormattableMarkup('<span class="credit">@amount</span>', ['@amount' => static::formatCurrency($record->amount)]);
      }

      // Format the currency as needed.
      $row['balance'] = static::formatCurrency($record->balance);

      $rows[] = $row;
    }
    $element['#header'] = [
      'timestamp' => ['data' => t('Time')],
      'message' => ['data' => t('Message')],
      'amount' => ['data' => t('Amount')],
      'balance' => ['data' => t('Balance')],
    ];

    $element['#rows'] = $rows;

    return $element;
  }

  /**
   * Formats a currency value according to the admin settings.
   *
   * @param float $value
   *   A float containing the currency value to be displayed.
   *
   * @return string
   *   A string with the formatted currency.
   */
  public static function formatCurrency($value) {
    $config = \Drupal::config('balance_tracker.settings');
    $symbol = $config->get('currency_symbol');
    $position = $config->get('currency_symbol_position');
    $thousands_separator = $config->get('thousands_separator');
    $decimal_separator = $config->get('decimal_separator');

    $number = number_format($value, 2, $decimal_separator, $thousands_separator);

    if ($position === 'before') {
      return $symbol . $number;
    }
    else {
      return $number . $symbol;
    }
  }

}
