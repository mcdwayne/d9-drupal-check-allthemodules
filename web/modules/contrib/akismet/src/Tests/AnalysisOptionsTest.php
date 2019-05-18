<?php

namespace Drupal\akismet\Tests;
use Drupal\akismet\Entity\FormInterface;

/**
 * Tests text analysis options of binary mode, retaining unsure/spam.
 * @group akismet
 */
class AnalysisOptionsTest extends AkismetTestBase {
  public static $modules = ['dblog', 'akismet', 'node', 'comment', 'akismet_test_server', 'akismet_test'];

  public $disableDefaultSetup = TRUE;

  public function setUp() {
    parent::setUp();
    $this->setKeys();
    $this->assertValidKeys();

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer akismet',
    ]);
  }

  /**
   * Tests binary unsure mode.
   */
  function testUnsureBinary() {
    $this->drupalLogin($this->adminUser);
    $this->setProtectionUI('akismet_test_post_form', FormInterface::AKISMET_MODE_ANALYSIS, NULL, ['unsure' => 'binary']);
    $this->drupalLogout();

    // Verify that an unsure post is ham.
    // Note: The actual binary mode of Akismet's production API is more granular.
    $edit = [
      'title' => $this->randomString(),
      'body' => 'unsure',
    ];
    $this->assertHamSubmit('akismet-test/form', array(), $edit, t('Save'));
    $mid = $this->assertTestSubmitData();
    $data = $this->assertAkismetData('akismet_test_post', $mid);
    $record = $this->loadTestPost($mid);
    $this->assertEqual($record->getStatus(), 1, t('Published test post found.'));
    $this->assertSame('spamScore', $data->spamScore, 0);
    $this->assertSame('spamClassification', $data->spamClassification, 'ham');
    $this->assertSame('moderate', $data->moderate, 0);

    // Verify that a ham post is accepted.
    $edit = [
      'title' => $this->randomString(),
      'body' => 'ham',
    ];
    $this->assertHamSubmit('akismet-test/form', [], $edit, t('Save'));
    $mid = $this->assertTestSubmitData();
    $data = $this->assertAkismetData('akismet_test_post', $mid);
    $record = $this->loadTestPost($mid);
    $this->assertEqual($record->getStatus(), 1, t('Published test post found.'));
    $this->assertSame('spamScore', $data->spamScore, 0);
    $this->assertSame('spamClassification', $data->spamClassification, 'ham');
    $this->assertSame('moderate', $data->moderate, 0);

    // Verify that a spam post is blocked.
    $edit = [
      'title' => $this->randomString(),
      'body' => 'spam',
    ];
    $this->assertSpamSubmit('akismet-test/form', [], $edit, t('Save'));
  }

  /**
   * Tests retaining unsure posts and moderating them.
   */
  function testRetainUnsure() {
    $this->drupalLogin($this->adminUser);
    // Verify that akismet_basic_elements_test_form cannot be configured to put
    // posts into moderation queue.
    $this->setProtectionUI('akismet_basic_elements_test_form');
    $this->drupalGet('admin/config/content/akismet/form/akismet_basic_elements_test_form/edit');
    $this->assertNoFieldByName('unsure');

    // Configure akismet_test_form to retain unsure posts.
    $this->setProtectionUI('akismet_test_post_form', FormInterface::AKISMET_MODE_ANALYSIS, NULL, ['unsure' => 'moderate']);
    $this->drupalLogout();

    // Verify that an unsure post gets unpublished.
    $edit = [
      'title' => $this->randomString(),
      'body' => 'unsure',
    ];
    $this->drupalPostForm('akismet-test/form', $edit, t('Save'));
    $mid = $this->assertTestSubmitData();
    $data = $this->assertAkismetData('akismet_test_post', $mid);
    $record = $this->loadTestPost($mid);
    $this->assertEqual($record->getStatus(), 0, t('Unpublished test post found.'));
    $this->assertSame('spamScore', $data->spamScore, 0.5);
    $this->assertSame('spamClassification', $data->spamClassification, 'unsure');
    $this->assertSame('moderate', $data->moderate, 1);

    // Verify that editing the post does neither change the session data, nor
    // the publishing status.
    $edit = [
      'title' => $this->randomString(),
      'body' => 'unsure unsure',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $mid = $this->assertTestSubmitData($mid);
    $data = $this->assertAkismetData('akismet_test_post', $mid);
    $record = $this->loadTestPost($mid);
    $this->assertEqual($record->getStatus(), 0, t('Unpublished test post found.'));
    $this->assertSame('spamScore', $data->spamScore, 0.5);
    $this->assertSame('spamClassification', $data->spamClassification, 'unsure');
    $this->assertSame('moderate', $data->moderate, 1);

    // Verify that publishing the post changes the session data accordingly.
    $this->drupalLogin($this->adminUser);
    $edit = [
      'status' => TRUE,
    ];
    $this->drupalPostForm('akismet-test/form/' . $mid, $edit, t('Save'));
    $mid = $this->assertTestSubmitData($mid);
    $data = $this->assertAkismetData('akismet_test_post', $mid);
    $record = $this->loadTestPost($mid);
    $this->assertEqual($record->getStatus(), 1, t('Published test post found.'));
    $this->assertSame('spamScore', $data->spamScore, 0.5);
    $this->assertSame('spamClassification', $data->spamClassification, 'unsure');
    $this->assertSame('moderate', $data->moderate, 0);
  }

  /**
   * Tests retaining spam posts and moderating them.
   */
  function testRetainSpam() {
    $this->drupalLogin($this->adminUser);
    // Verify that akismet_basic_test_form cannot be configured to put posts into
    // moderation queue.
    $this->setProtectionUI('akismet_basic_elements_test_form');
    $this->drupalGet('admin/config/content/akismet/form/akismet_basic_elements_test_form/edit');
    $this->assertNoFieldByName('discard');

    // Configure akismet_test_form to accept bad posts.
    $this->setProtectionUI('akismet_test_post_form', FormInterface::AKISMET_MODE_ANALYSIS, NULL, [
      'checks[profanity]' => TRUE,
      'discard' => 0,
    ]);
    $this->drupalLogout();

    // Verify that we are able to post spam and the post is unpublished.
    $edit = [
      'title' => $this->randomString(),
      'body' => 'spam profanity',
    ];
    $this->drupalPostForm('akismet-test/form', $edit, t('Save'));
    $mid = $this->assertTestSubmitData();
    $data = $this->assertAkismetData('akismet_test_post', $mid);
    $record = $this->loadTestPost($mid);
    $this->assertEqual($record->getStatus(), 0, t('Unpublished test post found.'));
    $this->assertSame('spamScore', $data->spamScore, 1.0);
    $this->assertSame('spamClassification', $data->spamClassification, 'spam');
    $this->assertSame('profanityScore', $data->profanityScore, 1);
    $this->assertSame('moderate', $data->moderate, 1);

    // Verify that editing the post does neither change the session data, nor
    // the publishing status.
    $edit = [
      'title' => $this->randomString(),
      'body' => 'spam profanity spam profanity',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $mid = $this->assertTestSubmitData($mid);
    $data = $this->assertAkismetData('akismet_test_post', $mid);
    $record = $this->loadTestPost($mid);
    $this->assertEqual($record->getStatus(), 0, t('Unpublished test post found.'));
    $this->assertSame('spamScore', $data->spamScore, 1.0);
    $this->assertSame('spamClassification', $data->spamClassification, 'spam');
    $this->assertSame('profanityScore', $data->profanityScore, 1);
    $this->assertSame('moderate', $data->moderate, 1);

    // Verify that publishing the post changes the session data accordingly.
    $this->drupalLogin($this->adminUser);
    $edit = [
      'status' => TRUE,
    ];
    $this->drupalPostForm('akismet-test/form/' . $mid, $edit, t('Save'));
    $mid = $this->assertTestSubmitData($mid);
    $data = $this->assertAkismetData('akismet_test_post', $mid);
    $record = $this->loadTestPost($mid);
    $this->assertEqual($record->getStatus(), 1, t('Published test post found.'));
    $this->assertSame('spamScore', $data->spamScore, 1.0);
    $this->assertSame('spamClassification', $data->spamClassification, 'spam');
    $this->assertSame('profanityScore', $data->profanityScore, 1);
    $this->assertSame('moderate', $data->moderate, 0);

    // Verify that neither ham or unsure spam posts, nor non-profane posts are
    // marked for moderation.
    $this->drupalLogout();
    $expectations = [
      'ham' => array('spamScore' => 0.0, 'spamClassification' => 'ham', 'profanityScore' => 0),
      'unsure' => array('spamScore' => 0.0, 'spamClassification' => 'unsure', 'profanityScore' => 0),
      $this->randomString() => array('spamScore' => 0.0, 'spamClassification' => 'unsure', 'profanityScore' => 0),
    ];
    foreach ($expectations as $body => $expected) {
      $edit = [
        'title' => $this->randomString(),
        'body' => $body,
      ];
      $this->drupalPostForm('akismet-test/form', $edit, t('Save'));
      if ($expected['spamClassification'] == 'unsure') {
        $this->postCorrectCaptcha(NULL, array(), t('Save'));
        $expected['spamClassification'] = 'ham';
      }
      $mid = $this->assertTestSubmitData();
      $data = $this->assertAkismetData('akismet_test_post', $mid);
      $record = $this->loadTestPost($mid);
      $this->assertEqual($record->getStatus(), 1, t('Published test post %body found.', array('%body' => $body)));
      $this->assertSame('spamScore', $data->spamScore, $expected['spamScore']);
      $this->assertSame('spamClassification', $data->spamClassification, $expected['spamClassification']);
      $this->assertSame('profanityScore', $data->profanityScore, $expected['profanityScore']);
      $this->assertSame('moderate', $data->moderate, 0);
    }
  }


  /**
   * Loads a test post entity.
   * 
   * @param $id
   *   The id to load
   * @return \Drupal\akismet_test\Entity\PostInterface
   */
  protected function loadTestPost($id) {
    $controller = \Drupal::entityManager()->getStorage('akismet_test_post');
    $controller->resetCache(array($id));
    return $controller->load($id);
  }
}
