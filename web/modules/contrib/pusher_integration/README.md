# Pusher Integration
Pusher Integration is a Drupal 8 module designed to provide a robust interface for integrating Pusher.com services into Drupal modules and apps. By itself, it doesn't do anything, but rather is a tool for Drupal developers to incorporate realtime message broadcasting into their modules and applications. There are/were some similar modules for Drupal 7, but as of now, they have not yet been ported for Drupal 8, which is why we wrote this one!

NOTE: If you are reading this on Github, we recommend that you visit our <a href="https://www.drupal.org/project/pusher_integration">official project page</a> on Drupal.org and install the module as you would any other Drupal module. Code that is committed here at GitHub should be considered in development/bleed edge/etc. 
 
# Requirements

* Drupal 8.x
* PHP 5.5 or higher (untested under PHP 7)
* An account at Pusher.com (at least a free sandbox account for smaller sites)
* Composer Manager module installed

# Known Issues

* None at this time

# Installation Instructions

1. Download and install the module (./modules/custom/push_integration)
2. Run an update with Drush to pull in dependencies: "drush up" (Be sure to have the Composer Manager module installed!)
3. Configure the module (admin/config/pusher_integration)
4. Install whatever other module needs it and go from there

# Configuration

The configuration options can be accessed via the normal admin configuration menu (under "Pusher Integration Options"), or by visiting /admin/config/pusher_integration.

## Pusher.com Connection Settings

This is pretty straightforward. Just plug in the configuration values that Pusher.com gives you for your app.

## Miscellaneous Settings

Currently, there is only one option in this section, and it will allow you to enable debug logging to the Drupal Watchdog logger. Note: this is not recommended for production!

## Channel Configuration

This section requires a bit more explanation. To use Pusher services, you must create a mapping between Pusher channels and your pages/paths. In essence, you configure channels for specific paths on your site. Click the "Channel-Path-Map" tab to access the mapping page. To add a new entry, simply provide a Pusher channel name and a "path pattern". Since the path patterns support regex, you can quickly and easily create global channels that affect your whole site, or just certain sections of your site.

CHANNEL_NAME can be:

    presence-SOMESTRING: to create a presence channel
    private-SOMESTRING: to create a private channel
    SOMESTRING: Without "presence-" or "private-" in it, to create a public channel

For example, our SiteCommander module, which will be published soon, supports Pusher for message broadcasting. It requires a public channel simply called "site-commander"
to be setup for all pages on the site. Simply use "site-commander" (no quotes) for the channel name, and a path pattern of ".*" (again, no quotes), and you're all set!

As another example, let's say you wanted to create a private channel, but only on a page at "/super/secret/path". You would simply use "private-my-secret-channel" (or whatever you want to call it) for the channel name, and "/super/secret/path" for the path pattern.

You get the idea.

# Usage Information for Developers

## Server-Side PHP

On the server side, in your app or module, you can simply broadcast commands as follows:

```php
  use Drupal\pusher_integration\Controller\PusherController;
  ...
  $this->pusher = new PusherController( $configFactory, $currentUser );
  $data = array(
    'someVar' => 'Some value',
    'anotherVar' => 'Some other value'
  );

  // Broadcast an event to a single channel
  $this->pusher->broadcastMessage( $this->configFactory, 'my-channel-name-here', 'my-event-name-here', $data );
  ...
  // Broadcast an event to an array of channels
  $this->pusher->broadcastMessage( $this->configFactory, array('my-channel-name-here', 'channel2'), 'my-event-name-here', $data );
  ...
  // Get info on a specific channel
  $this->pusher->getChannelInfo( 'my-channel-name-here' );
  ...
  // Get list of channels
  $this->pusher->getChannelList();
  ...
  // Send any generic Pusher.com REST command
  $this->pusher->get('/channels');
```


Here is a more pseudo-complete example, with dependency injection:

```php
<?php

namespace Drupal\my_module\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Session\AccountInterface;

use Drupal\pusher_integration\Controller\PusherController;

class MyController extends ControllerBase {

  protected $configFactory;
  protected $currentUser;
  protected $pusher;

  public function __construct( ConfigFactory $configFactory, AccountInterface $account )
  {
    $this->configFactory = $configFactory;
    $this->currentUser = $account;

    $this->pusher = new PusherController( $configFactory, $currentUser );
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('current_user'),
    );
  }

  ...

  public function foo()
  {
		...

    $data = array(
      'someVar' => 'Some value',
      'anotherVar' => 'Some other value'
    );

    $this->pusher->broadcastMessage( $this->configFactory, 'my-channel-name-here', 'my-event-name-here', $data );

		...
  }

	...
}
```

## Client-side Javascript

This module will create a global Javascript object simply called "pusher". You may use that to access the Pusher connection that is created automatically for you on page loads. Additionally, it will create the following global Javascript variables that can be used to access various Pusher channels:

*pusher*
  the global Pusher object

*pusherChannels*
  array of public channels the user is subscribed to.
  
*privateChannel*
  array of private channels the user is subscribed to.

*presenceChannel*
  array of presence channels the user is subscribed to.
  
```javascript
...
  // Bind to the "my-event-name-here" event on the private channel, so we can listen for it to come across the wire!
  privateChannels[index].bind('my-event-name-here', function(data) {
	  // Access your event information via the "data" object once the event is received by the client/browser
	  console.log( data );
  });
...
```

Here is an example of creating your own channel and subscribing to an event:
  
```javascript
var myChannel;

if (pusher)
{
  myChannel = pusher.subscribe('my-channel-name-here');

  // Bind to the "my-event-name-here" event, so we can listen for it to come across the wire!
  myChannel.bind('my-event-name-here', function(data) {
	  // Access your event information via the "data" object once the event is received by the client/browser
	  console.log( data );
  });

}
```

If you have the need, you can also trigger/broadcast events straight from your app via Javascript as well:

```javascript
var triggered = publicChannel.trigger('some-event-name', { your: data });
```

In order for this to work, be sure to enable client events inside your app settings at Pusher.com.
