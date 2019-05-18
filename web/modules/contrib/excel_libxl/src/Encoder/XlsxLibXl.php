<?php

namespace Drupal\excel_libxl\Encoder;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\Entity\DateFormat;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

/**
 * Adds XLSX encoder support via LibXL library for the Serialization API.
 */
class XlsxLibXl implements EncoderInterface {

  /**
   * The format that this encoder supports.
   *
   * @var string
   */
  protected static $format = 'xlsx';

  /**
   * Format to write XLS files as.
   *
   * @var string
   */
  protected $xlsFormat = 'Excel2007';

  /**
   * The XLSX Book.
   *
   * @var \ExcelBook
   */
  protected $xlBook;

  /**
   * The XLSX Spreadsheet.
   *
   * @var \ExcelSheet
   */
  protected $spreadsheet;


  /**
   * Constructs an XLS encoder.
   *
   * @param string $xls_format
   *   The XLS format to use.
   */
  public function __construct($xls_format = 'Excel2007') {
    $this->xlsFormat = $xls_format;
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = []) {
    switch (gettype($data)) {
      case 'array':
        // Nothing to do.
        break;

      case 'object':
        $data = (array) $data;
        break;

      default:
        $data = [$data];
        break;
    }

    try {
      // Instantiate a new excel object.
      $this->xlBook = new \ExcelBook(NULL, NULL, TRUE);
      $this->xlBook->setLocale('UTF-8');
      $this->spreadsheet = $this->xlBook->addSheet('Worksheet');
      $start_row = 0;
      // Set header text.
      if (isset($context['header_text'])) {
        if (is_string($context['header_text'])) {
          $context['header_text'] = ['text' => $context['header_text']];
        }
        $this->setHeaderFooterText($context['header_text'], $context, $start_row);
      }
      // Set headers.
      $this->setHeaders($data, $context, $start_row);
      // Set the data.
      $this->setData($data, $start_row, $context);
      // Set footer text.
      if (isset($context['footer_text'])) {
        if (is_string($context['footer_text'])) {
          $context['footer_text'] = ['text' => $context['footer_text']];
        }
        $this->setHeaderFooterText($context['footer_text'], $context, $start_row);
      }

      if (!empty($context['views_style_plugin']->options['xls_settings'])) {
        $this->setSettings($context['views_style_plugin']->options['xls_settings']);
      }
      return $this->xlBook->save();
    }
    catch (\Throwable $e) {
      throw new InvalidDataTypeException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding($format) {
    return $format === static::$format;
  }

  /**
   * Set sheet headers.
   *
   * @param array $data
   *   The data array.
   * @param array $context
   *   The context options array.
   * @param int $start_row
   *   The start row.
   */
  protected function setHeaders(array $data, array $context, &$start_row) {
    // TODO: Add configuration for table header.
    $font = $this->xlBook->addFont();
    $font->color(\ExcelFormat::COLOR_WHITE);
    $font->bold(TRUE);
    $font->size(12);
    $format = $this->xlBook->addFormat();
    $format->borderStyle(\ExcelFormat::BORDERSTYLE_THIN);
    $format->fillPattern(\ExcelFormat::FILLPATTERN_SOLID);
    $format->patternForegroundColor(\ExcelFormat::COLOR_OCEANBLUE_CF);
    $format->horizontalAlign(\ExcelFormat::ALIGNH_CENTER);
    $format->setFont($font);
    $c = 0;
    // Extract headers from the data.
    $headers = $this->extractHeaders($data, $context);
    foreach ($headers as $column => $header) {
      $this->spreadsheet->write($start_row, $c, $this->cleanValue($header), $format);
      // TODO: Make this setting configurable.
      $this->spreadsheet->setColWidth(0, $c, -1);
      $c++;
    }
    $start_row++;
  }

  /**
   * Set sheet header/footer text.
   *
   * @param array $data
   *   The data array.
   * @param array $context
   *   The context options array.
   * @param int $start_row
   *   The start row.
   */
  protected function setHeaderFooterText(array $data, array $context, &$start_row) {
    // TODO: Add configuration for table header.
    $font = $this->xlBook->addFont();
    $font->size(12);
    $format = $this->xlBook->addFormat();
    $format->setFont($font);
    if (!empty($data['skip_rows_before'])) {
      $start_row += $data['skip_rows_before'];
    }
    if (!empty($data['colspan'])) {
      $this->spreadsheet->setMerge($start_row, $start_row, 0, $data['colspan']);
    }
    $this->spreadsheet->write($start_row, 0, $this->cleanValue($data['text']), $format);
    if (($line_count = count(explode("\n", $data['text']))) > 1) {
      $default_height = $this->spreadsheet->rowHeight($start_row);
      $this->spreadsheet->setRowHeight($start_row, $line_count * $default_height, $format);
    }
    if (!empty($data['skip_rows_after'])) {
      $start_row += $data['skip_rows_after'];
    }
    $start_row++;
  }

  /**
   * Set sheet data.
   *
   * @param array $data
   *   The data array.
   * @param int $start_row
   *   The start row.
   * @param array $context
   *   The context options array.
   */
  protected function setData(array $data, &$start_row, array $context) {
    $xls_data = $this->extractXlsServiceData($context);
    foreach ($data as $i => $row) {
      $column = 0;
      foreach ($row as $key => $value) {
        /** @var \ExcelFormat $format */
        $format = $this->xlBook->addFormat();
        $format->horizontalAlign(\ExcelFormat::ALIGNH_LEFT);
        // TODO: Change behaviour: Type keys must be the same as data keys!!!
        $xlsx_column_data = isset($xls_data[$key]) ? $xls_data[$key] : [];
        $data_type = \ExcelFormat::AS_NUMERIC_STRING;
        // Since headers have been added, rows are offset here by start row.
        $formatted_value = $this->formatValue($value, $format, $data_type, $xlsx_column_data);
        $this->spreadsheet->write($start_row, $column, $formatted_value, $format, $data_type);
        $column++;
      }
      $start_row++;
    }
    // TODO: Make this setting configurable.
    if (isset($column)) {
      $this->spreadsheet->setAutofitArea(0, $start_row, 0, $column);
    }
  }

  /**
   * Clean value for a given XLSX cell.
   *
   * @param string $value
   *   The raw value to be cleaned.
   *
   * @return string
   *   The cleaned value.
   */
  protected function cleanValue($value) {
    $value = Html::decodeEntities($value);
    $value = strip_tags($value);
    $value = trim($value);

    return $value;
  }

  /**
   * Formats a single value for a given XLSX cell if type options provided.
   *
   * @param mixed $value
   *   The raw value to be formatted.
   * @param \ExcelFormat|null $format
   *   The cell format or null.
   * @param int $data_type
   *   The cell type.
   * @param array $xlsx_data
   *   (optional) The array of column options.
   *
   * @return string
   *   The formatted value.
   */
  protected function formatValue($value, \ExcelFormat &$format, &$data_type, array $xlsx_data = []) {
    $need_changes = TRUE;
    if (empty($value)) {
      return $value;
    }
    try {
      $value = $this->cleanValue($value);
    }
    catch (\Throwable $throwable) {
      return 'Incorrect data type ' . gettype($value);
    }
    // Simple conversion for Numeric fields.
    if (is_numeric($value) && $need_changes) {
      $need_changes = FALSE;
      $value = floatval($value);
    }
    // Deepest changes if Type exist.
    if (!empty($xlsx_data['type']) && $need_changes) {
      switch ($xlsx_data['type']) {
        case 'number':
          $result = $this->parseNumeric($value, $xlsx_data);
          if ($result && !empty($xlsx_data['custom_cell_format'])) {
            $custom_cell_format = $this->xlBook->addCustomFormat($xlsx_data['custom_cell_format']);
            $format->numberFormat($custom_cell_format);
          }
          break;

        case 'date':
          if ($this->parseDate($value, $xlsx_data)) {
            $data_type = \ExcelFormat::AS_DATE;
            $custom_cell_format = !empty($xlsx_data['custom_cell_format']) ? $xlsx_data['custom_cell_format'] : FALSE;
            $cell_format = $custom_cell_format ? $this->xlBook->addCustomFormat($custom_cell_format) : \ExcelFormat::NUMFORMAT_DATE;
            $format->numberFormat($cell_format);
          }
          break;

        case 'currency':
          if ($this->parseCurrency($value, $xlsx_data)) {
            $custom_cell_format = !empty($xlsx_data['custom_cell_format']) ? $xlsx_data['custom_cell_format'] : '_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)';
            $cell_format = $this->xlBook->addCustomFormat($custom_cell_format);
            $format->numberFormat($cell_format);
          }
          break;
      }
    }

    return $value;
  }

  /**
   * Helper function for parsing Numeric values.
   *
   * @param mixed $value
   *   The value for parsing.
   * @param array $options
   *   The array of column options.
   *
   * @return bool
   *   The parsing result. True if parsing successful.
   */
  protected function parseNumeric(&$value, array $options) {
    $parsed_value = $value;
    $parsed_value = !empty($options['thousand_separator']) ? str_replace($options['thousand_separator'], '', $parsed_value) : $parsed_value;
    $parsed_value = !empty($options['decimal_separator']) ? str_replace($options['decimal_separator'], '.', $parsed_value) : $parsed_value;
    $parsed_value = preg_replace('/[^\d\.]/', '', $parsed_value);
    if (is_numeric($parsed_value)) {
      if (is_int($value)) {
        $value = intval($parsed_value);
      }
      else {
        $value = floatval($parsed_value);
      }
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Helper function for parsing Date values.
   *
   * @param mixed $value
   *   The value for parsing.
   * @param array $options
   *   The array of column options.
   *
   * @return bool
   *   The parsing result. True if parsing successful.
   */
  protected function parseDate(&$value, array $options) {
    $parsed_value = $value;
    if (!empty($options['date_format']) && $date = \DateTime::createFromFormat($options['date_format'] . '|', $parsed_value)) {
      $value = $date->getTimestamp();
      return TRUE;
    }
    if ($timestamp = strtotime($parsed_value)) {
      $value = $timestamp;
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Helper function for parsing Currency values.
   *
   * @param mixed $value
   *   The value for parsing.
   * @param array $options
   *   The array of column options.
   *
   * @return bool
   *   The parsing result. True if parsing successful.
   */
  protected function parseCurrency(&$value, array $options) {
    $locale = !empty($options['locale']) ? $options['locale'] : 'en_US';
    $currency = !empty($options['currency']) ? $options['currency'] : 'USD';
    $parser = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
    $parsed_value = $parser->parseCurrency($value, $currency);
    if (!is_bool($parsed_value)) {
      $value = $parsed_value;
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Extract the headers from the data array.
   *
   * @param array $data
   *   The data array.
   * @param array $context
   *   The context options array.
   *
   * @return string[]
   *   An array of headers to be used.
   */
  protected function extractHeaders(array $data, array $context) {
    $headers = [];
    if ($first_row = reset($data)) {
      if (array_key_exists('views_style_plugin', $context)) {
        /** @var \Drupal\views\ViewExecutable $view */
        $view = $context['views_style_plugin']->view;
        $fields = $view->field;
        foreach ($first_row as $key => $value) {
          $headers[] = !empty($fields[$key]->options['label']) ? $fields[$key]->options['label'] : $key;
        }
      }
      elseif (array_key_exists('header', $context)) {
        $headers = $context['header'];
      }
      else {
        foreach ($first_row as $key => $value) {
          $headers[] = $key;
        }
      }
    }

    return $headers;
  }

  /**
   * Extract the Types from the Context array.
   *
   * @param array $context
   *   The context options array.
   *
   * @return string[]
   *   An array of headers to be used.
   */
  protected function extractXlsServiceData(array $context) {
    $xls_data = [];
    if (empty(!$context)) {
      if (array_key_exists('views_style_plugin', $context)) {
        /** @var \Drupal\views\ViewExecutable $view */
        $view = $context['views_style_plugin']->view;
        $fields = $view->field;
        /** @var \Drupal\views\Plugin\views\field\FieldPluginBase $field */
        foreach ($fields as $name => $field) {
          $plugin = $field->getPluginDefinition();
          // Try to process only simple entity fields.
          // TODO: Refactor processing.
          if ($plugin['id'] == 'field' && !empty($field->options['type'])) {
            switch ($field->options['type']) {
              case 'number_integer':
              case 'number_unformatted':
              case 'number_decimal':
              case 'number_float':
                $xls_data[$name] = [
                  'type' => 'number',
                ];
                if (!empty($field->options['settings']['thousand_separator'])) {
                  $xls_data[$name]['thousand_separator'] = $field->options['settings']['thousand_separator'];
                }
                if (!empty($field->options['settings']['decimal_separator'])) {
                  $xls_data[$name]['decimal_separator'] = $field->options['settings']['decimal_separator'];
                }
                break;

              case 'created':
              case 'timestamp':
              case 'datetime_default':
                $xls_data[$name] = [
                  'type' => 'date',
                ];
                if (!empty($field->options['settings']['format_type'])
                  && $date_type = DateFormat::load($field->options['settings']['format_type'])
                ) {
                  $xls_data[$name]['date_format'] = $date_type->getPattern();
                }
                elseif (
                  !empty($field->options['settings']['date_format'])
                  && $date_type = DateFormat::load($field->options['settings']['date_format'])
                ) {
                  $xls_data[$name]['date_format'] = $date_type->getPattern();
                }
                elseif (
                  !empty($field->options['settings']['custom_date_format'])
                ) {
                  $xls_data[$name]['date_format'] = $field->options['settings']['custom_date_format'];
                }
                break;
            }
          }
        }
      }
      elseif (array_key_exists('xls_data', $context)) {
        $xls_data = $context['xls_data'];
      }
    }

    return $xls_data;
  }

  /**
   * Set XLS settings from the Views settings array.
   *
   * @param array $settings
   *   An array of XLS settings.
   */
  protected function setSettings(array $settings) {
    $this->xlsFormat = $settings['xls_format'];
  }

}
