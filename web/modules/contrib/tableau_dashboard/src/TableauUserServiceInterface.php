<?php

namespace Drupal\tableau_dashboard;

interface TableauUserServiceInterface {

  public function search($user);

  public function insert($user);

}