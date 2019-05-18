Googalytics Module
-------------------

**Googalytics** provides flexible Google Analytics integration in Drupal that is
easily extensible by other modules.

No inline Javascript is used, to enable the use of
[Content Security Policy](https://developer.mozilla.org/en-US/docs/Web/Security/CSP)
for the prevention of XSS attacks.


## Basic Configuration

The default module configuration allows specifying a Google Analytics
Tracking ID, and optionally enabling additional plugins.

Each of the default options can be individually disabled if custom behaviour is
provided by another module, or all default behaviour can be disabled completely.

## Providing Custom Commands

### Collect Event

To add additional commands, create an Event Subscriber for the
`\Drupal\ga\AnalyticsEvents::COLLECT` event.  The subscriber will be sent an
instance of `\Drupal\ga\Event\CollectEvent`.

### Command Registry

The `ga.command_registry` service is available as a convenience for adding
commands within procedural code.


### Ordering Commands

Each command has a priority that determines the order that it is added to the
command queue.  The default values are

 - Create: 300
 - Require: 250
 - Set: 100
 - Generic: 0
 - Pageview: -5
 - Send, Event: -10

If a set of commands need to maintain a consistent order among other commands,
add them within a `\Drupal\ga\AnalyticsCommand\Group` instance with the
appropriate priority.
