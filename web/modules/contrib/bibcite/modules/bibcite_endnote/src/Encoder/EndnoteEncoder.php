<?php

namespace Drupal\bibcite_endnote\Encoder;

use SimpleXMLElement;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

/**
 * Endnote format encoder.
 *
 * @todo Refactor this class.
 */
class EndnoteEncoder implements EncoderInterface, DecoderInterface {

  /**
   * The format that this encoder supports.
   *
   * @var array
   */
  protected static $format = ['endnote7', 'endnote8', 'tagged'];

  /**
   * {@inheritdoc}
   */
  public function supportsDecoding($format) {
    return in_array($format, static::$format);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function decode($data, $format, array $context = []) {
    if ($format === 'tagged') {
      return $this->decodeTagged($data);
    }
    $result = [];
    try {
      $sxml = new SimpleXMLElement($data);
    }
    catch (\Exception $ex) {
      $format_definition = \Drupal::service('plugin.manager.bibcite_format')->getDefinition($format);
      throw new \Exception(t('Incorrect @format format.', ['@format' => $format_definition['label']]));
    }
    $records = $sxml->records;
    $config = \Drupal::config('bibcite_entity.mapping.' . $format);
    $indexes = $config->get('indexes');
    if ($records instanceof SimpleXMLElement) {
      foreach ($records->children() as $record) {
        $rec = [];
        if ($record instanceof SimpleXMLElement) {
          foreach ($record->children() as $child) {
            if ($child instanceof SimpleXMLElement) {
              switch ($child->getName()) {
                case'REFERENCE_TYPE':
                case'ref-type':
                  if (strlen($child->__toString()) > 0) {
                    $type = array_search($child->__toString(), $indexes);
                    if ($type) {
                      $rec['type'] = $type;
                    }
                    else {
                      $rec['type'] = -1;
                    }
                  }
                  break;

                case'DATES':
                case 'dates':
                  foreach ($child->children() as $dates) {
                    if ($dates instanceof SimpleXMLElement) {
                      switch ($dates->getName()) {
                        case'YEAR':
                        case'year':
                          $rec[$dates->getName()] = $this->getString($dates);
                          break;

                        case'DATE':
                        case'date':
                          $rec[$dates->{'pub-dates'}->getName()] = $this->getString($dates->{'pub-dates'});
                          break;
                      }
                    }
                  }
                  break;

                case'CONTRIBUTORS':
                case'contributors':
                  foreach ($child->children() as $authors) {
                    if ($authors instanceof SimpleXMLElement) {
                      foreach ($authors->children() as $author) {
                        $rec[$authors->getName()][] = $this->getString($author);
                      }
                    }
                  }
                  break;

                case'TITLES':
                case'titles':
                  foreach ($child->children() as $title) {
                    if ($title instanceof SimpleXMLElement) {
                      $rec[$title->getName()] = $this->getString($title);
                    }
                  }
                  break;

                case'KEYWORDS':
                case'keywords':
                  foreach ($child->children() as $keyword) {
                    if ($keyword instanceof SimpleXMLElement) {
                      $rec['keywords'][] = $this->getString($keyword);
                    }
                  }
                  break;

                case'source-app':
                  break;

                default:
                  $rec[$child->getName()] = $this->getString($child);
                  break;
              }
            }
          }
        }
        $result[] = $rec;
      }
    }
    return $result;
  }

  /**
   * Get string from element.
   *
   * @param \SimpleXMLElement $element
   *   Element value.
   *
   * @return string
   *   Result string.
   */
  private function getString($element) {
    return $element->style->__toString() ?: $element->__toString();
  }

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding($format) {
    return in_array($format, static::$format);
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = []) {
    if ($format === 'tagged') {
      return $this->encodeTagged($data);
    }
    $sxml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><xml><records></records></xml>');
    $records = $sxml->records;
    foreach ($data as $id => $ref) {
      if ($records instanceof SimpleXMLElement) {
        switch ($format) {
          case'endnote7':
            $record = $records->addChild('RECORD');
            $contrib_key = 'CONTRIBUTORS';
            $author_key = 'AUTHOR';
            $titles_key = 'TITLES';
            $keywords_key = 'KEYWORDS';
            $dates_key = 'DATES';
            $web_key = 'WEB-URLS';
            $pub_key = 'PUB-DATES';
            $keyword_key = 'KEYWORD';
            $authors_key = 'AUTHORS';
            break;

          case'endnote8':
          default:
            $record = $records->addChild('record');
            $contrib_key = 'contributors';
            $author_key = 'author';
            $titles_key = 'titles';
            $keywords_key = 'keywords';
            $dates_key = 'dates';
            $web_key = 'web-urls';
            $pub_key = 'pub-dates';
            $keyword_key = 'keyword';
            $authors_key = 'authors';
            break;
        }
        $source = $record->addChild('source-app', 'Drupal-Bibcite');
        $source->addAttribute('name', 'Bibcite');
        $source->addAttribute('version', '8.x');

        $config = \Drupal::config('bibcite_entity.mapping.' . $format);
        $indexes = $config->get('fields');
        $ref['type_en8id'] = $config->get('indexes')[$ref['type']];

        $type_key = array_search('type', $indexes);

        if ($type_key) {
          $record->addChild($type_key, $ref['type_en8id']);
        }
        unset($ref[$type_key]);
        unset($ref['type']);
        unset($ref['type_en8id']);
        unset($ref['reference']);

        if ($authors_key) {
          $authors = $record->addChild($contrib_key)->addChild($authors_key);
          if (isset($ref[$authors_key])) {
            foreach ($ref[$authors_key] as $author) {
              $author_xml = $authors->addChild($author_key);
              $this->setStyledText($author_xml, $author);
            }
            unset($ref[$authors_key]);
          }
        }

        $titles = $record->addChild($titles_key);
        $this->addTitles($titles, $ref, $indexes);

        $keywords = $record->addChild($keywords_key);
        $this->addKeywords($keywords, $ref, $indexes, $keyword_key);
        unset($ref[$keywords_key]);

        $dates = $record->addChild($dates_key);
        $this->addDates($dates, $ref, $indexes, $pub_key);

        $urls_key = array_search('urls', $indexes);
        if (isset($ref[$urls_key])) {
          $this->addTag($record->addChild($web_key), $urls_key, $ref[$urls_key]);
          unset($ref[$urls_key]);
        }

        // Only in endnote X3.
        if (isset($ref['full-title'])) {
          $this->addTag($record->addChild('periodical'), 'full-title', $ref['full-title']);
          unset($ref['full-title']);
        }

        $this->addFields($record, $ref);
      }
    }
    return $sxml->asXML();
  }

  /**
   * Add titles to xml.
   *
   * @param \SimpleXMLElement $xml
   *   Parent XmlElement.
   * @param mixed $ref
   *   Our reference.
   * @param array $indexes
   *   Mapping indexes.
   */
  private function addTitles(&$xml, &$ref, $indexes) {
    foreach ($ref as $key => $value) {
      if (array_key_exists($key, $indexes)) {
        $title_key = $indexes[$key];
        switch ($title_key) {
          case 'title':
          case 'secondary-title':
          case 'tertiary-title':
          case 'alt-title':
          case 'short-title':
          case 'translated-title':
            $this->addTag($xml, $key, $value);
            unset($ref[$key]);
            break;
        }
      }
    }
  }

  /**
   * Add keywords to xml.
   *
   * @param \SimpleXMLElement $xml
   *   Parent XmlElement.
   * @param mixed $ref
   *   Our reference.
   * @param array $indexes
   *   Mapping indexes.
   */
  private function addKeywords(&$xml, &$ref, $indexes, $keyword_key) {
    foreach ($ref as $key => $value) {
      switch ($key) {
        case 'keywords':
          foreach ($ref[$key] as $keyword) {
            $this->addTag($xml, $keyword_key, $keyword);
          }
          unset($ref[$key]);
          break;
      }
    }
  }

  /**
   * Add dates to xml.
   *
   * @param \SimpleXMLElement $xml
   *   Parent XmlElement.
   * @param mixed $ref
   *   Our reference.
   * @param array $indexes
   *   Mapping indexes.
   * @param string $pub_key
   *   Key for pub-date.
   */
  private function addDates(&$xml, &$ref, $indexes, $pub_key) {
    foreach ($ref as $key => $value) {
      switch ($key) {
        case 'date':
          $date = $xml->addChild($pub_key);
          $this->addTag($date, $key, $value);
          unset($ref[$key]);
          break;

        case 'year':
          $this->addTag($xml, $key, $value);
          unset($ref[$key]);
          break;
      }
    }
  }

  /**
   * Add fields to xml.
   *
   * @param \SimpleXMLElement $xml
   *   Parent XmlElement.
   * @param mixed $ref
   *   Our reference.
   */
  private function addFields(&$xml, &$ref) {
    foreach ($ref as $key => $value) {
      $this->addTag($xml, $key, $value);
      unset($ref[$key]);
    }
  }

  /**
   * Add value to xml tag.
   *
   * @param \SimpleXMLElement $xml
   *   Parent XmlElement.
   * @param string $tag
   *   Xml tag to add.
   * @param string $value
   *   Text to set.
   */
  private function addTag(&$xml, $tag, $value) {
    $xc = $xml->addChild($tag);
    $this->setStyledText($xc, $value);
  }

  /**
   * Add xml child style.
   *
   * @param \SimpleXMLElement $xml
   *   Parent XmlElement.
   * @param string $text
   *   Text to set.
   */
  private function setStyledText(&$xml, $text) {
    $styled = $xml->addChild('style', $text);
    $styled->addAttribute('face', 'normal');
    $styled->addAttribute('font', 'default');
    $styled->addAttribute('size', '100%');
  }

  /**
   * Decode tagged format function.
   */
  private function decodeTagged($data) {
    $result = [];

    $config = \Drupal::config('bibcite_entity.mapping.tagged');
    $indexes = $config->get('indexes');
    $data = explode("\n\n", $data);
    foreach ($data as $i => &$record) {
      $fields = explode("\n", $record);
      foreach ($fields as $field) {
        $key = array_search(substr($field, 0, 2), $indexes);
        $value = substr($field, 3);
        if ($key) {
          if (isset($result[$i][$key])) {
            if (is_array($result[$i][$key])) {
              $result[$i][$key][] = $value;
            }
            else {
              $val = $result[$i][$key];
              unset($result[$i][$key]);
              $result[$i][$key][] = $val;
              $result[$i][$key][] = $value;
            }
          }
          else {
            $result[$i][$key] = $value;
          }
        }
      }
    }
    return $result;
  }

  /**
   * Encode tagged format function.
   */
  private function encodeTagged($data) {
    if (isset($data['type'])) {
      $data = [$data];
    }

    $data = array_map(function ($raw) {
      return $this->buildEntry($raw);
    }, $data);

    return implode("\n", $data);
  }

  /**
   * Build tagged entry string.
   *
   * @param array $data
   *   Array of tagged values.
   *
   * @return string
   *   Formatted tagged string.
   */
  protected function buildEntry(array $data) {
    $config = \Drupal::config('bibcite_entity.mapping.tagged');
    $indexes = $config->get('indexes');
    $entry = NULL;
    // For not duplicating pages parse.
    $pages_parsed = FALSE;
    foreach ($data as $key => $value) {
      if (is_array($value)) {
        if (isset($indexes[$key])) {
          $entry .= $this->buildMultiLine($indexes[$key], $value);
        }
      }
      else {
        if (isset($indexes[$key])) {
          $entry .= $this->buildLine($indexes[$key], $value);
        }
      }
    }

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
    return $key . ' ' . $value . "\n";
  }

}
