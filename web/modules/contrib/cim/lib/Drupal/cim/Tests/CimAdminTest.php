<?php

/**
 * @file
 * Definition of Drupal\cim\Tests\CimAdminTest.
 */

namespace Drupal\cim\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\cim\Crypt;
use Drupal\cim\Peer;

class CimAdminTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('cim');

  public static function getInfo() {
    return array(
      'name' => 'CIM administration',
      'description' => 'Test CIM administration page functionality.',
      'group' => 'CIM',
    );
  }

  function setUp() {
    parent::setUp();
    $this->admin_user = $this->drupalCreateUser(array('access administration pages', 'administer snapshots', 'administer site configuration'));
  }

  /**
   * Helper method to find the links to snapshots in the admin listing.
   */
  function getLinks() {
    $links = array();
    $page_links = $this->xpath('//a');
    foreach ($page_links as $link) {
      if (preg_match('{/admin/config/development/cim/snapshot/}', $link['href'])) {
        $links[] = $link;
      }
    }
    return $links;
  }

  /**
   * Test that basic snapshot administration and creation works.
   */
  function testCimAdmin() {
    $this->drupalLogin($this->admin_user);

    // Check that the snapshot created at installation is there.
    $this->drupalGet('admin/config/development/cim');
    // Look for the standard log message.
    $this->assertText('Initial snapshot at install time.', 'Initial snapshot has been created.');
    $links = $this->getLinks();
    $this->assertEqual(sizeof($links), 1, 'One snapshot found.');

    // Look at the snapshot.
    $this->drupalGet(trim($links[0]['href'], '/'));

    // Check a few random setting.
    $this->assertText('system.cron', 'Snapshot contains system.cron');
    $this->assertText('system.performance', 'Snapshot contains system.performance');
    $this->assertText('system.site', 'Snapshot contains system.site');

    // Check that creating a snapshot works.
    // First, change a setting.
    $edit = array(
      'cache' => 1,
    );
    $this->drupalPost('admin/config/development/performance', $edit, t('Save configuration'));

    $this->drupalGet('admin/config/development/cim/create');
    // Random tests of the changeset. It will contain more than the cache
    // setting due to simpletest setting other config variables after
    // enabling cim.
    $this->assertNoText('No configuration changes', 'Can create a new snapshot.');
    $this->assertNoText('system.cron', 'Snapshot does not contains system.cron');
    $this->assertText('system.performance', 'Snapshot contains system.performance');
    $this->assertText("system.performance.cache", 'Snapshot contains cache setting.');
    $this->assertNoText("preprocess_css", 'Snapshot does not contains preprocess_css setting.');

    // Take the snapshot.
    $edit = array(
      'message' => 'First snapshot.',
    );
    $this->drupalPost('admin/config/development/cim/create', $edit, t('Confirm'));
    $this->drupalGet('admin/config/development/cim');
    // Look for the log message of our newly created snapshot..
    $this->assertText('First snapshot.', 'Snapshot was created.');

    // Check that it now reports that no changes exists.
    $this->drupalGet('admin/config/development/cim/create');
    $this->assertText('No configuration changes', 'No changes since snapshot.');
  }

  /**
   * Test binding up with upstream.
   */
  function testUpstream() {
    $this->drupalLogin($this->admin_user);
    // Create a peer, with keys and the lot.
    $crypt = new Crypt();
    $crypt->keyGen();
    $peer = new Peer('Test peer', $crypt->getPublicKey(), 1, url('', array('absolute' => TRUE)));

    // Send an authentication request to the site.
    $request = array(
      'action' => 'authenticate',
      'core' => 8,
      'version' => 1,
      'public_key' => $crypt->getPublicKey(),
      'return_url' => url('', array('absolute' => TRUE)),
    );
    ksort($request);
    $request['signature'] = $crypt->sign(serialize($request));
    $auth_response = cim_request($peer->url(), $request);
    if (!($auth_response = base64_decode($auth_response)) ||
      !($auth_response = unserialize($auth_response)) ||
      !($auth_response = $crypt->open($auth_response)) ||
      !($auth_response = unserialize($auth_response))) {
      $this->fail('Could not decode response.');
      return;
    }

    // Sign the token and add it to the post request.
    $crypt->setPeerKey($auth_response['public_key']);
    $options = array(
      'query' => array(
        'token' => $auth_response['token'],
        'sig' => base64_encode($crypt->sign($auth_response['token'])),
      ),
    );
    $edit = array(
      'downstream_name' => 'test_downstream',
    );

    // Post to the upstream.
    $this->drupalPost($auth_response['authenticate_url'], $edit, t('Save configuration'), $options);
    $this->assertTrue(preg_match('/token=([^&]+)&sig=([^&]+)/', $this->getUrl(), $matches), 'Return contains token and sig.');

    // Assert that the signed token verifies, like a proper client would.
    $this->assertTrue($crypt->verify(urldecode($matches[1]), base64_decode(urldecode($matches[2]))), 'Token and sig verifies.');

    // Do a pull request. Do some random tests on the returned data.
    $request = array(
      'action' => 'pull',
    );
    $response = cim_peer_request($request, $peer, $crypt);
    $this->assertTrue($response['success'], 'Pulling works.');
    $this->assertEqual($response['message'], '', 'Empty message in response');
    $this->assertTrue($response['snapshot'] instanceof \Drupal\cim\Snapshot, 'Pull response contains a snapshot.');

    // Pull the dump.
    $request = array(
      'action' => 'blob',
      'sha' => $response['snapshot']->dump_sha(),
    );
    $blob = cim_peer_request($request, $peer, $crypt);
    $this->assertTrue($blob['success'], 'Getting dump blob works.');
    $this->assertEqual($blob['message'], '', 'Empty message in response');
    $this->assertTrue(is_array($blob['blob']), 'Blob response contains a dump.');

    // Pull the snapshot.
    $request = array(
      'action' => 'blob',
      'sha' => $response['snapshot']->changeset_sha(),
    );
    $blob = cim_peer_request($request, $peer, $crypt);
    $this->assertEqual($blob['message'], '', 'Empty message in response');
    $this->assertTrue($blob['blob'] instanceof \Drupal\cim\Changeset, 'Blob response contains a changeset.');
    $this->assertTrue($blob['success'], 'Getting changeset blob works.');
  }

  /**
   * Test binding with upstream, pulling and pushing changesets.
   */
  /* function testUpstream() { */

  /* } */
}
