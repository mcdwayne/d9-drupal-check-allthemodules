<?php

namespace Drupal\dcat_import\Plugin\migrate\source;

use EasyRdf_Resource;

/**
 * DCAT Dataset feed source.
 *
 * @MigrateSource(
 *   id = "dcat.distribution"
 * )
 */
class DistributionDcatFeedSource extends DcatFeedSource {

  /**
   * {@inheritdoc}
   */
  public function getDcatType() {
    return 'dcat:Distribution';
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'uri' => t('URI / ID'),
      'title' => t('Title'),
      'description' => t('Description'),
      'issued' => t('Issued'),
      'modified' => t('Modified'),
      'access_url' => t('Access URL'),
      'download_url' => t('Download URL'),
      'byte_size' => t('Byte size'),
      'format' => t('Format'),
      'license' => t('License'),
      'media_type' => t('Media type'),
      'rights' => t('Rights'),
      'dcat_status' => t('Status'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function convertResource(EasyRdf_Resource $resource) {
    return parent::convertResource($resource) + [
      'title' => $this->getValue($resource, 'dc:title'),
      'description' => $this->getValue($resource, 'dc:description'),
      'issued' => $this->getDateValue($resource, 'dc:issued'),
      'modified' => $this->getDateValue($resource, 'dc:modified'),
      'access_url' => $this->getValue($resource, 'dcat:accessURL'),
      'download_url' => $this->getValue($resource, 'dcat:downloadURL'),
      'byte_size' => $this->getValue($resource, 'dcat:byteSize'),
      'format' => $this->getValue($resource, 'dc:format'),
      'license' => $this->getValue($resource, 'dc:license'),
      'media_type' => $this->getValue($resource, 'dcat:mediaType'),
      'rights' => $this->getValue($resource, 'dc:rights'),
      'dcat_status' => $this->getValue($resource, 'adms:status'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['uri']['type'] = 'string';
    return $ids;
  }

}
