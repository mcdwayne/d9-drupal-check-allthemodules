<?php

namespace Drupal\akismet\Tests;

use Drupal\component\Utility\Unicode;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\akismet\Storage\BlacklistStorage;

/**
 * Tests URL and text blacklist functionality.
 * @group akismet
 */
class BlacklistTest extends AkismetTestBase {

  /**
   * Modules to enable.
   * @var array
   */
  public static $modules = ['dblog', 'akismet', 'node', 'comment', 'akismet_test_server'];

  public $disableDefaultSetup = TRUE;

  function setUp() {
    parent::setUp();
    $this->setKeys();
  }

  /**
   * Test the blacklist functionality at the API level without using a web interface.
   */
  function testBlacklistAPI() {
    $akismet = $this->getClient(TRUE);
    // Remove any stale blacklist entries from test runs that did not finish.
    $blacklist = $akismet->getBlacklist();
    foreach ($blacklist as $entry) {
      if (REQUEST_TIME - strtotime($entry['created']) > 86400) {
        $akismet->deleteBlacklistEntry($entry['id']);
      }
    }
    $this->assertAkismetWatchdogMessages();

    // Blacklist a URL.
    $domain = Unicode::strtolower($this->randomMachineName()) . '.com';
    $entry = $akismet->saveBlacklistEntry([
      'value' => $domain,
      'context' => 'allFields',
      'reason' => 'spam',
      'match' => 'contains',
    ]);
    $this->assertAkismetWatchdogMessages();
    $this->assertTrue($entry['id'], t('The URL was blacklisted.'));

    // Check whether posts containing the blacklisted URL are properly blocked.
    $result = $akismet->checkContent([
      'postBody' => "When the exact URL is present, the post should get blocked: http://{$domain}",
    ]);
    $this->assertAkismetWatchdogMessages();
    $this->assertSame('spamScore', $result['spamScore'], 1.0);
    $this->assertEqual($result['spamClassification'], 'spam', t('Exact URL match was blocked.'));

    $result = $akismet->checkContent([
      'postBody' => "When the URL is expanded in the back, the post should get blocked: http://{$domain}/oh-my",
    ]);
    $this->assertAkismetWatchdogMessages();
    $this->assertSame('spamScore', $result['spamScore'], 1.0);
    $this->assertEqual($result['spamClassification'], 'spam', t('Partial URL match was blocked.'));

    $result = $akismet->checkContent([
      'postBody' => "When the URL is expanded in the front, the post should get blocked: http://www.{$domain}",
    ]);
    $this->assertAkismetWatchdogMessages();
    $this->assertSame('spamScore', $result['spamScore'], 1.0);
    $this->assertEqual($result['spamClassification'], 'spam', t('URL with www-prefix was blocked.'));

    $result = $akismet->checkContent([
      'postBody' => "When the URL has a different schema, the post should get blocked: ftp://www.{$domain}",
    ]);
    $this->assertAkismetWatchdogMessages();
    $this->assertSame('spamScore', $result['spamScore'], 1.0);
    $this->assertEqual($result['spamClassification'], 'spam', t('URL with different schema was blocked.'));

    $result = $akismet->deleteBlacklistEntry($entry['id']);
    $this->assertAkismetWatchdogMessages();
    $this->assertIdentical($result, TRUE, t('The blacklisted URL was removed.'));

    // Blacklist a word.
    // @todo As of now, only non-numeric, lower-case text seems to be supported.
    $term = Unicode::strtolower(preg_replace('/[^a-zA-Z]/', '', $this->randomMachineName()));
    $entry = $akismet->saveBlacklistEntry([
      'value' => $term,
      'context' => 'allFields',
      'reason' => 'spam',
      'match' => 'contains',
    ]);
    $this->assertAkismetWatchdogMessages();
    $this->assertTrue($entry['id'], t('The text was blacklisted.'));

    // Check whether posts containing the blacklisted word are properly blocked.
    $data = [
      'postBody' => $term,
    ];
    $result = $akismet->checkContent($data);
    $this->assertAkismetWatchdogMessages();
    $this->assertSame('spamScore', $result['spamScore'], 1.0);
    $this->assertEqual($result['spamClassification'], 'spam', t('Identical match was blocked.'));

    $data = [
      'postBody' => "When the term is present, the post should get blocked: " . $term,
    ];
    $result = $akismet->checkContent($data);
    $this->assertAkismetWatchdogMessages();
    $this->assertSame('spamScore', $result['spamScore'], 1.0);
    $this->assertEqual($result['spamClassification'], 'spam', t('Exact match was blocked.'));

    $data = [
      'postBody' => "When match is 'contains', the word can be surrounded by other text: abc" . $term . "def",
    ];
    $result = $akismet->checkContent($data);
    $this->assertAkismetWatchdogMessages();
    $this->assertSame('spamScore', $result['spamScore'], 1.0);
    $this->assertEqual($result['spamClassification'], 'spam', t('Partial match was blocked.'));

    // Update the blacklist entry to match the term only exactly.
    $entry = $akismet->saveBlacklistEntry([
      'id' => $entry['id'],
      'value' => $term,
      'context' => 'allFields',
      'reason' => 'spam',
      'match' => 'exact',
    ]);
    $this->assertAkismetWatchdogMessages();
    $this->assertTrue($entry['id'], t('The blacklist entry was updated.'));

    $data = [
      'postBody' => "When match is 'exact', it has to be exact: " . $term,
    ];
    $result = $akismet->checkContent($data);
    $this->assertAkismetWatchdogMessages();
    $this->assertSame('spamScore', $result['spamScore'], 1.0);
    $this->assertEqual($result['spamClassification'], 'spam', t('Exact match was blocked.'));

    $data = [
      'postBody' => "When match is 'exact', it has to be exact: abc{$term}def",
    ];
    $result = $akismet->checkContent($data);
    $this->assertAkismetWatchdogMessages();
    $this->assertSame('spamScore', $result['spamScore'], 0.5);
    $this->assertEqual($result['spamClassification'], 'unsure', t('Partial match was not blocked.'));

    $result = $akismet->deleteBlacklistEntry($entry['id']);
    $this->assertAkismetWatchdogMessages();
    $this->assertIdentical($result, TRUE, t('The blacklisted text was removed.'));

    // Try to remove a non-existing entry.
    // @todo Ensure that the ID does not exist.
    $result = $akismet->deleteBlacklistEntry(999);
    $this->assertAkismetWatchdogMessages(RfcLogLevel::EMERGENCY);
    $this->assertNotIdentical($result, TRUE, t('Error response for a non-existing blacklist text found.'));
    $this->assertSame('Response code', $akismet->lastResponseCode, 404);
  }

  /**
   * Test the blacklist administration interface.
   *
   * We don't need to check whether the blacklisting actually works
   * (i.e. blocks posts) because that is tested in testTextBlacklistAPI() and
   * testURLBlacklistAPI().
   */
  function testBlacklistUI() {
    // Log in as an administrator and access the blacklist administration page.
    $this->adminUser = $this->drupalCreateUser([
      'administer akismet',
      'access administration pages',
    ]);
    $this->drupalLogin($this->adminUser);

    // Add a word to the spam blacklist.
    $this->drupalGet('admin/config/content/akismet/blacklist/add');
    $text = $this->randomMachineName();
    $edit = [
      'value' => $text,
      'context' => 'allFields',
      'match' => 'contains',
    ];
    $this->drupalPostForm(NULL, $edit, t('Add blacklist entry'));
    $text = Unicode::strtolower($text);
    $this->assertText(t('The entry was added to the spam blacklist.'));
    $this->assertText($text);

    // Remove the word from the spam blacklist.
    $this->clickLink(t('Delete'));
    $this->drupalPostForm(NULL, [], t('Confirm'));
    $this->assertText(t('There are no entries in the blacklist.'));

    // Add a word to the profanity blacklist.
    $this->drupalGet('admin/config/content/akismet/blacklist/add');
    $text = $this->randomMachineName();
    $edit = [
      'reason' => BlacklistStorage::TYPE_PROFANITY,
      'value' => $text,
      'context' => 'allFields',
      'match' => 'contains',
    ];
    $this->drupalPostForm(NULL, $edit, t('Add blacklist entry'));
    $this->assertText(t('The entry was added to the profanity blacklist.'));
    $text = Unicode::strtolower($text);
    $this->assertText($text);

    // Remove the word from the profanity blacklist.
    $this->clickLink(t('Delete'));
    $this->drupalPostForm(NULL, [], t('Confirm'));
    $this->assertText('There are no entries in the blacklist.');
  }
}
