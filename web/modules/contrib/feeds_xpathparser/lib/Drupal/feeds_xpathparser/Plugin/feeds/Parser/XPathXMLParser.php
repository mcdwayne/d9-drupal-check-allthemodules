<?php

/**
 * @file
 * Contains \Drupal\feeds_xpathparser\Plugin\feeds\Parser\XPathXMLParser.
 */

namespace Drupal\feeds_xpathparser\Plugin\feeds\Parser;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\feeds\FetcherResultInterface;
use Drupal\feeds_xpathparser\ParserBase;

/**
 * Defines an XML feed parser.
 *
 * @Plugin(
 *   id = "feeds_xpathparser_xml",
 *   title = @Translation("XPath XML parser"),
 *   description = @Translation("Parse XML files using XPath.")
 * )
 */
class XPathXMLParser extends ParserBase {

  /**
   * {@inheritdoc}
   */
  protected function setup(array $feed_config, FetcherResultInterface $fetcher_result) {

    if (!empty($feed_config['tidy'])) {
      $config = array(
        'input-xml' => TRUE,
        'wrap'      => 0,
        'tidy-mark' => FALSE,
      );
      // Default tidy encoding is UTF8.
      $encoding = $feed_config['tidy_encoding'];
      $raw = tidy_repair_string(trim($fetcher_result->getRaw()), $config, $encoding);
    }
    else {
      $raw = $fetcher_result->getRaw();
    }
    $doc = new \DOMDocument();
    $use = $this->errorStart();
    $success = $doc->loadXML($raw);
    unset($raw);
    $this->errorStop($use, $feed_config['errors']);
    if (!$success) {
      throw new \RuntimeException(t('There was an error parsing the XML document.'));
    }

    return $doc;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRaw(\DOMNode $node) {
    return $this->doc->saveXML($node);
  }

}
