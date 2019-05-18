<?php

/**
 * Implements hook_drush_command().
 */
function dhis_drush_command()
{
    $items = array();
    $items['dhis-remove-entities'] = [
        'description' => 'Removes dhis2 entities (data elements, Org units)',
        'arguments' => [
            'entity_name' => 'The dhis2 entity to remove.',
        ],
        'examples' => [
            'drush dre all' => 'Removes all dhis2 entities.',
            'drush dre dx' => 'Removes data element entities only.',
            'drush dre ou' => 'Removes org unit entities only.'
        ],
        'aliases' => ['dre'],
    ];

    return $items;
}

function drush_dhis_remove_entities($arg = NULL)
{
    $tokens = ['@arg' => $arg];
    $dhisService = \Drupal::service("dhis_service");

    switch ($arg) {
        case 'dx':
            print('Deleting ' . $arg);
            $dhisService->removeDhisEntities('data_element');
            break;
        case 'ou':
            print('Deleting ' . $arg);
            $dhisService->removeDhisEntities('organisation_unit');
            break;
        case 'all':
            print('Deleting ' . $arg);
            $dhisService->removeDhisEntities('data_element');
            $dhisService->removeDhisEntities('organisation_unit');
            break;
        default:
            drush_print(dt('Wrong argument \'' . $arg . '\'. Possible arguments: dx, ou or all.'));
    }
}