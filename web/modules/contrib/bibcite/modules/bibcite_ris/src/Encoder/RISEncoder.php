<?php

namespace Drupal\bibcite_ris\Encoder;

use LibRIS\RISReader;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

/**
 * RIS format encoder.
 */
class RISEncoder implements EncoderInterface, DecoderInterface {

  /**
   * The format that this encoder supports.
   *
   * @var string
   */
  protected static $format = 'ris';

  /**
   * {@inheritdoc}
   */
  public function supportsDecoding($format) {
    return $format == static::$format;
  }

  /**
   * {@inheritdoc}
   */
  public function decode($data, $format, array $context = []) {
    /*
     * Workaround for weird behavior of "LibRIS" library.
     *
     * Replace LF line ends by CRLF.
     */
    $data = str_replace("\n", "\r\n", $data);

    $config = \Drupal::config('bibcite_entity.mapping.' . $format);
    $fields = $config->get('fields');
    $ris = new RISReader();
    $ris->parseString($data);
    $records = $ris->getRecords();

    // Workaround for weird behavior of "LibRIS" library.
    foreach ($records as &$record) {
      foreach ($record as $key => $value) {
        if (is_array($value) && count($value) == 1) {
          $record[$key] = reset($value);
        }
      }
      // Additional pages parsing.
      $pages_string = '';
      if (array_key_exists('SP', $record) || array_key_exists('EP', $record)) {
        if (array_key_exists('SP', $record)) {
          $record['SP'] = (array) $record['SP'];
        }
        if (array_key_exists('EP', $record)) {
          $record['EP'] = (array) $record['EP'];
        }
        $max_sp = array_key_exists('SP', $record) ? max($record['SP']) : NULL;
        $max_ep = array_key_exists('EP', $record) ? max($record['EP']) : 0;
        if ($max_sp && $max_sp > $max_ep) {
          $pages_string .= $max_sp . '+';
          array_splice($record['SP'], array_search($max_sp, $record['SP']), 1);
        }
        while ($max_ep) {
          $pages_string = $max_ep . ', ' . $pages_string;
          array_splice($record['EP'], array_search($max_ep, $record['EP']), 1);
          $max_ep = count($record['EP']) > 0 ? max($record['EP']) : NULL;
          $max_sp = array_key_exists('SP', $record) ? max($record['SP']) : NULL;
          if ($max_sp && (!$max_ep || $max_sp > $max_ep)) {
            $pages_string = $max_sp . '-' . $pages_string;
            array_splice($record['SP'], array_search($max_sp, $record['SP']), 1);
          }
        }
        $record['SP'] = $pages_string;
        $record['EP'] = $pages_string;
      }

      // From old format import fix.
      // User fields to custom.
      if (isset($record['U1']) && !isset($fields['U1']) && !isset($records['C1'])) {
        $record['C1'] = $record['U1'];
        unset($record['U1']);
      }
      if (isset($record['U2']) && !isset($fields['U2']) && !isset($records['C2'])) {
        $record['C2'] = $record['U2'];
        unset($record['U2']);
      }
      if (isset($record['U3']) && !isset($fields['U3']) && !isset($records['C3'])) {
        $record['C3'] = $record['U3'];
        unset($record['U3']);
      }
      if (isset($record['U4']) && !isset($fields['U4']) && !isset($records['C4'])) {
        $record['C4'] = $record['U4'];
        unset($record['U4']);
      }
      if (isset($record['U5']) && !isset($fields['U5']) && !isset($records['C5'])) {
        $record['C5'] = $record['U5'];
        unset($record['U5']);
      }
      // Year of publication.
      if (isset($record['Y1']) && !isset($fields['Y1']) && !isset($records['PY'])) {
        $record['PY'] = $record['Y1'];
        unset($record['Y1']);
      }
      // Titles.
      if ($this->checkKeys(['TI', 'T1', 'ST', 'CT', 'BT'], $record)) {
        $title = $record['TI'];
        if ($title === $record['T1'] && $title === $record['ST'] && $title === $record['CT'] && $title === $record['BT']) {
          unset($record['T1']);
          unset($record['ST']);
          unset($record['CT']);
          unset($record['BT']);
        }
      }
      // Issue.
      if ($this->checkKeys(['CP', 'IS'], $record) && $record['CP'] === $record['IS']) {
        unset($record['CP']);
      }
      // Short title.
      if ($this->checkKeys(['J1', 'J2', 'JO'], $record) && $record['J1'] === $record['J2'] && $record['J1'] === $record['JO']) {
        $record['ST'] = $record['J1'];
        unset($record['J1']);
        unset($record['J2']);
        unset($record['JO']);
      }
      // Secondary title.
      if ($this->checkKeys(['JA', 'JF', 'T2'], $record) && $record['JA'] === $record['JF'] && $record['JA'] === $record['T2']) {
        unset($record['JA']);
        unset($record['JF']);
      }
      // Abstract.
      if ($this->checkKeys(['AB', 'N2'], $record) && $record['AB'] && $record['AB'] === $record['N2']) {
        unset($record['N2']);
      }
    }

    if (count($records) === 0) {
      $format_definition = \Drupal::service('plugin.manager.bibcite_format')->getDefinition($format);
      throw new \Exception(t("Incorrect @format format or empty set.", ['@format' => $format_definition['label']]));
    }

    return $records;
  }

  /**
   * Check if keys in array.
   *
   * @param array $keys
   *   Array of keys.
   * @param array $array
   *   Array to check.
   *
   * @return bool
   *   Result of checking.
   */
  private function checkKeys($keys, $array) {
    $result = TRUE;
    foreach ($keys as $key) {
      $result &= array_key_exists($key, $array);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding($format) {
    return $format == static::$format;
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = []) {
    if (isset($data['TY'])) {
      $data = [$data];
    }

    $data = array_map(function ($raw) {
      return $this->buildEntry($raw);
    }, $data);

    return implode("\n", $data);
  }

  /**
   * Build RIS entry string.
   *
   * @param array $data
   *   Array of RIS values.
   *
   * @return string
   *   Formatted RIS string.
   */
  protected function buildEntry(array $data) {
    $entry = NULL;
    // For not duplicating pages parse.
    $pages_parsed = FALSE;
    foreach ($data as $key => $value) {
      switch ($key) {
        // Pages found.
        case "SP":
        case "EP":
          if (!$pages_parsed) {
            $pages_parsed = TRUE;
            $exp = explode(',', trim($value));
            foreach ($exp as $page) {
              // Interval of pages.
              if (strpos(trim($page), '-') !== FALSE) {
                $interval = explode('-', trim($page));
                $entry .= $this->buildLine('SP', $interval[0]);
                $entry .= $this->buildLine('EP', $interval[count(@$interval) - 1]);
              } else {
                // From here to the end.
                if ($page[strlen(trim($page)) - 1] === '+') {
                  $entry .= $this->buildLine('SP', trim($page, ' +'));
                } else {
                  // Single page.
                  $entry .= $this->buildLine('EP', trim($page));
                }
              }
            }
          }
          break;

        // Not pages.
        default:
          if (is_array($value)) {
            $entry .= $this->buildMultiLine($key, $value);
          } else {
            $entry .= $this->buildLine($key, $value);
          }
          break;
      }
    }

    $entry .= $this->buildEnd();

    return $entry;
  }

  /**
   * Build multi line entry.
   *
   * @param string $key
   *   Line key.
   * @param array $value
   *   Array of multi line values.
   *
   * @return string
   *   Multi line entry.
   */
  protected function buildMultiLine($key, array $value) {
    $lines = '';

    foreach ($value as $item) {
      $lines .= $this->buildLine($key, $item);
    }

    return $lines;
  }

  /**
   * Build entry line.
   *
   * @param string $key
   *   Line key.
   * @param string $value
   *   Line value.
   *
   * @return string
   *   Entry line.
   */
  protected function buildLine($key, $value) {
    return $key . ' - ' . $value . "\n";
  }

  /**
   * Build the end of RIS entry.
   *
   * @return string
   *   End line for the RIS entry.
   */
  protected function buildEnd() {
    return $this->buildLine('ER', '');
  }

}
