<?php

namespace Drupal\cision_feeds\Feeds\Parser;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\Parser\ParserInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\StateInterface;
use Drupal\cision_feeds\Feeds\Item\CisionItem;
use Drupal\feeds\Result\ParserResult;

/**
 * Defines cision feed parser.
 *
 * @FeedsParser(
 *   id = "cision_parse",
 *   title = @Translation("Cision feeds"),
 *   description = @Translation("Default parser for cision feeds.")
 * )
 */
class CisionParser extends PluginBase implements ParserInterface {

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    $result = new ParserResult();
    $raw = $fetcher_result->getRaw();
    $cision_xml = simplexml_load_file($raw);
    foreach ($cision_xml->children() as $child) {
      $item = new CisionItem();
      $DetailUrl = (string) $child->attributes()->DetailUrl->__toString();
      $release = simplexml_load_file($DetailUrl);
      $item->set('guid', $release->attributes()->Id->__toString());
      $item->set('langCommonGuid', $release->LanguageVersions->attributes()->CommonId->__toString());
      $item->set('PublishDateUtc', strtotime($release->attributes()->PublishDateUtc->__toString()));
      $item->set('LastChangeDateUtc', strtotime($release->attributes()->LastChangeDateUtc->__toString()));
      $item->set('LanguageCode', $release->attributes()->LanguageCode->__toString());
      $item->set('title', $release->Title->__toString());
      $item->set('Intro', $release->Intro->__toString());
      $item->set('Body', $release->Body->__toString());
      $item->set('SyndicatedUrl', $release->SyndicatedUrl->__toString());
      $result->addItem($item);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources() {
    return [
      'title' => [
        'label' => $this->t('Title'),
        'description' => $this->t('Title of the feed item.'),
        'suggestions' => [
          'targets' => ['subject', 'title', 'label', 'name'],
          'types' => ['field_item:text' => []],
        ],
      ],
      'Intro' => [
        'label' => $this->t('Intro/lead'),
        'description' => $this->t('Lead of the content'),
      ],
      'Body' => [
        'label' => $this->t('Body'),
        'description' => $this->t('Description of the feed item.'),
      ],
      'PublishDateUtc' => [
        'label' => $this->t('Published date'),
        'description' => $this->t('Published date as UNIX time GMT of the feed item.'),
        'suggested' => ['PublishDateUtc'],
        'suggestions' => ['targets' => ['created']],
      ],
      'LastChangeDateUtc' => [
        'label' => $this->t('Updated date'),
        'description' => $this->t('Updated date as UNIX time GMT of the feed item.'),
        'suggested' => ['LastChangeDateUtc'],
        'suggestions' => ['targets' => ['changed']],
      ],
      'guid' => [
        'label' => $this->t('Item GUID'),
        'description' => $this->t('Global Unique Identifier of the feed item.'),
        'suggestions' => ['targets' => ['guid']],
      ],
      'langCommonGuid' => [
        'label' => $this->t('Item langauage GUID'),
        'description' => $this->t('Global Unique Identifier for different languages.'),
        'suggestions' => ['targets' => ['guid']],
      ],
      'LanguageCode' => [
        'label' => $this->t('Language'),
        'description' => $this->t('Language Code'),
        'suggestions' => [
          'targets' => ['langcode'],
          'types' => ['field_item:language' => []],
        ],
      ],
      'SyndicatedUrl' => [
        'label' => $this->t('Syndicated Url'),
        'description' => $this->t('SyndicatedUrl'),
        'suggestions' => [
          'targets' => ['SyndicatedUrl'],
          'types' => ['field_item:text' => []],
        ],
      ],
      /* 'Images' => [
        'label' => $this->t('Image'),
        'description' => $this->t('The URL of the image.'),
        ],
        'Categories' => [
        'label' => $this->t('Categories'),
        'description' => $this->t('An array of categories that have been assigned to the feed item.'),
        'suggestions' => [
        'targets' => ['category'],
        'types' => ['field_item:taxonomy_term_reference' => []],
        ],
        ],
        'Keywords' => [
        'label' => $this->t('Tags'),
        'description' => $this->t('An array of keywords that have been assigned to the feed item.'),
        'suggestions' => [
        'targets' => ['field_tags'],
        'types' => ['field_item:taxonomy_term_reference' => []],
        ],
        ],
        'Files' => [
        'label' => $this->t('Files'),
        'description' => $this->t('A list of files attached to the feed item.'),
        'suggestions' => ['types' => ['field_item:file' => []]],
        ], */
    ];
  }

}
