<?php

namespace Drupal\cision_feed\Feeds\Parser;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\Parser\ParserInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\StateInterface;
use Drupal\cision_feed\Feeds\Item\CisionFeedItem;
use Drupal\feeds\Result\ParserResult;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Html;

/**
 * Defines cision feed parser.
 *
 * @FeedsParser(
 *   id = "cision_parse",
 *   title = @Translation("Cision parser"),
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
    $data = json_decode($raw);

    foreach ($data->Releases as $release) {
      $item = new CisionFeedItem();

      if (count($release->Images)) {
        $release->Images = array_map(function ($img) {
          return UrlHelper::stripDangerousProtocols($img->DownloadUrl);
        }, $release->Images);
      }
      if (count($release->Files)) {
        $release->Files = array_map(function ($file) {
          return UrlHelper::stripDangerousProtocols($file->Url);
        }, $release->Files);
      }
      if (count($release->Quotes)) {
        $release->Quotes = array_map(function ($quote) {
          return Html::escape($quote->Text);
        }, $release->Quotes);
      }
      if (count($release->ExternalLinks)) {
        $release->ExternalLinks = array_map(function ($link) {
          return UrlHelper::stripDangerousProtocols($link->Url);
        }, $release->ExternalLinks);
      }
      if (count($release->EmbeddedItems)) {
        $release->EmbeddedItems = array_map(function ($embeddeditem) {
          return $embeddeditem->EmbedCode;
        }, $release->EmbeddedItems);
      }
      if (count($release->Keywords)) {
        $release->Keywords = array_map(function ($keyword) {
          return Html::escape($keyword);
        }, $release->Keywords);
      }
      if (count($release->Categories)) {
        $release->Categories = array_map(function ($category) {
          return HTML::escape($category->Name);
        }, $release->Categories);
      }

      $item->set('guid', (int) $release->Id);
      $item->set('title', Html::escape($release->Title));
      $item->set('htmltitle', check_markup($release->HtmlTitle, 'full_html'));
      $item->set('header', Html::escape($release->Header));
      $item->set('htmlheader', check_markup($release->HtmlHeader, 'full_html'));
      $item->set('created', strtotime($release->PublishDate));
      $item->set('changed', strtotime($release->LastChangeDate));
      $item->set('intro', strip_tags($release->Intro));
      $item->set('htmlintro', check_markup($release->HtmlIntro, 'full_html'));
      $item->set('body', strip_tags($release->Body));
      $item->set('htmlbody', check_markup($release->HtmlBody, 'full_html'));
      $item->set('countrycode', Html::escape($release->CountryCode));
      $item->set('languagecode', Html::escape($release->LanguageCode));
      $item->set('isregulatory', (int) $release->IsRegulatory);
      $item->set('socialmediapitch', isset($release->SocialMediaPitch) ? Html::escape($release->SocialMediaPitch) : '');
      $item->set('type', Html::escape($release->InformationType));
      $item->set('contact', Html::escape($release->Contact));
      $item->set('htmlcontact', check_markup($release->HtmlContact));
      $item->set('images', $release->Images);
      $item->set('files', $release->Files);
      $item->set('cisionwireurl', UrlHelper::stripDangerousProtocols($release->CisionWireUrl));
      $item->set('syndicatedurl', isset($release->SyndicatedUrl) ? UrlHelper::stripDangerousProtocols($release->SyndicatedUrl) : '');
      $item->set('keywords', $release->Keywords);
      $item->set('categories', $release->Categories);
      $item->set('companyinformation', Html::escape($release->CompanyInformation));
      $item->set('htmlcompanyinformation', check_markup($release->HtmlCompanyInformation));
      $item->set('quickfacts', $release->QuickFacts);
      $item->set('logourl', UrlHelper::stripDangerousProtocols($release->LogoUrl));
      $item->set('quotes', $release->Quotes);
      $item->set('externallinks', $release->ExternalLinks);
      $item->set('embeddeditems', $release->EmbeddedItems);
      $item->set('tid', (int) $release->Tid);

      $result->addItem($item);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources() {
    return [
      'guid' => [
        'label' => $this->t('GUID'),
        'description' => $this->t('The id of the release.'),
      ],
      'title' => [
        'label' => $this->t('Title'),
        'description' => $this->t('The title of the release.'),
        'suggestions' => [
          'targets' => ['subject', 'title', 'label', 'name'],
          'types' => ['field_item:text' => []],
        ],
      ],
      'htmltitle' => [
        'label' => $this->t('HtmlTitle'),
        'description' => $this->t('The title with the associated html-code that has been edited in the html editor in Cision Connect.'),
      ],
      'header' => [
        'label' => $this->t('Header'),
        'description' => $this->t('The header of the release.'),
      ],
      'htmlheader' => [
        'label' => $this->t('HtmlHeader'),
        'description' => $this->t('The header with the associated html-code that has been edited in the html editor in Cision Connect.'),
      ],
      'created' => [
        'label' => $this->t('PublishDate'),
        'description' => $this->t('The date when the release was published. It uses the UTC time standard.'),
      ],
      'changed' => [
        'label' => $this->t('LastChangeDate'),
        'description' => $this->t('The date when the release was last changed. It uses the UTC time standard.'),
      ],
      'intro' => [
        'label' => $this->t('Intro'),
        'description' => $this->t('The preamble of the release.'),
      ],
      'htmlintro' => [
        'label' => $this->t('HtmlIntro'),
        'description' => $this->t('The preamble with the associated html-code that has been edited in the html editor in Cision Connect.'),
      ],
      'body' => [
        'label' => $this->t('Body'),
        'description' => $this->t('The body of the release in plain text.'),
      ],
      'htmlbody' => [
        'label' => $this->t('HtmlBody'),
        'description' => $this->t("The body with the associated html-code that has been edited in the html editor in Cision Connect."),
      ],
      'isregulator' => [
        'label' => $this->t('Regulatory'),
        'description' => $this->t("Boolian which states if the release contains regulatory information."),
      ],
      'type' => [
        'label' => $this->t('InformationType'),
        'description' => $this->t('The type code of the release.'),
      ],
      'languagecode' => [
        'label' => $this->t('LanguageCode'),
        'description' => $this->t('The language code of the release.'),
        'suggestions' => [
          'targets' => ['langcode'],
          'types' => ['field_item:language' => []],
        ],
      ],
      'countrycode' => [
        'label' => $this->t('CountryCode'),
        'description' => $this->t('The country linked to this release.'),
      ],
      'contact' => [
        'label' => $this->t('Contact'),
        'description' => $this->t('Contact information linked to the release.'),
      ],
      'htmlcontact' => [
        'label' => $this->t('HtmlContact'),
        'description' => $this->t('The contact information with the associated html-code that has been edited in the html editor in Cision Connect.'),
      ],
      'images' => [
        'label' => $this->t('Images'),
        'description' => $this->t('Cision images.'),
      ],
      'files' => [
        'label' => $this->t('Files'),
        'description' => $this->t('Cision files.'),
      ],
      'syndicatedurl' => [
        'label' => $this->t('SyndicatedUrl'),
        'description' => $this->t('Used by search engines to prevent duplicate content. Also known as canonical url.'),
      ],
      'keywords' => [
        'label' => $this->t('Keywords'),
        'description' => $this->t('Keywords are Cisions version of tags.'),
      ],
      'categories' => [
        'label' => $this->t('Categories'),
        'description' => $this->t('Cision categories.'),
      ],
      'cisionwireurl' => [
        'label' => $this->t('CisionWireUrl'),
        'description' => $this->t('A URL leading to the release page on Cision News.'),
      ],
      'socialmediapitch' => [
        'label' => $this->t('SocialMediaPitch'),
        'description' => $this->t('The text that appears when sharing a release on social networks.'),
      ],
      'companyinformation' => [
        'label' => $this->t('CompanyInformation'),
        'description' => $this->t('The company information linked to the release.'),
      ],
      'htmlcompanyinformation' => [
        'label' => $this->t('HtmlCompanyInformation'),
        'description' => $this->t('The company information with the associated html-code that has been edited in the html editor in Cision Connect.'),
      ],
      'quickfacts' => [
        'label' => $this->t('QuickFacts'),
        'description' => $this->t('Quick facts about the release or company.'),
      ],
      'logourl' => [
        'label' => $this->t('LogoUrl'),
        'description' => $this->t('URL to the company logotype.'),
      ],
      'quotes' => [
        'label' => $this->t('Quotes'),
        'description' => $this->t('Quotes for the release.'),
      ],
      'externallinks' => [
        'label' => $this->t('ExternalLinks'),
        'description' => $this->t('External links related to the company or the release.'),
      ],
      'embeddedimages' => [
        'label' => $this->t('EmbeddedItems'),
        'description' => $this->t('Element containing information about items embedded to the release.'),
      ],
      /*
      'videos' => [
        'label' => $this->t('Videos'),
        'description' => $this->t(''),
      ],*/
    ];
  }

}
