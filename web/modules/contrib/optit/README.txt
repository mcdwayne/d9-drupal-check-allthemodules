1. OVERVIEW
===========
Opt It Mobile is a text message marketing application. This module provides API integration with it. It allows admins
to create keywords and interests, subscribe users to them and then message all subscribers in a single act.


2. INSTALLATION AND SETUP
=========================
The module requires PHP 5.3.0 or newer.

Install as usual, see http://drupal.org/node/70151 for further information.

Once the Opt It Mobile: Core module is set up, go to `admin/config/services/optit` and enter your Opt It
username and password.

In order to allow sending messages, enable Opt It Mobile: Send. For bulk messaging, enable Opt It Mobile: Bulk actions.


3. API
======
The core module includes Optit class which handles consumption of the API. The module has a handy optit_instantiate()
function, which works as a class loader, instantiates Optit class with API access details and returns its object. The
function uses Drupal caches to emulate Singleton design pattern.

All getter API calls return entity objects (NB: Entity as a design pattern, not "Drupal entity") or arrays of entity
objects. All setter API calls return TRUE or FALSE depending on success of the API call.


3.1 First steps consuming the API
---------------------------------
<?php

// First you want to instantiate the Optit class.
$optit = optit_instantiate();

// Next, you may want a list of all keywords. The list is actually an enumerated array of Keyword entity objects.
$keywords = $optit->keywordsGet();

// Each entity object (Keyword included) has a handy toArray() method which allows easier debugging or access to all
// properties of the object. So, say you want to loop through received keywords and print_r them.
foreach ($keywords as $keyword) {
  print_r($keyword->toArray());
}

// You might notice that each keyword (among the others) has internal_name and keyword_name properties. If you want to
// access the individual property, use get($propertyName) method. get() method exists for all entities.
foreach ($keywords as $keyword) {
  print "Keyword name is: " . $keyword->get('keyword_name');
}

?>


3.2 Pagination
--------------
The Optit class supports pagination on all getters which return multiple entity results. Class defaults to first page.

<?php

// Instantiate the Optit class.
$optit = optit_instantiate();

// Get the first page of the keywords list.
$keywords1 = $optit->setPage(1)->keywordsGet();

// After keywordsGet() method, Optit object changes its two properties: totalPages and currentPage. These properties
// allow you to either loop through all pages and get all keywords or to build some nice Drupal pagination.
// Let's really get ALL keywords in the API.

$page = 1;
$keywords = array();

while (true) {
  $keywords += $optit->setPage($page)->keywordsGet();
  if($optit->totalPages == $optit->currentPage) {
    break;
  }
  $page++
}

print_r($keywords);

?>


3.3 All the entities...
-----------------------
Opt It API currently supports following entities:
- Keyword
- Interest
- Member
- Subscription

Current state of the API is still not object-oriented enough to load a Member, load a Keyword, instantiate a new
Subscription, feed it with Member and Keyword reference and save it, but that is the direction in which I'd like to
develop the code. For the time being, these entities are only instantiated on API getters.


3.4 Reference and advanced documentation
----------------------------------------
Each public method of the Optit class is well documented. Please check annotations above methods for the reference.
Please be free to report any problem or ask for support in the issue queue at Drupal.org.


4 CONTACT
=========

Branislav Bujisic - http://drupal.org/user/52799


5 THANKS TO
===========

Opt It, Inc.
  Mobile marketing SaaS
