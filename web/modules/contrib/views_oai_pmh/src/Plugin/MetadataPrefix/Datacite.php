<?php

namespace Drupal\views_oai_pmh\Plugin\MetadataPrefix;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views_oai_pmh\Plugin\MetadataPrefixInterface;
use Drupal\Component\Plugin\PluginBase;

/**
 * Class DublinCore.
 *
 * @MetadataPrefix(
 *   id     = "oai_datacite",
 *   label  = "Datacite",
 *   prefix = "oai_datacite",
 * )
 */
class Datacite extends PluginBase implements MetadataPrefixInterface {

  use StringTranslationTrait;

  /**
   *
   */
  public function getRootNodeName(): string {
    return 'oai_datacite';
  }

  /**
   *
   */
  public function getRootNodeAttributes(): array {
    return [];
  }

  /**
   *
   */
  public function getSchema(): string {
    return 'http://schema.datacite.org/oai/oai-1.1/oai.xsd';
  }

  /**
   *
   */
  public function getNamespace(): string {
    return 'http://schema.datacite.org/oai/oai-1.1/';
  }

  /**
   *
   */
  public function getElements(): array {
    return [
      'none' => $this->t('- None -'),
      'titles>title' => 'titles > title',
      'publisher' => 'publisher',
      'subjects>subject' => 'subjects>subject',
      'subjects>subject@subjectScheme' => 'subjects>subject@subjectScheme',
      'dates>date' => 'dates>date',
      'dates>date@dateType' => 'dates>date@dateType',
      'descriptions>description' => 'descriptions>description',
      'descriptions>description@descriptionType' => 'descriptions>description@descriptionType',
      'publicationYear' => 'publicationYear',
      'identifier' => 'identifier',
      'identifier@identifierType' => 'identifier@identifierType',
      'creators>creator>creatorName' => 'creators>creator>creatorName',
      'creators>creator>nameIdentifier' => 'creators>creator>nameIdentifier',
      'creators>creator>nameIdentifier@nameIdentifierScheme' => 'creators>creator>nameIdentifier@nameIdentifierScheme',
      'creators>creator>nameIdentifier@schemeURI' => 'creators>creator>nameIdentifier@schemeURI',
      'contributors>contributor>contributorName' => 'contributors>contributor>contributorName',
      'contributors>contributor>contributorName@contributorType' => 'contributors>contributor>contributorName@contributorType',
      'contributors>contributor>nameIdentifier' => 'contributors>contributor>nameIdentifier',
      'contributors>contributor>nameIdentifier@nameIdentifierScheme' => 'contributors>contributor>nameIdentifier@nameIdentifierScheme',
      'contributors>contributor>nameIdentifier@schemeURI' => 'contributors>contributor>nameIdentifier@schemeURI',
      'resourceType' => 'resourceType',
      'resourceType@resourceTypeGeneral' => 'resourceType@resourceTypeGeneral',
      'rightsList>rights' => 'rightsList>rights',
      'rightsList>rights@rightsURI' => 'rightsList>rights@rightsURI',
    ];
  }

}
