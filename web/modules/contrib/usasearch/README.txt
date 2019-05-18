
Contents of this file
---------------------

 * Overview
 * Installation
 * Requirements
 * Setup Tips
 * Features

Overview
--------

DigitalGov search (formerly USASearch) is an embeddable search engine that can
be used to search federal websites. A search site is configured in the DigitalGov Search
Admin Center, and that site’s handle is used by the module to access the search site.

Read more at http://search.digitalgov.gov/

DigitalGov Search offers different services:
 1. A hosted search platform, built on Elasticsearch, which includes options for searching across agency content, whether contained in the CMS, legacy file servers, social media, or government-specific sources like USAJobs and the Federal Register. The are additional search features available, such as Best Bets, configurable results page design, etc.
 2. i14y: a real-time indexing API, to ensure that the site’s content is current in DigitalGov Search’s Elasticsearch index.


#### Hosted Search
Provides a custom search block, separate from Drupal’s system search block.
When content is searched, the user will be redirected to the hosted search
solution. When they click on a result, they will be sent back to your site.
The search block has the optional “autocomplete” functionality,
using DigitalGov’s “Type-ahead” API

The hosted search results page is styled in the DigitalGov Search Admin Center.

#### i14y
This module uses the i14y API to send content directly from your Drupal
installation to DigitalGov Search for real-time indexing.

For indexing you will need an i14y drawer handle. Login at
https://search.usa.gov/sites and select Content > i14y Drawers in the
left side menu. Add an i14y Drawer and enter an i14y drawer handle or select
Show to display the i14y secret token of an existing drawer. If you don’t see the Drawers page, contact the team at search@support.digitalgov.gov to have i14y enabled for your search site.

Installation
-----------

1. Place the usasearch directory in your modules directory.
2. Enable the DigitalGov Search module at admin/modules.
3. Configure it at admin/config/search/usasearch by entering DigitalGov Search’s unique site handle for your affiliate.


Requirements
------------

 * A valid search site in the DigitalGov Search system. https://search.digitalgov.gov


Setup Tips
------------
After enabling the module, you must go to /admin/structure/block to place the
"USA Search Form" block in the desired region. You should also disable the native Drupal search block.

See also https://search.digitalgov.gov/manual/drupal-8-module-instructions.html

Customization
-------------
Developers may use an alter hook to alter the document before being sent to i14y API.
For example (in {mymodule}.module file):

function mymodule_usasearch_document_alter(&$data) {
  // Append a string to the document title
  $data['title'] = $data['title'] . ', So it goes.';
  return $data;
}

In addition, an event 'usasearch.request' has been created and can be used by
adding an event subscriber.
An example module creating an event subscriber:
https://github.com/ericpugh/drupal-example-event-subscriber


Features
--------

The module was designed to allow exporting of all the admin configuration options via Drupal 8 Configuration Management.