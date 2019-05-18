# Organic Groups : Site Routing
This module adds support to have Site specific routes.




## Functionality
Modules can require to have Site-specific routes with Site specific 
custom paths. This module provides the necessary events to provide those items.

### OG Context provider for Site routes
Context provider to detect the Group context based on the Site route

Will check if:
  - The site routes has a "og_sm_routing:site" parameter.


## Requirements
* og_sm




## Installation
1. Enable the module.
2. Add event listeners for the `SiteRoutingEvents::COLLECT` or 
   `SiteRoutingEvents::ALTER` events. You can also make use of the 
   `SiteRoutesSubscriberBase` class.




## Events
This module provides events to collect and alter site routes.


### SiteRoutingEvents::COLLECT
Event fired during route collection to allow site routes.

This event is used to add new routes based upon sites. The event listener
method receives a \Drupal\og_sm_routing\Event\SiteRoutingEvent instance.

The event listeners should add new routes to the site route collection.

```php
protected function collectRoutes(RouteCollection $collection, NodeInterface $site) {
  // For this implementation of the collect routes event we are going to
  // create a page based on whether a node is published or not.
  // This example was chosen for simplicity, creating pages based on the
  // site's published state should probably be done with custom access checks.
  // Sites routes are more useful in combination with the og_sm_config module.
  if (!$site->isPublished()) {
    return;
  }

  $route = new Route(
    '/group/node/' . $site->id() . '/published',
    [
      '_controller' => '\Drupal\og_sm_routing_test\Controller\PublishedController::published',
      '_title' => 'This is a published site.',
    ],
    [
      '_access' => 'TRUE',
    ]
  );
  $collection->add('og_sm_routing_test.published', $route);
}
```


### SiteRoutingEvents::ALTER
Event fired during route collection to allow changing site routes.

This event is used to alter existing site routes. The event listener
method receives a \Drupal\og_sm_routing\Event\SiteRoutingEvent instance.

```php
public function alterRoutes(SiteRoutingEvent $event) {
  $route = $event->getRouteCollection()->get('og_sm_routing_test.published');

  if ($route) {
    $route->setPath('/group/node/' . $event->getSite()->id() . '/is-published');
  }
}
```
