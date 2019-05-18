Drupal Yext
=====

[![CircleCI](https://circleci.com/gh/dcycle/drupal_yext.svg?style=svg)](https://circleci.com/gh/dcycle/drupal_yext)

A Drupal 8 module which allows you to import data from Yext.

Usage
-----

### Step 1: Install as you would any Drupal module:

    drush dl drupal_yext

### Step 2: Make sure you have a node in which to save Yext data:

Each Yext record will create a new node. Make sure you create a node type which will correspond to Yext records, and a field which will contain the Yext unique ID.

For the sake of this example, we will use "article" as a node type, and, as a field, we will create a new field of type "Text (plain)" in article with the machine name "field_yext_unique_id".

Also create a field which will contain information about when the corresponding record was last updated on Yext; call this field something like "field_yext_last_updated".

### Step 2: Configure Drupal Yext:

Go to /admin/config/yext/yext, and add the following:

* Basic node information: target node type: **article**;
* Basic node information: target ID field: **field_yext_unique_id**;
* Basic node information: target last updated field: **field_yext_last_updated**;
* Yext base URL: https://api.yext.com for dev, or https://liveapi.yext.com for prod;
* Your account number: "me" seems to always work; you can also enter your actual account number;
* Yext API key: enter your API key from Yext;

Make sure that pressing the "Test the API key" results in success message.

**Save the form** for changes to take effect.

### Step 3: Import data:

You can use the Yext Import Status in /admin/config/yext/yext to import data day-by-day.

If that times out, you can also use:

    drush ev \'drupal_yext_import_some()\'"

This will update the "Next check date for Yext" by one day, and import data for taht day. If there is no data which was updated on that day, nothing will be imported! **So you will need to run 30, 40 times** to make sure it works!

When you are sure it works, you can then add a cron job, say, every 15 mintues on your environment, with

    drush ev \'drupal_yext_import_some()\'"

Issue queue and pull requests
-----

Please use the [Drupal issue queue](https://www.drupal.org/project/issues/search/drupal_yext) for this project.

Please run tests by running `./scripts/test.sh` (you do not need to install or configure anything except Docker to run this) on your proposed changes before suggesting patches. Use [GitHub](https://github.com/dcycle/drupal_yext) for pull requests.

Adding new mapping once you already have data
-----

If you add new mapping but already have nodes in your system, you can run:

    drush ev '\Drupal\drupal_yext\Yext\Yext::instance()->resaveAllExisting()'

Be careful as this will **overwrite** all fields in your target node type with data taken from the Yext raw API output in your node's "yext raw data" field. Back up your database before trying this, please.

If you want to delete all existing nodes of the target type, obviously back up your database, and run:

    drush ev '\Drupal\drupal_yext\Yext\Yext::instance()->deleteAllExisting()'

This might be useful if you want to reset the importer and start from scratch.

Development
-----

The code is available on [GitHub](https://github.com/dcycle/drupal_yext) and [Drupal.org](https://www.drupal.org/project/drupal_yext).

Automated testing is on [CircleCI](https://circleci.com/gh/dcycle/drupal_yext).

To install a local version for development or evaluation, install Docker and run `./scripts/deploy.sh`.

Debugging
-----

If you know a Doctor's ID, and want to fetch it from Yext, you can go to /devel/php and run:

    $id = 123456;
    $y = Drupal\drupal_yext\Yext\Yext::instance();
    $url = $y->buildUrl('/v2/accounts/me/locations/' . $id);
    $body = (string) $y->httpGet($url)->getBody();
    dpm(json_decode($body, TRUE));

If you are not sure if a particular field (say, 12819) or a bio is actually importing, but you have lots of content which does not have these fields or a bio, you can create a view which filters by "yext raw data contains:" and set it to only display content which has your field or a description.
