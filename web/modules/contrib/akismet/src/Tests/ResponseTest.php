<?php

namespace Drupal\akismet\Tests;

use Drupal\Core\Logger\RfcLogLevel;
use Akismet\Client\Client;

/**
 * Tests that Akismet server responses match expectations.
 * @group akismet
 */
class ResponseTest extends AkismetTestBase {
  public static $modules = ['dblog', 'akismet', 'node', 'comment', 'akismet_test_server', 'akismet_test'];

  public $disableDefaultSetup = TRUE;
  
  function setUp() {
    parent::setUp();
    $this->setKeys();
    $this->assertValidKeys();
    $this->adminUser = $this->drupalCreateUser();
  }

  /**
   * Tests Site API.
   */
  function testSiteAPI() {
    $akismet = $this->getClient();
    $info = $akismet->getClientInformation();

    // Create a new site.
    $site = array(
      'url' => 'example.com',
      'email' => 'akismet@example.com',
    );
    $result = $akismet->createSite($site);
    $this->assertAkismetWatchdogMessages();
    $this->assertTrue(!empty($result['publicKey']), 'publicKey found.');
    $this->assertTrue(!empty($result['privateKey']), 'privateKey found.');
    $this->assertSame('url', $result['url'], $site['url']);
    $this->assertSame('email', $result['email'], $site['email']);
    $this->assertTrue(!isset($result['platformName']), 'platformName not found.');
    $this->assertTrue(!isset($result['platformVersion']), 'platformVersion not found.');
    $this->assertTrue(!isset($result['clientName']), 'clientName not found.');
    $this->assertTrue(!isset($result['clientVersion']), 'clientVersion not found.');

    $site = $result;
    $akismet->publicKey = $site['publicKey'];
    $akismet->privateKey = $site['privateKey'];

    // Verify that getSite() response equals the createSite() response.
    $result = $akismet->getSite();
    $this->assertAkismetWatchdogMessages();
    $this->assertSame('publicKey', $result['publicKey'], $site['publicKey']);
    $this->assertSame('privateKey', $result['privateKey'], $site['privateKey']);
    $this->assertSame('url', $result['url'], $site['url']);
    $this->assertSame('email', $result['email'], $site['email']);
    $this->assertTrue(!isset($result['platformName']), 'platformName not found.');
    $this->assertTrue(!isset($result['platformVersion']), 'platformVersion not found.');
    $this->assertTrue(!isset($result['clientName']), 'clientName not found.');
    $this->assertTrue(!isset($result['clientVersion']), 'clientVersion not found.');

    // Test that verifying keys updates client information.
    $result = $akismet->verifyKeys();
    $this->assertAkismetWatchdogMessages();
    $this->assertIdentical($result, TRUE, 'Site was updated.');

    $result = $akismet->getSite();
    $this->assertAkismetWatchdogMessages();
    $this->assertSame('publicKey', $result['publicKey'], $site['publicKey']);
    $this->assertSame('privateKey', $result['privateKey'], $site['privateKey']);
    $this->assertSame('url', $result['url'], $site['url']);
    $this->assertSame('email', $result['email'], $site['email']);
    $this->assertSame('platformName', $result['platformName'], $info['platformName']);
    $this->assertSame('platformVersion', $result['platformVersion'], $info['platformVersion']);
    $this->assertSame('clientName', $result['clientName'], $info['clientName']);
    $this->assertSame('clientVersion', $result['clientVersion'], $info['clientVersion']);

    // Verify that the site is listed.
    // FIXME: Site listing not supported by backend yet.
    /*
    $result = $akismet->getSites();
    $this->assertAkismetWatchdogMessages();
    $found = FALSE;
    foreach ($result as $record) {
      if ($record['publicKey'] == $site['publicKey']) {
        $found = TRUE;
      }
    }
    $this->assertTrue($found, 'Site record was found in site list.');
    */

    // Verify that the site can be deleted.
    $result = $akismet->deleteSite($site['publicKey']);
    $this->assertAkismetWatchdogMessages();
    $this->assertIdentical($result, TRUE, 'Site was deleted.');

    // Verify that the site no longer appears in site list.
    $akismet->publicKey = $this->publicKey;
    $akismet->privateKey = $this->privateKey;
    // FIXME: Site listing not supported by backend yet.
    /*
    $result = $akismet->getSites();
    $this->assertAkismetWatchdogMessages();
    $found = FALSE;
    foreach ($result as $record) {
      if ($record['publicKey'] == $site['publicKey']) {
        $found = TRUE;
      }
    }
    $this->assertFalse($found, 'Deleted site no longer exists.');
    */

    // Verify that retrieving the deleted site yields a 404.
    $result = $akismet->getSite($site['publicKey']);
    $this->assertAkismetWatchdogMessages(RfcLogLevel::EMERGENCY);
    $this->assertEqual($result, 404, 'Attempt to get deleted site throws 404.');

    // Verify that authentication fails.
    $akismet->publicKey = $site['publicKey'];
    $akismet->privateKey = $site['privateKey'];
    $result = $akismet->getSite();
    $this->assertAkismetWatchdogMessages(RfcLogLevel::EMERGENCY);
    $this->assertEqual($akismet->lastResponseCode, Client::AUTH_ERROR, 'Attempt to authenticate with deleted site keys fails.');

    // Restore keys for tearDown().
    $akismet->publicKey = $this->publicKey;
    $akismet->privateKey = $this->privateKey;
  }

  /**
   * Tests akismet.checkContent().
   */
  function testCheckContent() {
    $akismet = $this->getClient();
    $data = [
      'authorName' => $this->adminUser->getAccountName(),
      'authorMail' => $this->adminUser->getEmail(),
      'authorId' => $this->adminUser->id(),
      'authorIp' => \Drupal::request()->getClientIp(),
    ];

    // Ensure proper response for 'ham' submissions.
    // By default (i.e., omitting 'checks') we expect spam and quality checking
    // only.
    $data['postBody'] = 'ham';
    $result = $akismet->checkContent($data);
    $this->assertAkismetWatchdogMessages();
    $this->assertSame('spamScore', $result['spamScore'], 0.0);
    $this->assertSame('spamClassification', $result['spamClassification'], 'ham');
    $this->assertTrue(!isset($result['qualityScore']), 'qualityScore not returned.');
    $this->assertTrue(!isset($result['profanityScore']), 'profanityScore not returned.');
    $data['id'] = $this->assertResponseID('contentId', $result['id']);

    // Ensure proper response for 'spam' submissions, re-using session_id.
    $data['postBody'] = 'spam';
    $result = $akismet->checkContent($data);
    $this->assertAkismetWatchdogMessages();
    $this->assertSame('spamScore', $result['spamScore'], 1.0);
    $this->assertSame('spamClassification', $result['spamClassification'], 'spam');
    $this->assertTrue(!isset($result['qualityScore']), 'qualityScore not returned.');
    $this->assertTrue(!isset($result['profanityScore']), 'profanityScore not returned.');
    $data['id'] = $this->assertResponseID('contentId', $result['id']);

    // Ensure proper response for 'unsure' submissions, re-using session_id.
    $data['postBody'] = 'unsure';
    $result = $akismet->checkContent($data);
    $this->assertAkismetWatchdogMessages();
    $this->assertSame('spamScore', $result['spamScore'], 0.5);
    $this->assertSame('spamClassification', $result['spamClassification'], 'unsure');
    $this->assertTrue(!isset($result['qualityScore']), 'qualityScore not returned.');
    $this->assertTrue(!isset($result['profanityScore']), 'profanityScore not returned.');
    $data['id'] = $this->assertResponseID('contentId', $result['id']);

    // Additionally enable profanity checking.
    $data['postBody'] = 'spam profanity';
    $data['checks'] = array('spam', 'quality', 'profanity');
    $result = $akismet->checkContent($data);
    $this->assertAkismetWatchdogMessages();
    $this->assertSame('spamScore', $result['spamScore'], 1.0);
    $this->assertSame('spamClassification', $result['spamClassification'], 'spam');
    $this->assertSame('qualityScore', $result['qualityScore'], 0.0);
    $this->assertSame('profanityScore', $result['profanityScore'], 1.0);
    $data['id'] = $this->assertResponseID('contentId', $result['id']);

    // Change the string to contain profanity only.
    $data['postBody'] = 'profanity';
    $data['checks'] = array('spam', 'quality', 'profanity');
    $result = $akismet->checkContent($data);
    $this->assertAkismetWatchdogMessages();
    $this->assertSame('spamScore', $result['spamScore'], 0.5);
    $this->assertSame('spamClassification', $result['spamClassification'], 'unsure');
    $this->assertSame('qualityScore', $result['qualityScore'], 0.0);
    $this->assertSame('profanityScore', $result['profanityScore'], 1.0);
    $data['id'] = $this->assertResponseID('contentId', $result['id']);

    // Disable spam checking, only do profanity checking.
    $data['postBody'] = 'spam profanity';
    $data['checks'] = array('profanity');
    $result = $akismet->checkContent($data);
    $this->assertAkismetWatchdogMessages();
    $this->assertTrue(!isset($result['spamScore']), 'spam not returned.');
    $this->assertTrue(!isset($result['spamClassification']), 'spamClassification not returned.');
    $this->assertTrue(!isset($result['qualityScore']), 'qualityScore not returned.');
    $this->assertSame('profanityScore', $result['profanityScore'], 1.0);
    $data['id'] = $this->assertResponseID('contentId', $result['id']);

    // Pass arbitrary string to profanity checking.
    $data['postBody'] = $this->randomString(12);
    $result = $akismet->checkContent($data);
    $this->assertAkismetWatchdogMessages();
    $this->assertTrue(!isset($result['spamScore']), 'spam not returned.');
    $this->assertTrue(!isset($result['spamClassification']), 'spamClassification not returned.');
    $this->assertTrue(!isset($result['qualityScore']), 'qualityScore not returned.');
    $this->assertSame('profanityScore', $result['profanityScore'], 0.0);
    $data['id'] = $this->assertResponseID('contentId', $result['id']);
  }

  /**
   * Tests results of akismet.checkContent() across requests for a single session.
   */
  function testCheckContentSession() {
    $akismet = $this->getClient();
    $base_data = [
      'authorName' => $this->adminUser->getAccountName(),
      'authorMail' => $this->adminUser->getEmail(),
      'authorId' => $this->adminUser->id(),
      'authorIp' => \Drupal::request()->getClientIp(),
    ];

    // Sequence:
    // - Post unsure content
    // - Solve CAPTCHA
    // - Post spam content
    // - Expect spamClassification 'spam' (spam always trumps)
    $this->resetResponseID();
    $content_data = $base_data;
    $content_data['postBody'] = 'unsure';
    $result = $akismet->checkContent($content_data);
    $this->assertAkismetWatchdogMessages();
    $this->assertSame('spamScore', $result['spamScore'], 0.5);
    $this->assertSame('spamClassification', $result['spamClassification'], 'unsure');
    $contentId = $this->assertResponseID('contentId', $result['id']);
    $content_data['id'] = &$contentId;

    $captcha_data = array(
      'type' => 'image',
      'contentId' => &$contentId,
      'authorIp' => $base_data['authorIp'],
    );
    $result = $akismet->createCaptcha($captcha_data);
    $this->assertAkismetWatchdogMessages();
    $captchaId = $this->assertResponseID('captchaId', $result['id']);
    $content_data['captchaId'] = &$captchaId;

    $captcha_data = array(
      'id' => &$captchaId,
      'contentId' => &$contentId,
      'authorIp' => $base_data['authorIp'],
      'authorId' => $base_data['authorId'],
      'solution' => 'correct',
    );
    $result = $akismet->checkCaptcha($captcha_data);
    $this->assertAkismetWatchdogMessages();
    $this->assertSame('solved', $result['solved'], 1);

    $content_data['postBody'] = 'spam';
    $result = $akismet->checkContent($content_data);
    $this->assertAkismetWatchdogMessages();
    $this->assertSame('spamScore', $result['spamScore'], 1.0);
    $this->assertSame('spamClassification', $result['spamClassification'], 'spam');
    $contentId = $this->assertResponseID('contentId', $result['id']);

    // @todo Enable following sequence after fixing Testing API.
    return;

    // Sequence:
    // - Post unsure content
    // - Solve CAPTCHA
    // - Post unsure content
    // - Expect spamClassification 'ham'
    // - Post ham content
    // - Expect spamClassification 'ham'
    // - Post unsure content
    // - Expect spamClassification 'ham'
    $this->resetResponseID();
    $content_data = $base_data;
    $content_data['postBody'] = 'unsure';
    $result = $akismet->checkContent($content_data);
    $this->assertAkismetWatchdogMessages();
    $this->assertSame('spamScore', $result['spamScore'], 0.5);
    $this->assertSame('spamClassification', $result['spamClassification'], 'unsure');
    $contentId = $this->assertResponseID('contentId', $result['id']);
    $content_data['id'] = &$contentId;

    $captcha_data = array(
      'type' => 'image',
      'contentId' => &$contentId,
      'authorIp' => $base_data['authorIp'],
    );
    $result = $akismet->createCaptcha($captcha_data);
    $this->assertAkismetWatchdogMessages();
    $captchaId = $this->assertResponseID('captchaId', $result['id']);
    $content_data['captchaId'] = &$captchaId;

    $captcha_data = array(
      'id' => &$captchaId,
      'contentId' => &$contentId,
      'authorIp' => $base_data['authorIp'],
      'authorId' => $base_data['authorId'],
      'solution' => 'correct',
    );
    $result = $akismet->checkCaptcha($captcha_data);
    $this->assertAkismetWatchdogMessages();
    $this->assertSame('solved', $result['solved'], 1);

    $content_data['postBody'] = 'unsure';
    $result = $akismet->checkContent($content_data);
    $this->assertAkismetWatchdogMessages();
    //$this->assertSame('spamScore', $result['spamScore'], 0.0);
    $this->assertSame('spamClassification', $result['spamClassification'], 'ham');
    $contentId = $this->assertResponseID('contentId', $result['id']);

    $content_data['postBody'] = 'ham';
    $result = $akismet->checkContent($content_data);
    $this->assertAkismetWatchdogMessages();
    //$this->assertSame('spamScore', $result['spamScore'], 0.0);
    $this->assertSame('spamClassification', $result['spamClassification'], 'ham');
    $contentId = $this->assertResponseID('contentId', $result['id']);

    $content_data['postBody'] = 'unsure';
    $result = $akismet->checkContent($content_data);
    $this->assertAkismetWatchdogMessages();
    //$this->assertSame('spamScore', $result['spamScore'], 0.0);
    $this->assertSame('spamClassification', $result['spamClassification'], 'ham');
    $contentId = $this->assertResponseID('contentId', $result['id']);
  }

  /**
   * Tests the language detection functionality at the API level.
   */
  function testCheckContentLanguage() {
    // Note that Akismet supports more languages than those tested.
    // Development server checks for the "lang-{language_code}" in content.
    $tests = [
      'lang-en With 2.7 million residents, it is the most populous city in both the U.S. state of Illinois and the American Midwest.' => array(
        'en',
      ),
      'lang-en lang-de With 2.7 million residents, it is the most populous city in both the U.S. state of Illinois and the American Midwest.  Chicago ist seit der Mitte des 19. Jahrhunderts eine wichtige Handelsstadt in den Vereinigten Staaten.' => array(
        'en',
        'de',
      ),
      '!!!!!!!!!!!!!!!!!!!!!!!!!!!' => array(
        'zxx',
      ),
    ];

    $akismet = $this->getClient();
    foreach ($tests as $string => $expected) {
      $result = $akismet->checkContent(array(
        'checks' => 'language',
        'postBody' => $string,
      ));
      // Parse result values.
      foreach ($result['languages'] as $item => $language) {
        $this->assertTrue(in_array($language['languageCode'], $expected), 'Found returned language code ' . $language['languageCode'] . ' in expected languages.');
      }
    }
  }

  /**
   * Tests akismet.getImageCaptcha().
   */
  function testGetImageCaptcha() {
    $akismet = $this->getClient();
    // Ensure we get no SSL URL by default.
    $data = array(
      'type' => 'image',
      'authorIp' => \Drupal::request()->getClientIp(),
    );
    $result = $akismet->createCaptcha($data);
    $this->assertAkismetWatchdogMessages();
    $this->assertTrue(strpos($result['url'], 'http://') === 0, t('CAPTCHA URL uses HTTP protocol.'));

    // Ensure we get a SSL URL when passing the 'ssl' parameter.
    $data['ssl'] = TRUE;
    $result = $akismet->createCaptcha($data);
    $this->assertAkismetWatchdogMessages();
    $this->assertTrue(strpos($result['url'], 'https://') === 0, t('CAPTCHA URL uses HTTPS protocol.'));
  }

  /**
   * Tests akismet.checkCaptcha().
   */
  function testCheckCaptcha() {
    $akismet = $this->getClient();
    // Ensure we can send an 'author_id'.
    // Verifying no severe watchdog messages is sufficient, as unsupported
    // parameters would trigger a XML-RPC error.
    $uid = rand();
    $data = [
      'type' => 'image',
      'authorIp' => \Drupal::request()->getClientIp(),
      'authorId' => $uid,
    ];
    $result = $akismet->createCaptcha($data);
    $this->assertAkismetWatchdogMessages();
    $data['id'] = $this->assertResponseID('captchaId', $result['id']);

    $data += array(
      'solution' => 'correct',
    );
    $result = $akismet->checkCaptcha($data);
    $this->assertAkismetWatchdogMessages();
  }
}
