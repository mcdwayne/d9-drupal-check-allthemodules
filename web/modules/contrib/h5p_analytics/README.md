# H5P Analytics (h5p_analytics)

A Drupal 8 integration of Experience API (xAPI) statements emitted by H5P content types to be captured and sent to Learning Record Store (LRS).

## Requirements

* PHP 7.0+
* Drupal 8 (8.6+)
* H5P module for Drupal
* Several additional JS libraries installed

## Installation

* Add to `/modules` directory and activate
* Make sure that required libraries are installed in `DRUPAL_WEB_ROOT/libraries`. Use `h5p_analytics.libraries.yml` as a source for information
  - [d3.js](https://d3js.org/) in `libraries/d3` with only `d3.min.js` being used
  - [c3.js](https://c3js.org/) in `libraries/c3` with only `c3.min.js` and `c3.min.css` being used
* Fill in the LRS configuration data (please note that larger batches might require more memory being available to the process)
* Setup the cron job to be triggered every 30 minutes (the internal sending logic is allowed to run for about 20 minutes)

## General logic

Module integrates with H5P on the client size (covering both internal content within normal pages and an externally embedded one). The xAPI event listener is being set up and statements are sent to the backend. The backend is capturing statements and adding those to a queue. A periodic background process will go through the queue and combine individual statements into batches with configurable size. Those would in turn be processed by the `BatchQueue` job and sent to the LRS if possible. All HTTP requests would have their failures logged, with probability of the same batch appearing multiple times under specific circumstances. Statistical data for failed or successful requests would be added to a standalone log (failures would include JSON-encoded statements batch).

## TODO

* Add better handling of different response cases (come up with a solution for request timeout).
* Make proper use of DI where appropriate instead of using `\Drupal::service()` as much as possible.
* See if it would make sense to remove the statement data from the request log after a certain period of time (storing that indefinitely seems wasteful and pointless).
* Add token checks to the xAPI statements AJAX endpoint so that it could not be easily spammed or at least protect from outside requests
* Allow downloading statement log and request log data as CSV (having possibility to select a period is a plus)
* Consider allowing statistics page to have data with period limitation capability

## Issues

* Handling of LRS responses is incomplete. Current solution might not be able to handle all the meaningful HTTP call failures that would allow retrying with the same batch (a few cases are handled, but that should be a default behaviour).
* Logging is eager and stores full batch dataset on each failed HTTP request. Might need to be discontinued or automatically removed by a cleanup procedure.
* Needs more testing with larger volumes of data outside of the local environment
