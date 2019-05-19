<?php

namespace Drupal\views_oai_pmh\Plugin\MetadataPrefix;

use Drupal\views_oai_pmh\Plugin\MetadataPrefixInterface;
use Drupal\Component\Plugin\PluginBase;

/**
 * Class DublinCore.
 *
 * @MetadataPrefix(
 *   id     = "oai_dc",
 *   label  = "Dublin Core",
 *   prefix = "oai_dc",
 * )
 */
class DublinCore extends PluginBase implements MetadataPrefixInterface {

  /**
   *
   */
  public function getSchema(): string {
    return 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd';
  }

  /**
   *
   */
  public function getNamespace(): string {
    return 'http://www.openarchives.org/OAI/2.0/oai_dc/';
  }

  /**
   *
   */
  public function getRootNodeName(): string {
    return 'oai_dc:dc';
  }

  /**
   *
   */
  public function getRootNodeAttributes(): array {
    return [
      '@xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
      '@xsi:schemaLocation' => 'http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd',
      '@xmlns:oai_dc' => 'http://www.openarchives.org/OAI/2.0/oai_dc/',
      '@xmlns:dc' => 'http://purl.org/dc/elements/1.1/',
    ];
  }

  /**
   *
   */
  public function getElements(): array {
    return [
      'none'           => t('- None -'),
      'dc:title'       => 'dc:title',
      'dc:creator'     => 'dc:creator',
      'dc:subject'     => 'dc:subject',
      'dc:description' => 'dc:description',
      'dc:publisher'   => 'dc:publisher',
      'dc:contributor' => 'dc:contributor',
      'dc:date'        => 'dc:date',
      'dc:type'        => 'dc:type',
      'dc:format'      => 'dc:format',
      'dc:identifier'  => 'dc:identifier',
      'dc:source'      => 'dc:source',
      'dc:language'    => 'dc:language',
      'dc:relation'    => 'dc:relation',
      'dc:coverage'    => 'dc:coverage',
      'dc:rights'      => 'dc:rights',
    ];
  }

}
