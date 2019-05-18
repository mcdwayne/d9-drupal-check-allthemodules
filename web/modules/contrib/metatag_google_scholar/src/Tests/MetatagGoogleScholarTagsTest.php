<?php

namespace Drupal\metatag_google_scholar\Tests;

use Drupal\metatag\Tests\MetatagTagsTestBase;

/**
 * Tests that each of the Metatag Google Scholar tags work correctly.
 *
 * @group metatag
 */
class MetatagGoogleScholarTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public $tags = [
    'citation_title',
    'citation_author',
    'citation_publication_date',
    'citation_journal_title',
    'citation_issn',
    'citation_isbn',
    'citation_volume',
    'citation_issue',
    'citation_firstpage',
    'citation_lastpage',
    'citation_dissertation_institution',
    'citation_technical_report_institution',
    'citation_technical_report_number',
    'citation_pdf_url',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::$modules[] = 'metatag_google_scholar';
    parent::setUp();
  }

  /**
   * Implements {meta_tag_name}_test_value() for 'citation_pdf_url'.
   */
  public function citation_pdf_url_test_value() {
    return 'http://www.example.com/documents/' . $this->randomMachineName() . '.pdf';
  }

}
