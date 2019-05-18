<?php

/**
 * Defines trails that can be followed.
 *
 * @return array
 */
function hook_navigation_trail() {
  $trails = array();
  $trails['trail_machine_name'] = array(
    'title' => 'My trail',
    'description' => 'My description for this trail',
    'trail' => "
      /
      node
      admin/index",
  );

  return $trails;
}


function hook_navigation_trail_alter() {

}
