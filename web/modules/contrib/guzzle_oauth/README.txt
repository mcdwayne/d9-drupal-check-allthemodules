This module includes the Guzzle OAuth plugin through Libraries API.

INSTALLATION
============
1. Create a directory called guzzle_oauth under any of the directories
that Libraries API. For example, [drupal root]/libraries/guzzle_oauth.
2. Download and extract the contents of the Guzzle Oauth plugin into
the directory created in the previous step. The end result should be:

[drupal root]/libraries/guzzle_oauth/plugin-oauth/OauthPlugin.php]

The plugin can be found at
https://github.com/guzzle/plugin-oauth/archive/master.zip

3. Enable the module.
4. Open the site and verify that the Status Report (Admin > Reports > Status)
shows that the library was successfully loaded.

USAGE
=======

Here is an example where we pull tweets from a Twitter account:

<?php
/**
 * @file
 * Contains \Drupal\twitter\Controller\TwitterController.
 */
namespace Drupal\twitter\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Guzzle\Plugin\OAuth\OAuthPlugin;

/**
 * Controller routines for Twitter module.
 */
class TwitterController implements ContainerInjectionInterface {

  /**
   * Test action.
   */
  public function test() {
    libraries_load('guzzle_oauth');
    $client = \Drupal::httpClient();
    $client->setBaseUrl('https://api.twitter.com/1.1');
    $client->addSubscriber(new OauthPlugin(array(
      'consumer_key'  => '***',
      'consumer_secret' => '***',
      'token'       => '***',
      'token_secret'  => '***',
    )));

    $tweets = json_decode($client->get('statuses/user_timeline.json')->send()->getBody());

  }

}
