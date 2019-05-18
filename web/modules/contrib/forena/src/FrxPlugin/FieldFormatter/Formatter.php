<?php
/**
 * @file contains various methods for extending report
 * formating, layout, transformation and design.
 *
 */
namespace Drupal\forena\FrxPlugin\FieldFormatter;
use Drupal\forena\Token\ReportReplacer;

/**
 * Formatter for common drupal fields.
 *
 * @FrxFormatter(
 *   id = "Formatter"
 * )
 */
class Formatter implements FormatterInterface {
  /**
   * @section
   * Below here are advertising methods
   */

  /**
   * List formats provided by this class.
   * @return array
   */
  public function formats() {
    $formats = array('drupal_date_format' => 'Drupal Date',
                     'xhtml' => 'XHTML Decode of data',
                     'drupal_text_format' => 'Drupal Text Format',
                     'iso_date' => 'ISO Date',
                     'sprintf' => 'Sprintf() function',
                     'number' => 'Number',
                     'drupal_translation' => 'Translation Text (Drupal)',
                     'template' => 'Template (Field is a forena)',
                     'indentation' => 'Indentation',
                     'expression' => 'Expression (xpath)'
                     );
                     return $formats;
  }


  /**
   * Determines if a value is a number.
   * @param $value
   * @return bool
   */
  private function is_number($value) {
    $non_numeric_chars = trim($value, ' +-.,0123456789');
    // Determine if it contains +- in the interior
    // Zero is ok here bu
    $inner_symbols = FALSE;
    if (strpos($value, '+') || strpos($value, '-') || strpos($value, ' ')) $inner_symbols =  TRUE;
    return (empty($non_numeric_chars) && trim($value)!=='' && !$inner_symbols) ? TRUE : FALSE;
  }


  /**
   * @param $value
   * @param $format_string
   * @param \Drupal\forena\Token\ReportReplacer $teng
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   */
  public function drupal_translation($value, $format_string, $teng) {
    $field = '';
    if ($format_string) $field = '{' . $format_string . '}';
    $field = $teng->replace($field, TRUE);
    $vars = array();
    if ($field) {
      $vars = @unserialize($field);
      if (!$vars) $vars = array();
    }
    return t($value, $vars);
  }

  /**
   *
   * @param string $value
   * @param string $format_string
   * @param \Drupal\forena\Token\ReportReplacer $teng
   *   Replacer object to use in token replacement.
   * @return \Drupal\Component\Render\MarkupInterface
   */
  public function drupal_text_format($value, $format_string, $teng) {
    $field = '';
    if ($format_string) $field = $format_string;
    $field = $teng->replace($field, TRUE);
    if ($field) {
      $format = $field;
    }
    else {
      $format = filter_default_format();
    }
    return check_markup(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), $format);
  }


  /**
   * Date formatting method
   * @param string $value
   * @param string $format_string
   * @return bool|string
   */
  public function drupal_date_format($value, $format_string) {
    if (!$format_string) $format_string = 'small';
    switch ($format_string) {
      case 'medium':
        $type = $format_string;
        $format='';
        break;
      case 'small':
        $type = $format_string;
        $format='';
        break;
      case 'large':
        $type = $format_string;
        $format='';
        break;
      default:
        $type = 'custom';
        $format = $format_string;
    }

    if ($value) {
      if ($type != 'custom') {
        $value = \Drupal::service('date.formatter')->format($value);
      }
      else {
        $value = date($format, $value);
      }
    }
    return $value;
  }

  /**
   *  Format an ISO date as a date.
   */
  public function iso_date($value, $format_string) {
    $date = ($value) ? strtotime($value) : '' ;
    return $this->drupal_date_format($date, $format_string);
  }

  /**
   * Format data as XHTML, escaping output. 
   * @param $value
   *   The value to format. 
   * @param $format_string
   *   The name  of the input format to run against
   * @return string
   */
  public function xhtml($value, $format_string) {
    if ($value) {
      $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');

      if ($format_string && filter_format_exists($format_string)) {
        $value = check_markup($value, $format_string);
      }
    }
    return $value;
  }

  public function sprintf($value, $format_string) {
    if ($value) {
      $value = sprintf($format_string, $value);
    }
    return $value;
  }

  /**
   * Format field as a number
   * @param string $value
   * @param string $format_string
   * @return string
   */
  public function number($value, $format_string) {
    // Determine number nubmer formatter from the string.
    $chars = str_replace(array('9', '0', '$'), '', $format_string);
    if (strlen($chars) > 1) {
      $th_sep = substr($chars, 0, 1);
      $dec_sep = substr($chars, 1, 1);
      $dec_pos = strrpos($format_string, $dec_sep);
      if ($dec_pos) {
        $dec = strlen($format_string) - $dec_pos -1;
      }
      else {
        $dec = 0;
        $dec_sep = '';
      }
    }
    elseif (strlen($chars) == 1) {
      $th_sep = substr($chars, 0, 1);
      $dec_sep = '';
      $dec = 0;
    }
    else {
      $dec_sep='';
      $th_sep = '';
      $dec = 0;
    }


    if ($value && $this->is_number($value))  $value = number_format($value, $dec, $dec_sep, $th_sep);
    return $value;
  }

  /**
   * Indicates the data in the field is a template that can be used by forena to format.
   * @param string $value
   * @param string $format_string
   * @param ReportReplacer $teng
   * @return string
   *   Formatted field
   */
  public function template($value, $format_string, $teng, $default) {
    if (!$value) {
      $value = $default;
    }
    if ($value) {
      $value = $teng->replace($value);
      $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
      if ($format_string && filter_format_exists($format_string)) {
        $value = check_markup($value, $format_string);
      }
    }

    return $value;
  }

  public function indentation($value) {
    if ($value) {
      $vars = array('size' => $value);
      $value = theme_indentation($vars);
    }
    else {
      $value = '';
    }
    return $value;
  }

  /**
   * @param $value
   * @param $format_string
   * @param ReportReplacer $teng
   * @return mixed
   * Return an xapth expression based on data in the
   */
  public function expression($value, $format_string, $teng) {
    if ($value) {
      $value = $teng->replace($format_string);
      if ($value) $value = $teng->replace('{' . $value . '}');
    }
    return $value;
  }


}
