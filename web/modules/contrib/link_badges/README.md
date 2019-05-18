# Link Badges Documentation

## Introduction
Link Badges is an API module that allows developers to add iOS-style badges to
rendered links. These are useful for things like unread counts.

Once the module is enabled, badge values can be specified by specifying a badge
in the link options array. Example:

```
$url = Url::fromUri('http://example.com', 
         array(
          'link_badge' => array(
            'id' => 'link_badge_test',
            'attributes' => array(
              'badge_class' => array('test-link-badge'),
              'wrapper_class' => array('link-badge-wrapper'),
              'text_class' => array('test-link-badge-text'),
            ),
          ),
        )
      );
$link = \Drupal::l('Example', $url);
```

## Creating New Badges
Badges can be created either with with Views or programmatically.

### Creating Badges with Views
1. Create a view on the entity type the badge will query. (nodes, users, etc.)
2. Add a Link Badge display to the view.
3. Finish your view so that it ends in a single value. Most commonly, you would 
   use a single field and Views aggregation to turn it into a COUNT.
   
### Using Views Badges on a Link
The views badge uses properties to declare which view to render. Example:

```
$url = Url::fromUri('http://example.com', 
      array(
        'link_badge' => array(
          'id' => 'views_badge', 
          'properties' => array(
            'name' => 'example_view', 
            'display_id' => 'link_badge_1'
          ),
        )
      )
    );
$link = \Drupal::l('Example', $url2);
```

### Creating Badges Programmatically
Write a class that implements LinkBadgeBadgeInterface or extends LinkBadgeBase. 
See the example in src/Plugin/LinkBadge/TestBadge.php. Make sure you include
the plugin annotation as shown in the example, and put your badge class in
src/Plugin/LinkBadge in your custom module. That should be all you need
to do, as plugin discovery will find it there and make it available.


## Badge Value Details
Badges do not have to be numeric. Text will also work. To hide the badge,
you must return NULL from your view or getBadgeValue() function. Any other
return value will be displayed. (including "false" values, such as 0)
