<?php

namespace Drupal\domain_simple_sitemap;

use Drupal\domain\Entity\Domain;
use Drupal\simple_sitemap\SitemapGenerator;
use Drupal\simple_sitemap\EntityHelper;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Component\Datetime\Time;
use Drupal\simple_sitemap\SitemapWriter;
use Drupal\domain\DomainNegotiatorInterface;

/**
 * Class DomainSimpleSitemapGenerator.
 *
 * @package Drupal\domain_simple_sitemap
 */
class DomainSimpleSitemapGenerator extends SitemapGenerator {

  /**
   * The domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * SitemapGenerator constructor.
   * @param \Drupal\simple_sitemap\EntityHelper $entityHelper
   * @param \Drupal\Core\Database\Connection $database
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   * @param \Drupal\Component\Datetime\Time $time
   * @param \Drupal\simple_sitemap\SitemapWriter $sitemapWriter
   * @param \Drupal\domain\DomainNegotiatorInterface $domain_negotiator
   */
  public function __construct(
    EntityHelper $entityHelper,
    Connection $database,
    ModuleHandler $module_handler,
    LanguageManagerInterface $language_manager,
    Time $time,
    SitemapWriter $sitemapWriter,
    DomainNegotiatorInterface $domain_negotiator
  ) {
    parent::__construct($entityHelper, $database, $module_handler, $language_manager, $time, $sitemapWriter);
    $this->domainNegotiator = $domain_negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public function generateSitemap(array $links, $remove_sitemap = FALSE) {
    if ($remove_sitemap) {
      $this->db->truncate('simple_sitemap')->execute();
    }
    $chunk_id = $remove_sitemap ? self::FIRST_CHUNK_INDEX :
      $this->db->query('SELECT MAX(id) FROM {simple_sitemap}')
        ->fetchField() + 1;
    foreach ($links as $domain_id => $domain_links) {
      $values = [
        'id' => $chunk_id,
        'domain_id' => $domain_id,
        'sitemap_string' => $this->generateSitemapChunk($domain_links),
        'sitemap_created' => $this->time->getRequestTime(),
      ];
      $this->db->insert('simple_sitemap')->fields($values)->execute();
      $chunk_id++;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function generateSitemapIndex(array $chunk_info) {
    $this->writer->openMemory();
    $this->writer->setIndent(TRUE);
    $this->writer->startDocument(self::XML_VERSION, self::ENCODING);
    $this->writer->writeComment(self::GENERATED_BY);
    $this->writer->startElement('sitemapindex');
    // Add attributes to document.
    $this->moduleHandler->alter('simple_sitemap_index_attributes', self::$indexAttributes);
    foreach (self::$indexAttributes as $name => $value) {
      $this->writer->writeAttribute($name, $value);
    }

    foreach ($chunk_info as $chunk_id => $chunk_data) {
      $domain = Domain::load($chunk_data->domain_id);
      $this->writer->startElement('sitemap');
      $this->writer->writeElement('loc', $domain->getPath() . 'sitemaps/'
        . $chunk_id . '/sitemap.xml');
      $this->writer->writeElement('lastmod', date_iso8601($chunk_data->sitemap_created));
      $this->writer->endElement();
    }
    $this->writer->endElement();
    $this->writer->endDocument();

    return $this->writer->outputMemory();
  }

}
