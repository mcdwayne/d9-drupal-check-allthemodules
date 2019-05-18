<?php

namespace Drupal\bibcite_marc\Encoder;

use PhpMarc\Field;
use PhpMarc\File;
use PhpMarc\Record;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

/**
 * Marc format encoder.
 */
class MarcEncoder implements EncoderInterface, DecoderInterface {

  /**
   * The format that this encoder supports.
   *
   * @var string
   */
  protected static $format = 'marc';

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
    $parsed = [];
    $records = explode(File::END_OF_RECORD, $data);
    foreach ($records as $record) {
      if (strlen($record) > 0) {
        $rec = $this->decodeFile($record . File::END_OF_RECORD);
        $fields = $rec->fields();
        $leader = $rec->leader();
        $pubtype = $leader[6];
        $pubtype .= $leader[7];
        $fields['type'] = $pubtype === 'am' ? 'book' : 'misc';
        $parsed[] = $fields;
      }
    }

    $keys = array_keys($parsed);
    if (count($keys) === 0 || $keys[0] === -1) {
      $format_definition = \Drupal::service('plugin.manager.bibcite_format')->getDefinition($format);
      throw new \Exception(t("Incorrect @format format or empty set.", ['@format' => $format_definition['label']]));
    }
    $this->processEntries($parsed);

    return $parsed;
  }

  /**
   * Croaking function.
   *
   * Similar to Perl's croak function, which ends parsing and raises an
   * user error with a descriptive message.
   *
   * @param string $msg
   *   The message to display.
   */
  private function croak($msg) {
    trigger_error($msg, E_USER_ERROR);
  }

  /**
   * Decode a given raw MARC record.
   *
   * "Port" of Andy Lesters MARC::File::USMARC->decode() function into PHP.
   * Ideas and
   * "rules" have been used from USMARC::decode().
   *
   * @param string $text
   *   MARC record.
   *
   * @return Record
   *   Record Decoded MARC Record object
   */
  private function decodeFile($text) {
    if (!preg_match("/^\d{5}/", $text, $matches)) {
      $this->croak('Record length "' . substr($text, 0, 5) . '" is not numeric');
    }

    $marc = new Record();
    // Store record length.
    $reclen = $matches[0];

    if ($reclen != strlen($text)) {
      $this->croak("Invalid record length: Leader says $reclen bytes, but it's actually " . strlen($text));
    }

    if (substr($text, -1, 1) != File::END_OF_RECORD) {
      $this->croak("Invalid record terminator");
    }

    // Store leader.
    $marc->leader(substr($text, 0, File::LEADER_LEN));

    // Bytes 12 - 16 of leader give offset to the body of the record.
    $data_start = 0 + substr($text, 12, 5);

    // Immediately after the leader comes the directory (no separator)
    // -1 to allow for \x1e at end of directory.
    $dir = substr($text, File::LEADER_LEN, $data_start - File::LEADER_LEN - 1);

    if (substr($text, $data_start - 1, 1) != File::END_OF_FIELD) {
      $this->croak("No directory found");
    }

    // All directory entries 12 bytes long, so length % 12 must be 0.
    if (strlen($dir) % File::DIRECTORY_ENTRY_LEN != 0) {
      $this->croak("Invalid directory length");
    }

    // Go through all the fields.
    $nfields = strlen($dir) / File::DIRECTORY_ENTRY_LEN;
    for ($n = 0; $n < $nfields; $n++) {
      // As pack returns to key 1, leave place 0 in list empty.
      list(, $tagno) = unpack("A3", substr($dir, $n * File::DIRECTORY_ENTRY_LEN, File::DIRECTORY_ENTRY_LEN));
      list(, $len) = unpack("A3/A4", substr($dir, $n * File::DIRECTORY_ENTRY_LEN, File::DIRECTORY_ENTRY_LEN));
      list(, $offset) = unpack("A3/A4/A5", substr($dir, $n * File::DIRECTORY_ENTRY_LEN, File::DIRECTORY_ENTRY_LEN));

      // Check directory validity.
      if (!preg_match("/^[0-9A-Za-z]{3}$/", $tagno)) {
        $this->croak("Invalid tag in directory: \"$tagno\"");
      }
      if (!preg_match("/^\d{4}$/", $len)) {
        $this->croak("Invalid length in directory, tag $tagno: \"$len\"");
      }
      if (!preg_match("/^\d{5}$/", $offset)) {
        $this->croak("Invalid offset in directory, tag $tagno: \"$offset\"");
      }
      if ($offset + $len > $reclen) {
        $this->croak("Directory entry runs off the end of the record tag $tagno");
      }

      $tagdata = substr($text, $data_start + $offset, $len);

      if (substr($tagdata, -1, 1) == File::END_OF_FIELD) {
        // Get rid of the end-of-tag character.
        $tagdata = substr($tagdata, 0, -1);
        $len--;
      }
      else {
        $this->croak("field does not end in end of field character in tag $tagno");
      }

      if (preg_match("/^\d+$/", $tagno) && ($tagno < 10)) {
        $marc->append_fields(new Field($tagno, $tagdata));
      }
      else {
        $subfields = @preg_split('/' . File::SUBFIELD_INDICATOR . '/', $tagdata);
        $indicators = array_shift($subfields);

        if (strlen($indicators) > 2 || strlen($indicators) == 0) {
          //$this->_warn("Invalid indicators \"$indicators\" forced to blanks for tag $tagno\n");
          list($ind1, $ind2) = [" ", " "];
        }
        else {
          $ind1 = substr($indicators, 0, 1);
          $ind2 = substr($indicators, 1, 1);
        }

        // Split the subfield data into subfield name and data pairs.
        $subfield_data = [];
        foreach ($subfields as $subfield) {
          if (strlen($subfield) > 0) {
            $subfield_data[substr($subfield, 0, 1)] = substr($subfield, 1);
          } /*else {
            $this->_warn( "Entirely empty subfield found in tag $tagno" );
          }*/
        }

        /*if (!isset($subfield_data)) {
          $this->_warn( "No subfield data found $location for tag $tagno" );
        }*/

        $marc->append_fields(new Field($tagno, $ind1, $ind2, $subfield_data));
      }
    }
    return $marc;
  }

  /**
   * Workaround about some things in MarcParser library.
   *
   * @param array $parsed
   *   List of parsed entries.
   */
  protected function processEntries(array &$parsed) {
    $config = \Drupal::config('bibcite_entity.mapping.marc');
    $indexes = $config->get('indexes');
    foreach ($parsed as &$entry) {
      if (count($entry) > 0) {
        $entry['year'] = substr($entry['008'][0]->data, 7, 4);
        $entry['lang'] = substr($entry['008'][0]->data, 35, 3);
        foreach ($entry as $key => $value) {
          switch ($key) {
            case'000':
            case'008':
              unset($entry[$key]);
              break;

            case'100':
            case'700':
              $entity_key = 'authors';
              foreach ($value as $i => $field) {
                if ($field instanceof Field) {
                  foreach ($field->subfields as $j => $subfield) {
                    if ($entity_key) {
                      $entry[$entity_key][] = $subfield;
                    }
                  }
                }
              }
              unset($entry[$key]);
              break;

            default:
              if (is_array($value)) {
                foreach ($value as $i => $field) {
                  if ($field instanceof Field) {
                    foreach ($field->subfields as $j => $subfield) {
                      $entity_key = array_search(implode('_', [
                        $key,
                        $field->ind1 === ' ' ? '' : $field->ind1,
                        $field->ind2 === ' ' ? '' : $field->ind2,
                        $j,
                      ]), $indexes);
                      if (!$entity_key) {
                        $entity_key = array_search(implode('_', [
                          $key,
                          $field->ind1 === ' ' ? '' : $field->ind1,
                          $field->ind2 === ' ' ? '#' : $field->ind2,
                          $j,
                        ]), $indexes);
                      }
                      if ($entity_key) {
                        if (isset($entry[$entity_key])) {
                          if (!is_array($entry[$entity_key])) {
                            $val = $entry[$entity_key];
                            unset($entry[$entity_key]);
                            $entry[$entity_key][] = $val;
                          }
                          $entry[$entity_key][] = $subfield;
                        }
                        else {
                          $entry[$entity_key] = $subfield;
                        }
                      }
                    }
                  }
                }
                unset($entry[$key]);
              }
              break;
          }
        }
      }
    }
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
    if (isset($data['type'])) {
      $data = [$data];
    }

    $data = array_map(function ($raw) {
      return $this->buildEntry($raw);
    }, $data);

    return implode("", $data);
  }

  /**
   * Build Marc entry string.
   *
   * @param array $data
   *   Array of Marc values.
   *
   * @return string
   *   Formatted Marc string.
   */
  protected function buildEntry(array $data) {
    $record = new Record();
    $leader = $record->leader();
    $record->append_fields(new Field());

    if ($data['type'] == 'book') {
      $type = 'nam a';
    }
    else {
      $type = 'nas a';
    }

    unset($data['type']);
    unset($data['reference']);

    $record->leader(substr_replace($leader, $type, 5, 5));

    $rec_eight = str_repeat(' ', 40);
    if (isset($data['year'])) {
      $rec_eight = substr_replace($rec_eight, $data['year'], 7, 4);
    }
    if (isset($data['lang'])) {
      $rec_eight = substr_replace($rec_eight, $data['lang'], 35, 3);
    }
    $rec_eight = substr_replace($rec_eight, 'd', 39, 1);
    $field = new Field("008", $rec_eight);
    $record->append_fields($field);
    unset($data['year']);
    unset($data['lang']);

    $config = \Drupal::config('bibcite_entity.mapping.marc');
    $indexes = $config->get('indexes');

    foreach ($data as $key => $value) {
      switch ($key) {
        case 'publisher':
        case 'pub-location':
        case 'date':
          $type = explode('_', $indexes[$key]);
          if ($type) {
            $subfields[$type[3]] = $value;
          }
          unset($data[$key]);
      }
    }

    if (isset($subfields)) {
      $field = new Field(explode('_', $indexes['publisher'])[0], '', '', $subfields);
      $record->append_fields($field);
    }

    foreach ($data as $key => $value) {
      $index = explode('_', $indexes[$key]);
      if ($index) {
        switch ($key) {

          case 'authors':
            foreach ($value as $i => $author) {
              $tag = ($i == 0 ? 100 : 700);
              $field = new Field($tag, $index[1], $index[2], [$index[3] => $author]);
              $record->append_fields($field);
            }
            break;

          case'keywords':
            foreach ($value as $keyword) {
              $field = new Field($index[0], $index[1], $index[2], [$index[3] => $keyword]);
              $record->append_fields($field);
            }
            break;

          default:
            $field = new Field($index[0], $index[1], $index[2], [$index[3] => $value]);
            $record->append_fields($field);
            break;
        }
      }
      unset($data[$key]);
    }

    return $record->raw();
  }

}
