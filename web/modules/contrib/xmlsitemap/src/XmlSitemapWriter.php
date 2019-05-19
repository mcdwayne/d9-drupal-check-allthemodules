<?php

namespace Drupal\xmlsitemap;

use Drupal\Component\Utility\Html;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Extended class for writing XML sitemap files.
 */
class XmlSitemapWriter extends \XMLWriter {

  /**
   * Document URI.
   *
   * @var string
   */
  protected $uri = NULL;

  /**
   * Counter for the sitemap elements.
   *
   * @var int
   */
  protected $sitemapElementCount = 0;

  /**
   * Flush counter for sitemap links.
   *
   * @var int
   */
  protected $linkCountFlush = 500;

  /**
   * Sitemap object to be written.
   *
   * @var \Drupal\xmlsitemap\XmlSitemapInterface
   */
  protected $sitemap;

  /**
   * Sitemap page to be written.
   *
   * @var int|string
   */
  protected $page;

  /**
   * Constructors and XmlSitemapWriter object.
   *
   * @param \Drupal\xmlsitemap\XmlSitemapInterface $sitemap
   *   The XML sitemap.
   * @param int|string $page
   *   The current page of the sitemap being generated.
   *
   * @throws \InvalidArgumentException
   *   If the page is invalid.
   * @throws \Drupal\xmlsitemap\XmlSitemapGenerationException
   *   If the file URI cannot be opened.
   */
  public function __construct(XmlSitemapInterface $sitemap, $page) {
    if ($page !== 'index' && !filter_var($page, FILTER_VALIDATE_INT)) {
      throw new \InvalidArgumentException("Invalid XML sitemap page $page.");
    }

    $this->sitemap = $sitemap;
    $this->page = $page;
    $this->uri = xmlsitemap_sitemap_get_file($sitemap, $page);
    $this->openUri($this->uri);
  }

  /**
   * Opens and uri.
   *
   * @param string $uri
   *   Uri to be opened.
   *
   * @return bool
   *   Returns TRUE when uri was successfully opened.
   *
   * @throws XmlSitemapGenerationException
   *   If the file URI cannot be opened.
   */
  public function openUri($uri) {
    $return = parent::openUri($uri);
    if (!$return) {
      throw new XmlSitemapGenerationException(t('Could not open file @file for writing.', ['@file' => $uri]));
    }
    return $return;
  }

  /**
   * Starts an XML document.
   *
   * @param string $version
   *   The version number of the document.
   * @param string $encoding
   *   The encoding of the document.
   * @param string $standalone
   *   Yes or No.
   *
   * @throws XmlSitemapGenerationException
   *   Throws exception when document cannot be started.
   *
   * @return bool
   *   Returns TRUE on success.
   */
  public function startDocument($version = '1.0', $encoding = 'UTF-8', $standalone = NULL) {
    $this->setIndent(FALSE);
    $result = parent::startDocument($version, $encoding);
    if (!$result) {
      throw new XmlSitemapGenerationException(t('Unknown error occurred while writing to file @file.', ['@file' => $this->uri]));
    }
    if (\Drupal::config('xmlsitemap.settings')->get('xsl')) {
      $this->writeXSL();
    }
    $this->startElement($this->isIndex() ? 'sitemapindex' : 'urlset', TRUE);
    return $result;
  }

  /**
   * Adds the XML stylesheet to the XML page.
   */
  public function writeXSL() {
    $xls_url = Url::fromRoute('xmlsitemap.sitemap_xsl')->toString();
    $settings = \Drupal::config('language.negotiation');
    if ($settings) {
      $url_settings = $settings->get('url');
      if (isset($url_settings['source']) && $url_settings['source'] == 'domain') {
        $scheme = \Drupal::request()->getScheme();
        $context = $this->sitemap->getContext();
        $base_url = $scheme . '://' . $url_settings['domains'][$context['language']];
        $xls_url = Url::fromRoute('xmlsitemap.sitemap_xsl');
        $xls_url = $base_url . '/' . $xls_url->getInternalPath();
      }
    }
    $this->writePi('xml-stylesheet', 'type="text/xsl" href="' . $xls_url . '"');
    $this->writeRaw(PHP_EOL);
  }

  /**
   * Return an array of attributes for the root element of the XML.
   *
   * @return array
   *   Returns root attributes.
   */
  public function getRootAttributes() {
    $attributes['xmlns'] = 'http://www.sitemaps.org/schemas/sitemap/0.9';
    if (\Drupal::state()->get('xmlsitemap_developer_mode')) {
      $attributes['xmlns:xsi'] = 'http://www.w3.org/2001/XMLSchema-instance';
      $attributes['xsi:schemaLocation'] = 'http://www.sitemaps.org/schemas/sitemap/0.9';
      if ($this->isIndex()) {
        $attributes['xsi:schemaLocation'] .= ' http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd';
      }
      else {
        $attributes['xsi:schemaLocation'] .= ' http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd';
      }
    }

    \Drupal::moduleHandler()->alter('xmlsitemap_root_attributes', $attributes, $this->sitemap);

    return $attributes;
  }

  /**
   * Creates start element tag.
   *
   * @param string $name
   *   Element name.
   * @param bool $root
   *   Specify if it is root element or not.
   */
  public function startElement($name, $root = FALSE) {
    parent::startElement($name);

    if ($root) {
      foreach ($this->getRootAttributes() as $name => $value) {
        $this->writeAttribute($name, $value);
      }
      $this->writeRaw(PHP_EOL);
    }
  }

  /**
   * Writes an full XML sitemap element tag.
   *
   * @param string $name
   *   The element name.
   * @param array $element
   *   An array of the elements properties and values.
   *
   * @deprecated Use \Drupal\xmlsitemap\XmlSitemapWriter::writeElement().
   */
  public function writeSitemapElement($name, array $element) {
    $this->writeElement($name, $element);
  }

  /**
   * Writes full element tag including support for nested elements.
   *
   * @param string $name
   *   The element name.
   * @param string|array $content
   *   The element contents or an array of the elements' sub-elements.
   */
  public function writeElement($name, $content = NULL) {
    if (is_array($content)) {
      $this->startElement($name);
      $this->writeRaw($this->formatXmlElements($content));
      $this->endElement();
    }
    else {
      parent::writeElement($name, Html::escape(static::toString($content)));
    }
    $this->writeRaw(PHP_EOL);

    // After a certain number of elements have been added, flush the buffer
    // to the output file.
    $this->sitemapElementCount++;
    if (($this->sitemapElementCount % $this->linkCountFlush) == 0) {
      $this->flush();
    }
  }

  /**
   * Getter of the document uri.
   *
   * @return string
   *   Document uri.
   *
   * @codingStandardsIgnoreStart
   */
  public function getURI() {
    // @codingStandardsIgnoreEnd
    return $this->uri;
  }

  /**
   * Getter of the element count.
   *
   * @return int
   *   Element counters.
   */
  public function getSitemapElementCount() {
    return $this->sitemapElementCount;
  }

  /**
   * Ends an XML document.
   *
   * @throws XmlSitemapGenerationException
   *
   * @return bool
   *   Returns TRUE on success.
   */
  public function endDocument() {
    $return = parent::endDocument();

    if (!$return) {
      throw new XmlSitemapGenerationException(t('Unknown error occurred while writing to file @file.', ['@file' => $this->uri]));
    }

    if (xmlsitemap_var('gz')) {
      $file_gz = $this->uri . '.gz';
      file_put_contents($file_gz, gzencode(file_get_contents($this->uri), 9));
    }

    return $return;
  }

  /**
   * If the page being written is the index.
   *
   * @return bool
   *   TRUE if the sitemap index is being written, or FALSE otherwise.
   */
  protected function isIndex() {
    return $this->page === 'index';
  }

  /**
   * Copy of Drupal 7's format_xml_elements() function.
   *
   * The extra whitespace has been removed.
   *
   * @param $array
   *
   * @return string
   */
  public static function formatXmlElements($array) {
    $output = '';
    foreach ($array as $key => $value) {
      if (is_numeric($key)) {
        if ($value['key']) {
          $output .= '<' . $value['key'];
          if (isset($value['attributes'])) {
            if (is_array($value['attributes'])) {
              $value['attributes'] = new Attribute($value['attributes']);
            }
            $output .= static::toString($value['attributes']);
          }
          if (isset($value['value']) && $value['value'] != '') {
            $output .= '>' . (is_array($value['value']) ? static::formatXmlElements($value['value']) : Html::escape(static::toString($value['value']))) . '</' . $value['key'] . ">";
          }
          else {
            $output .= " />";
          }
        }
      }
      else {
        $output .= '<' . $key . '>' . (is_array($value) ? static::formatXmlElements($value) : Html::escape(static::toString($value))) . "</{$key}>";
      }
    }
    return $output;
  }

  /**
   * Convert translatable strings and URLs to strings.
   *
   * @param mixed $value
   *   The value to turn into a string.
   *
   * @return string
   */
  public static function toString($value) {
    if (is_object($value)) {
      if ($value instanceof Url) {
        return $value->toString();
      }
    }

    return (string) $value;
  }

}
