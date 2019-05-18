<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 3/18/15
 * Time: 10:52 AM
 */

namespace Drupal\forena\Token;


use Drupal\forena\AppService;
use Drupal\forena\FrxPlugin\FieldFormatter\FormatterInterface;

class ReportReplacer extends TokenReplacerBase {
  public $fields = [];
  public $format_callbacks = [];
  public $formats = [];

  // Build the token replacer and set the base formatter.
  public function __construct() {
    parent::__construct( '/\{[^\n^\r^}]+}/' , '{}');
    $this->setFormatter($this);
    $formatters = AppService::instance()->getFormatterPlugins();
    foreach($formatters as $class) {
      /** @var FormatterInterface $formatter */
      $formatter = new $class();
      $formats = $formatter->formats();
      foreach($formats as $method => $label) {
        $this->format_callbacks[$method] = array($formatter, $method);
        $this->formats[$method] = $label;
      }
    }
  }

  /**
   * Replaces nested array data.
   * @param $data
   *   The array containing values to replace.
   * @param $raw
   *   TRUE implies no formatting of data.
   */
  public function replaceNested(&$data, $raw = TRUE) {
    if (is_array($data)) {
      $values = $data;
      foreach ($values as $k => $value) {
        // Replace key data
        $key = $k;
        if (strpos($k, '{') !== FALSE) {
          $key = $this->replace($key);
          unset($data[$k]);
          $data[$key] = $value;
        }

        // Replace value data.
        if (is_array($value)) {
          $this->replaceNested($data[$key], $raw);
        }
        else {
          $data[$key] = $this->replace($value, $raw);
        }
      }
    }
    else {
      $data = $this->replace($data, $raw);
    }
  }

  /**
   * Get the value from the data.
   * This is used by token_replace method to extract the data based on the path provided.
   * @param $data
   * @param $key
   * @return string|array
   */
  protected function get_value($key, $raw=FALSE) {
    $context = '';
    $raw_key = $key;
    $dataSvc = $this->dataService();
    if ($key && strpos($key, '.')) {
      @list($context, $key) = explode('.', $key, 2);
      $o = $this->getDataContext($context);
    }
    else {
      $o = $this->currentDataContext();
    }
    $value = htmlentities($dataSvc->getValue($key, $context));
    if ($this->formatter) {
      $value = $this->formatter->format($value, $raw_key, $raw);
    }
    return $value;
  }

  /**
   * Define the fields associated with the formatter.
   * @param string $key
   *   Field replacement id
   * @param $field
   *   Field definition.
   */
  public function defineField($key, $field) {
    $def = [];
    //Make sure attribute names don't have -
    foreach($field as $attr => $value) {
      $def[$attr] = $value;
    }
    $this->fields[$key] = $def;
  }

  /*
   * Formatter used by the syntax engine to alter data that gets extracted.
   * This invokes the field translation
   */
  public function format($value, $key, $raw=FALSE) {
    // Determine if there is a field overide entry
    $default='';
    $link ='';
    $format='';
    $format_string = '';
    $calc= '';
    $context = '';
    $field = [];
    if (isset($this->fields[$key])) {
      $field = $this->fields[$key];
      extract($field);
      if (isset($field['format-string'])) {
        $format_string = $field['format-string'];
      }
    }

    // Evaluate any calculations first.
    if ($calc) {
      if ($context) $context .= ".";
      $calc = $this->replace($calc, TRUE);
      if ($calc) $value = $this->replace('{' . $context . '=' . $calc . '}', TRUE);
    }

    //@TODO: Figure out how to deal with formatters.
    if ($format && !$raw) {

      if (!empty($this->format_callbacks[$format])) {
        $value = call_user_func(
          $this->format_callbacks[$format],
          $value,
          $format_string,
          $this,
          $default
        );
      };
      $value = trim($value);
    }

    if (is_array($value) && !$raw) {
      $value = implode(' ', $value);
    }

    // Default if specified
    if (!$value && $default) {
      $value = $default;
    }

    if ($link && !$raw) {
      $link_fields = array_merge(array_fill_keys(['link', 'target', 'rel', 'class', 'add_query'], ''), $field);
      $this->replaceNested($link_fields, TRUE);
      $value = AppService::instance()->reportLink($value, $link_fields);
    }
    return $value;
  }

}