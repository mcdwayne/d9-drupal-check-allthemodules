This simple module monitors websites by making https requests
(via drupal_http_request), to provided URLs, and logs the duration, timestamp,
and response code. It leverages Drupal's Queue API and cron to schedule and make
the actual requests.

All information is exposed to views.

The module makes use of entities for both the site record and responses, so you
can use hook_entity_insert/update to do things like send notifications for
response codes other then 200 OK.

Site add form is located at admin/config/services/pinger.
