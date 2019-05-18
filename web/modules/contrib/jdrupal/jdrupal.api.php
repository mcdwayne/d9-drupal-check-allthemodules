<?php

/**
 * Alter the result of the jDrupal Connect resource.
 * @param $results
 */
function hook_jdrupal_connect_alter($results) {

  // When using jDrupal to communicate with Drupal 8 REST, the
  // first thing we usually do do is "connect" to Drupal 8 using JavaScript:

  // jDrupal.connect().then(function(data) { /* do stuff */ });

  // Under the hood this is simply a GET call to the jDrupal Connect resource
  // which is located at ?q=jdrupal/connect&_format=json and returns some JSON
  // similar to this for anonymous users:

  //  {
  //    "uid": 0,
  //    "name": "",
  //    "roles": ["anonymous"]
  //  }

  // And this for authenticated users:

  //  {
  //    "uid": "1",
  //    "name": "dries",
  //    "roles": ["authenticated", "administrator"]
  //  }

  // This hook gives modules an opportunity to add some data to the results. For
  // example this will add an associative array to the results:

  $results['my_module'] = array(
    'hello' => 'world',
    'foo' => array('bar', 'chew')
  );

  // Which will then be returned in the JSON data of the "connect" call:
  //  {
  //    "uid": "1",
  //    "name": "dries",
  //    "roles": ["authenticated", "administrator"],
  //    "my_module": {
  //      "hello": "world",
  //      "foo": [
  //        "bar",
  //        "chew"
  //      ]
  //    }
  //  }

}
