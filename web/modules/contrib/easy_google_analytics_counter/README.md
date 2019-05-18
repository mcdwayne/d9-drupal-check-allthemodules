# Easy Google Analytics Counter

Drupal module get pageview data from Google analytics.

## INTRODUCTION

...

## GETTING STARTED

After install and configure module new column is been created on node_field_data
with name page_views. This column ready to using from views as field, filter and
sort data.

### REQUIREMENTS

Module needs the google/apiclient v2 library. If you install module with
composer the library will download also.

If you use other technology to install module you need to install this library
manualy.

In the project (not module!) root folder run this command:
```
composer require "google/apiclient":"^2.0"
```

### INSTALLATION

Install module with composer:
```
composer require drupal/easy_google_analytics_counter
```

### CONFIGURATION

- Go to https://developers.google.com/analytics/devguides/reporting/core/v4/quickstart/service-php
and follow point 1.
- Go to YOURSITE/admin/config/easy_google_analytics_counter/admin and enter data
  and save.
- Set cron to periodicaly running on hosting server.
- Prepare or update views to using pageviews.

## AUTHOR
Dudas Jozsef dj@brainsum.com

## SPONSORED
Tieto - https://www.tieto.com
Brainsum - https://www.brainsum.com
