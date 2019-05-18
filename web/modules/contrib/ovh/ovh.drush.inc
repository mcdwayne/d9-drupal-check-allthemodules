<?php

/**
 * @file
 * Vehicule module (ovh).
 *
 * This file provide drush commands tasks.
 */

/**
 * Implements hook_drush_command().
 */
function ovh_drush_command() {
  $items = [];

  // Base.
  $items['ovh'] = [
    'description' => 'Exemple pour le module ovh',
  ];

  // Create an entity. Example 01.
  $items['otemp'] = [
    'description' => "Crée un entité de type TypeDeVehicule",
  ];


  return $items;
}

/**
 * Call back function of ovh.
 */
function drush_ovh() {
  drush_log("Rien à faire !!!", 'cancel');
}

/**
 * Call back function of ovh.
 */
function drush_ovh_otemp() {
  drush_log("Temp function START", 'ok');

  drush_log("Temp function END", 'ok');
}
