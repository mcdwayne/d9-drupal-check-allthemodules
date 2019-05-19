<?php

namespace Drupal\views_oai_pmh\Plugin\MetadataPrefix;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views_oai_pmh\Plugin\MetadataPrefixInterface;
use Drupal\Component\Plugin\PluginBase;

/**
 * Class Jats.
 *
 * @MetadataPrefix(
 *   id     = "oai_jats",
 *   label  = "Jats",
 *   prefix = "oai_jats",
 * )
 */
class Jats extends PluginBase implements MetadataPrefixInterface {

  use StringTranslationTrait;

  /**
   *
   */
  public function getRootNodeName(): string {
    return 'article';
  }

  /**
   *
   */
  public function getRootNodeAttributes(): array {
    return [
      '@xmlns' => 'https://jats.nlm.nih.gov/publishing/1.1/',
      '@xmlns:xlink' => 'http://www.w3.org/1999/xlink',
      '@dtd-version' => '1.1',
      '@specific-use' => 'eps-0.1',
      '@article-type' => 'research-article',
      '@xml:lang' => 'fr',
    ];
  }

  /**
   *
   */
  public function getSchema(): string {
    return 'https://jats.nlm.nih.gov/publishing/1.1/xsd/JATS-journalpublishing1.xsd';
  }

  /**
   *
   */
  public function getNamespace(): string {
    return 'https://jats.nlm.nih.gov/publishing/1.1/';
  }

  /**
   *
   */
  public function getElements(): array {
    return [
      'none' => $this->t('- None -'),

      'front>journal-meta>journal-id' => 'front > journal-meta > journal-id',
      'front>journal-meta>journal-id@journal-id-type' => 'front > journal-meta > journal-id@journal-id-type',

      'front>journal-meta>journal-title-group>journal-title' => 'front > journal-meta > journal-title-group > journal-title',
      'front>journal-meta>journal-title-group>journal-subtitle' => 'front > journal-meta > journal-title-group > journal-subtitle',
      'front>journal-meta>journal-title-group>abbrev-journal-title' => 'front > journal-meta > journal-title-group > abbrev-journal-title',

      'front>journal-meta>contrib-group@content-type' => 'front > journal-meta > contrib-group@content-type',
      'front>journal-meta>contrib-group>contrib@contrib-type' => 'front > journal-meta > contrib@contrib-type',
      'front>journal-meta>contrib-group>contrib>name>surname' => 'front > journal-meta > contrib > name > surname',
      'front>journal-meta>contrib-group>contrib>name>given-names' => 'front > journal-meta > contrib > name > given-names',
      'front>journal-meta>contrib-group>contrib>role' => 'front > journal-meta > contrib > role',

      'front>journal-meta>issn' => 'front > journal-meta > issn',
      'front>journal-meta>issn@pub-type' => 'front > journal-meta > issn@pub-type',

      'front>journal-meta>publisher>publisher-name' => 'front > journal-meta > publisher > publisher-name',

      'front>article-meta>article-id' => 'front > article-meta > article-id',
      'front>article-meta>article-id@pub-id-type' => 'front > article-meta > article-id@pub-id-type',

      'front>article-meta>article-categories>subj-group@subj-group-type' => 'front > article-meta > article-categories > subj-group@subj-group-type',
      'front>article-meta>article-categories>subj-group>subject' => 'front > article-meta > article-categories > subj-group > subject',
      'front>article-meta>article-categories>subj-group>subj-group@subj-group-type' => 'front > article-meta > article-categories > subj-group > subj-group@subj-group-type',
      'front>article-meta>article-categories>subj-group>subj-group>subject' => 'front > article-meta > article-categories > subj-group > subj-group > subject',

      'front>article-meta>title-group>article-title' => 'front > article-meta > title-group > article-title',
      'front>article-meta>title-group>article-title@xml:lang' => 'front > article-meta > title-group > article-title@xml:lang',
      'front>article-meta>title-group>subtitle' => 'front > article-meta > title-group > subtitle',
      'front>article-meta>title-group>subtitle@xml:lang' => 'front > article-meta > title-group > subtitle@xml:lang',

      'front>article-meta>volume' => 'front > article-meta > volume',

      'front>article-meta>contrib-group@content-type' => 'front > article-meta > contrib-group@content-type',
      'front>article-meta>contrib-group>contrib@contrib-type' => 'front > article-meta > contrib-group > contrib@contrib-type',
      'front>article-meta>contrib-group>contrib@id' => 'front > article-meta > contrib-group > contrib@id',
      'front>article-meta>contrib-group>contrib>name>surname' => 'front > article-meta > contrib-group > contrib > name > surname',
      'front>article-meta>contrib-group>contrib>name>given-names' => 'front > article-meta > contrib-group > contrib > name > given-names',

      'front>article-meta>contrib-group>contrib>xref' => 'front > article-meta > contrib-group > contrib > xref',
      'front>article-meta>contrib-group>contrib>xref@rid' => 'front > article-meta > contrib-group > contrib > xref@rid',
      'front>article-meta>contrib-group>contrib>xref@ref-type' => 'front > article-meta > contrib-group > contrib > xref@ref-type',

      'front>article-meta>aff' => 'front > article-meta > aff',
      'front>article-meta>institution' => 'front > article-meta > aff > institution',

      'front>article-meta>contrib-group>contrib>bio' => 'front > article-meta > contrib-group > contrib > bio',
      'front>article-meta>contrib-group>contrib>bio@xml:lang' => 'front > article-meta > contrib-group > contrib > bio@xml:lang',

      'front>article-meta>pub-date>season' => 'front > article-meta > pub-date > season',
      'front>article-meta>pub-date@publication-format' => 'front > article-meta > pub-date@publication-format',
      'front>article-meta>pub-date@date-type' => 'front > article-meta > pub-date@date-type',
      'front>article-meta>pub-date>day' => 'front > article-meta > pub-date > day',
      'front>article-meta>pub-date>month' => 'front > article-meta > pub-date > month',
      'front>article-meta>pub-date>year' => 'front > article-meta > pub-date > year',

      'front>article-meta>issue' => 'front > article-meta > issue',
      'front>article-meta>issue@seq' => 'front > article-meta > issue@seq',

      'front>article-meta>issue-id' => 'front > article-meta > issue-id',
      'front>article-meta>issue-id@pub-id-type' => 'front > article-meta > issue-id@pub-id-type',
      'front>article-meta>issue-title' => 'front > article-meta > issue-title',

      'front>article-meta>fpage' => 'front > article-meta > fpage',
      'front>article-meta>lpage' => 'front > article-meta > lpage',

      'front>article-meta>permissions>copyright-statement' => 'front > article-meta > permissions > copyright-statement',
      'front>article-meta>permissions>copyright-year' => 'front > article-meta > permissions > copyright-year',
      'front>article-meta>permissions>copyright-holder' => 'front > article-meta > permissions > copyright-holder',
      'front>article-meta>permissions>licence@xlink:href' => 'front > article-meta > permissions > licence@xlink:href',
      'front>article-meta>permissions>licence>licence-p>graphic@xlink:href' => 'front > article-meta > permissions > licence > licence-p > graphic@xlink:href',

      'front>article-meta>abstract' => 'front > article-meta > abstract',
      'front>article-meta>abstract@xml:lang' => 'front > article-meta > abstract@xml:lang',
      'front>article-meta>abstract' => 'front > article-meta > abstract',
      'front>article-meta>trans-abstract@xml:lang' => 'front > article-meta > trans-abstract@xml:lang',
      'front>article-meta>abstract>p' => 'front > article-meta > abstract > p',

      'front>article-meta>related-object@content-type' => 'front > article-meta > related-object@content-type',
      'front>article-meta>related-object@document-type' => 'front > article-meta > related-object@document-type',
      
      'body' => 'body',
      
      'back>ref-list>ref@id' => 'back > ref-list > ref@id',
      'back>ref-list>ref>element-citation>styled-content' => 'back > ref-list > ref > element-citation > styled-content',
      'back>ref-list>ref>element-citation>styled-content@specific-use' => 'back > ref-list > ref > element-citation > styled-content@specific-use',
      'back>ref-list>ref>element-citation>pub-id' => 'back > ref-list > ref > element-citation > pub-id',
      'back>ref-list>ref>element-citation>pub-id@pub-id-type' => 'back > ref-list > ref > element-citation > pub-id@pub-id-type',
    ];
  }

}
