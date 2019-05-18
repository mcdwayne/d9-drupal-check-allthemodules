<?php

namespace Drupal\bibcite_migrate\Plugin\migrate\source;


use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Source plugin for the Reference.
 *
 * @MigrateSource(
 *   id = "bibcite_reference",
 *   source_provider = "biblio",
 *   source_module = "biblio"
 * )
 */
class Reference extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('biblio', 'b');
    $query->join('node', 'n', 'b.vid = n.vid');
    $query->distinct();

    $query->fields('b');
    $query->fields('n', ['title']);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nid' => $this->t('Node ID'),
      'vid' => $this->t('Revision ID'),
      'biblio_type' => $this->t('Type'),
      'biblio_number' => $this->t('Number'),
      'biblio_other_number' => $this->t('Other number'),
      'biblio_sort_title' => $this->t('Sort title'),
      'biblio_secondary_title' => $this->t('Secondary title'),
      'biblio_tertiary_title' => $this->t('Tertiary title'),
      'biblio_edition' => $this->t('Edition'),
      'biblio_publisher' => $this->t('Published'),
      'biblio_place_published' => $this->t('Place published'),
      'biblio_year' => $this->t('Year of publication'),
      'biblio_volume' => $this->t('Volume'),
      'biblio_pages' => $this->t('Pages'),
      'biblio_date' => $this->t('Date'),
      'biblio_isbn' => $this->t('ISBN'),
      'biblio_lang' => $this->t('Language'),
      'biblio_abst_e' => $this->t('Abstract'),
      'biblio_abst_f' => $this->t('Abstract French'),
      'biblio_full_text' => $this->t('Full text'),
      'biblio_url' => $this->t('URL'),
      'biblio_issue' => $this->t('Issue'),
      'biblio_type_of_work' => $this->t('Type of work'),
      'biblio_accession_number' => $this->t('Accession number'),
      'biblio_call_number' => $this->t('Call number'),
      'biblio_notes' => $this->t('Notes'),
      'biblio_custom1' => $this->t('Custom 1'),
      'biblio_custom2' => $this->t('Custom 2'),
      'biblio_custom3' => $this->t('Custom 3'),
      'biblio_custom4' => $this->t('Custom 4'),
      'biblio_custom5' => $this->t('Custom 5'),
      'biblio_custom6' => $this->t('Custom 6'),
      'biblio_custom7' => $this->t('Custom 7'),
      'biblio_research_notes' => $this->t('Research notes'),
      'biblio_number_of_volumes' => $this->t('Number of volumes'),
      'biblio_short_title' => $this->t('Short title'),
      'biblio_alternate_title' => $this->t('Alternate title'),
      'biblio_original_publication' => $this->t('Original publication'),
      'biblio_reprint_edition' => $this->t('Reprint edition'),
      'biblio_translated_title' => $this->t('Translated title'),
      'biblio_citekey' => $this->t('Citekey'),
      'biblio_coins' => $this->t('Coins'),
      'biblio_doi' => $this->t('DOI'),
      'biblio_issn' => $this->t('ISSN'),
      'biblio_auth_address' => $this->t('Auth address'),
      'biblio_remote_db_name' => $this->t('Remote database name'),
      'biblio_remote_db_provider' => $this->t('Remote database provider'),
      'biblio_label' => $this->t('Label'),
      'biblio_access_date' => $this->t('Access date'),
      'biblio_refereed' => $this->t('Referred'),
      'biblio_md5' => $this->t('MD5'),
      'biblio_formats' => $this->t('Formats'),
      'author' => $this->t('Author'),
      'title' => $this->t('Title'),
      'keywords' => $this->t('Keywords'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $nid = $row->getSourceProperty('nid');
    $vid = $row->getSourceProperty('vid');

    $authors = $this->selectContributors($nid, $vid);
    $row->setSourceProperty('author', $authors);

    $keywords = $this->selectKeywords($nid, $vid);
    $row->setSourceProperty('keywords', $keywords);

    return parent::prepareRow($row);
  }

  /**
   * Select all contributors related to biblio entry.
   *
   * @param string $nid
   *   Biblio node identifier.
   * @param string $vid
   *   Biblio node revision identifier.
   *
   * @return array
   *   Array of contributors data.
   */
  protected function selectContributors($nid, $vid) {
    $query = $this->select('biblio_contributor', 'bc')
      ->fields('bc', ['cid', 'auth_type', 'auth_category']);

    $query->condition('bc.nid', $nid);
    $query->condition('bc.vid', $vid);
    $result = $query->execute();

    return $result->fetchAll();
  }

  /**
   * Select all keywords related to biblio entry.
   *
   * @param string $nid
   *   Biblio node identifier.
   * @param string $vid
   *   Biblio node revision identifier.
   *
   * @return array
   *   Array of keywords data.
   */
  protected function selectKeywords($nid, $vid) {
    $query = $this->select('biblio_keyword', 'bk')
      ->fields('bk', ['kid']);

    $query->condition('bk.nid', $nid);
    $query->condition('bk.vid', $vid);
    $result = $query->execute();

    return $result->fetchAll();
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'b',
      ],
      'vid' => [
        'type' => 'integer',
        'alias' => 'b',
      ],
    ];
  }

}
