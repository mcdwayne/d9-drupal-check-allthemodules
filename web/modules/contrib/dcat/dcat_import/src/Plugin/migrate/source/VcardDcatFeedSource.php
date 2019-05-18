<?php

namespace Drupal\dcat_import\Plugin\migrate\source;

use EasyRdf_Resource;
use EasyRdf_Graph;

/**
 * Agent feed source.
 *
 * @MigrateSource(
 *   id = "dcat.vcard"
 * )
 */
class VcardDcatFeedSource extends DcatFeedSource {

  /**
   * Not in use.
   *
   * @see getDcatData()
   */
  public function getDcatType() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDcatData(EasyRdf_Graph $graph) {
    $vcards = array();
    $datasets = $graph->allOfType('dcat:Dataset');

    /** @var EasyRdf_Resource $dataset */
    foreach ($datasets as $dataset) {
      $vcards = array_merge($vcards, $dataset->allResources('dcat:contactPoint'));
    }

    // Remove duplicates.
    $uris = array();
    /** @var EasyRdf_Resource $vcard */
    foreach ($vcards as $key => $vcard) {
      $uri = $vcard->getUri();
      if (isset($uris[$uri])) {
        unset($vcards[$key]);
      }
      else {
        $uris[$uri] = $uri;
      }
    }

    return $vcards;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'uri' => t('URI / ID'),
      'name' => t('Name'),
      'email' => t('Email'),
      'telephone' => t('Telephone'),
      'country' => t('Country'),
      'locality' => t('Locality'),
      'postal_code' => t('Postal code'),
      'region' => t('Region'),
      'street_address' => t('Street address'),
      'nickname' => t('Nickname'),
      'type' => t('Type'),
    );
  }

  /**
   * Resource type to vcard bundle mapping.
   *
   * @return array
   *   Bundle mapping.
   */
  private static function bundleMapping() {
    return [
      'vcard:Organization' => 'organization',
      'vcard:Individual' => 'individual',
      'vcard:Location' => 'location',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function convertResource(EasyRdf_Resource $resource) {
    if (isset(self::bundleMapping()[$resource->type()])) {
      $bundle = self::bundleMapping()[$resource->type()];
    }
    else {
      // Default to organization;
      $bundle = 'organization';
    }

    return parent::convertResource($resource) + [
      'uri' => $resource->getUri(),
      'type' => $bundle,
      'name' => $this->getValue($resource, 'vcard:fn'),
      'email' => $this->getEmailValue($resource, 'vcard:hasEmail'),
      'telephone' => $this->getValue($resource, 'vcard:hasTelephone'),
      'country' => $this->getValue($resource, 'vcard:country-name'),
      'locality' => $this->getValue($resource, 'vcard:locality'),
      'postal_code' => $this->getValue($resource, 'vcard:postal-code'),
      'region' => $this->getValue($resource, 'vcard:region'),
      'street_address' => $this->getValue($resource, 'vcard:street-address'),
      'nickname' => $this->getValue($resource, 'vcard:hasNickname'),
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
