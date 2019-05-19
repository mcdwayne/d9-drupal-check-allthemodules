# Simple ICS Module

## Simple API for creating and downloading ICS files

### 1. Create an ICAL file by calling simple_ics_build_ical(), this returns file fid
### 2. Pass the fid through a preprocess to pass the fid to a twig template. Good example of this is:
```

/**
 *
 * Implements hook_preprocess_node().
 */
function hook_preprocess_node(&$variables) {
  if ($variables['node']->bundle() === 'event') {
    $variables['start_date_field'] = $variables['node']->field_event_start_date->getName();
    $variables['end_date_field'] = $variables['node']->field_event_end_date->getName();
    $variables['nid'] = $variables['node']->id();
  }
}


```
### 2. Create a url through a twig template by using the dynamic ical fid
```
        <a href="/simple_ics/{{ nid }}/{{ start_date_field }}/{{ end_date_field }}">Add to My Calendar</a>

```
### 3. Place twig url in template to be rendered
### 4. Have a beer, you are done!


Note that the user requesting the ics file will first convert the timezone time to the user's preference in their profile to accommodate differences
