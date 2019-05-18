## More aggressive profile logging

This module currently logs Blackfire profiles in an event listener for KernelEvents::REQUEST. It will be triggered on every request, except:

* When an earlier listener responds to the event, such as Dynamic Page Cache
* When a middleware responds before the event is triggered, such as Page Cache

This is appropriate, since we don't want to have high overhead. But we could have a switch to enable aggressive logging via a high-priority middleware. This would be higher overhead, but would make sure every single profile is logged.

## Log non-profiled requests

In addition to logging requests that are being profiled, we could also log every single request made. This would help identify what pages should be profiled.

* Maybe this would integrate with `statistics` module?
* The load time could be logged for each page.
* There should be a setting for the maximum number of logs, so the DB isn't overwhelmed.

This would be particularly useful for POSTs and AJAX requests, that are hard to profile normally.

### Find slow pages

We could keep more advanced statistics in order to find particularly slow pages, and recommend that they be profiled.

* Requests should be grouped, probably just by URL. 
* Pages that are hit often or are slow should be prioritized. Perhaps use total time taken as a metric?
* Outliers should be ignored, eg: when caches are empty.

### Copy as cURL

For each request logged (both profiles and non-profiles), we could allow the user to access a cURL command that simulates the request. This would help reproducibility, and would make it easier to profile POSTS and AJAX requests.

Include:

* HTTP Method
* URL, including parameters
* Post data
* Cookies
* Miscellaneous headers: Accept, Accept-Encoding, Accept-Language, User-Agent, Origin, Referer...
* Clear Blackfire headers, since this will be for a new request
* Clear any XDebug cookie or parameter, since it interferes with Blackfire

### Disable certain caches

Sometimes cached performance is good, but the cache is cleared often. For example, if you have a view over nodes, every single node edit will invalidate the view's cache. This is hard to profile with Blackfire—you get good statistics only when Blackfire takes multiple samples ("Aggregation"), but only the first sample will be uncached.

This is possible to work around by simulating the uncached behavior you're interested in. This module could offer to take care of it, by:

* Disabling page cache
* On each request, invalidating certain cache tags.

## Webprofiler integration

The webprofiler module, part of [Devel](http://drupal.org/project/devel), already provides some useful information. Maybe we could integrate somehow?

* Add Blackfire statistics to the toolbar.
* Offer to perform a Blackfire profile of this page.
* Allow clicks on timeline to go to Blackfire instead of editor.

## Integrations with Blackfire

### Detect bad configuration

We could warn the user if Blackfire isn't enabled, or isn't properly set up.

### Dev mode

Blackfire has a concept of "Development mode". You can say that a Blackfire environment is in dev mode, which disables certain recommendations that aren't appropriate.

We could offer a setting for this in Drupal, but I don't think there's a good way to communicate it to Blackfire.

### More robust URLs

Currently, this module uses a heuristic to find the URL of the Blackfire profile. We could try to use the Blackfire SDK for this instead.

* It's unclear how to get the URL while a profile is still running.
* Can we get this info without making the user install the SDK?
    * Some Blackfire code is available with just the PHP extension.

### API integration

Blackfire has no public API, but the SDK serves as an API of sorts. Maybe we could use it to show the recommendations directly in Drupal!

Maybe we could install the SDK as a dependency of this module?

### Detect tier

Some features of this module may require a paid tier of Blackfire. Maybe we can detect that, and do something useful: Mark the settings as "PRO"?

### Auto-trigger profiles

We could trigger profiles of every single request, during development. Not sure if this is possible.

This could be particularly useful when combined with a crawler like [SiteDiff](http://github.com/evolvingweb/sitediff).

#### Custom blackfire.yml

We could generate a `blackfire.yml` to add metrics or tests that are useful for certain Drupal 8 sites. But not ones that are useful for all sites, since those should go into Blackfire's default recommendations.

## Admin settings

* Aggressive logging.
* Log all page requests.
* Caches to disable.
* Limit the number of profiles stored.
* Limit the number of page requests stored. See above.
