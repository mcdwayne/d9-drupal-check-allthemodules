<?php
/**
 * @file
 * Contains \Drupal\oauth\Tests\OAuthTest.
 */

namespace Drupal\oauth\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\user\USerData;

/**
 * Tests oauth functionality.
 *
 * @group OAuth
 */
class OAuthTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'entity_test', 'oauth', 'rest');

  /**
   * Tests consumer generation and deletion.
   */
  function testConsumers() {
    // Create a user with permissions to manage its own consumers.
    $permissions = array('access own consumers');
    $account = $this->drupalCreateUser($permissions);

    // Initiate user session.
    $this->drupalLogin($account);

    // Check that OAuth menu tab is visible at user profile.
    $this->drupalGet('user/' . $account->id() . '/oauth/consumer');
    $this->assertResponse(200);

    // Generate a set of consumer keys.
    $this->drupalPostForm('oauth/consumer/add/' . $account->id(), array(), 'Add');
    $this->assertText(t('Added a new consumer.'));

    // Delete the set of consumer keys.
    $user_data = \Drupal::service('user.data')->get('oauth', $account->id());
    $this->drupalPostForm('oauth/consumer/delete/' . $account->id() . '/' . key($user_data), array(), 'Delete');
    $this->assertText(t('OAuth consumer deleted.'));

    $this->drupalLogout();

    // Test administer consumer permissions
    $admin_account = $this->drupalCreateUser(array('administer consumers'));
    $this->drupalLogin($admin_account);

    $this->drupalGet('user/' . $account->id() . '/oauth/consumer');
    $this->assertResponse(200);

    // Generate a set of consumer keys.
    $this->drupalPostForm('oauth/consumer/add/' . $account->id(), array(), 'Add');
    $this->assertText(t('Added a new consumer.'));

    // Delete the set of consumer keys.
    $user_data = \Drupal::service('user.data')->get('oauth', $account->id());

    $this->drupalPostForm('oauth/consumer/delete/' . $account->id() . '/' . key($user_data), array(), 'Delete');
    $this->assertText(t('OAuth consumer deleted.'));

    $this->drupalLogout();
  }

  /**
   * Tests OAuth authentication in requests.
   */
  function testRequestAuthentication() {
    $entity_type = 'entity_test';
    $resource = 'entity:' . $entity_type;
    $method = 'GET';
    $format = 'json';

    // Allow GET requests through OAuth on entity_test.
    $config = \Drupal::configFactory()->getEditable('rest.settings');
    $settings = array();
    $settings[$resource][$method]['supported_formats'][] = $format;
    $settings[$resource][$method]['supported_auth'][] = 'oauth';
    $config->set('resources', $settings);
    $config->save();
    $this->container->get('router.builder')->rebuild();

    // Create an entity programmatically.
    $entity_values = array(
      'name' => 'Some name',
      'user_id' => 1,
      'field_test_text' => array(
        0 => array(
          'value' => 'Some value',
          'format' => 'plain_text',
      )),
    );
    $entity = entity_create($entity_type, $entity_values);
    $entity->save();

    // Create a user account that has the required permissions to read
    // resources via the REST API.
    $permissions = array(
      'view test entity',
      'restful get entity:' . $entity_type,
      'access own consumers',
    );
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    // Generate a set of consumer keys.
    $this->drupalPostForm('oauth/consumer/add/' . $account->id(), array(), 'Add');
    // Get the consumer we just generated for the new user.
    $user_data = \Drupal::service('user.data')->get('oauth', $account->id());
    // Now send an authenticated request to read the entity through REST.
    $url = $entity->urlInfo()->setRouteParameter('_format', $format);
    $endpoint = $url->setAbsolute()->toString();
    $oauth = new \OAuth(key($user_data), $user_data[key($user_data)]['consumer_secret']);
    $oauth_header = $oauth->getRequestHeader('GET', $endpoint);
    $out = $this->curlExec(
      array(
        CURLOPT_HTTPGET => TRUE,
        CURLOPT_NOBODY => FALSE,
        CURLOPT_URL => $endpoint,
        CURLOPT_HTTPHEADER => array('Authorization: ' . $oauth_header),
      )
    );
    $this->verbose('GET request to: ' . $endpoint . '<hr />' . $out);
    $this->assertResponse('200', 'HTTP response code is 200 for successfully authenticated request.');
    $this->curlClose();
  }

  /**
   * Tests automatic consumer deletion.
   */
  function testConsumerDeletion() {
    // Create a user with permissions to manage its own consumers.
    $permissions = array('access own consumers');
    $account = $this->drupalCreateUser($permissions);

    // Initiate user session.
    $this->drupalLogin($account);

    // Generate a set of consumer keys.
    $this->drupalPostForm('oauth/consumer/add/' . $account->id(), array(), 'Add');

    // Delete the user.
    $uid = $account->id();
    $account->delete();
    // Check that its consumers were deleted.
    $consumer = \Drupal::service('user.data')->get('oauth', $uid);
    $this->assertFalse($consumer, t('Consumer keys were deleted on user deletion.'));
  }

}
