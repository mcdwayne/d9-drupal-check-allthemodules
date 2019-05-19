<?php

/**
 * @file
 * Contains \Drupal\timelogin\Controller\DefaultController.
 */

namespace Drupal\timelogin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Default controller for the timelogin module.
 */
class DefaultController extends ControllerBase {

    public function timelogin_manage_timeslot() {
        $header = [
            ['data' => t('ID'), 'field' => 'id', 'sort' => 'DESC'],
            [
                'data' => t('Role'),
                'field' => 'timelogin_role_id',
            ],
            ['data' => t('From time'), 'field' => 'timelogin_from_time'],
            [
                'data' => t('To time'),
                'field' => 'timelogin_to_time',
            ],
            ['data' => t('Edit')],
            ['data' => t('Delete')]
        ];
        $rows = array();
        $query = db_select('time_login', 'tl');
        $query->fields('tl');
        $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header);
        $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(5);
        $result = $pager->execute();

        foreach ($result as $row) {
            //Get all roles
            $rolesArray = \Drupal\user\Entity\Role::loadMultiple();
            $roles = array_keys($rolesArray);
            $roleName = $roles[$row->timelogin_role_id];
            $editUrl = Url::fromRoute('timelogin.timeslot_form', array('id' => $row->id, 'operation' => 'edit'));
            $edit = \Drupal::l(t('Edit'), $editUrl);
            $deleteUrl = Url::fromRoute('timelogin.timeslot_delete_confirm', array('id' => $row->id, 'operation' => 'delete'));
            $delete = \Drupal::l(t('Delete'), $deleteUrl);
            $rows[] = array(
                $row->id,
                $roleName,
                $row->timelogin_from_time,
                $row->timelogin_to_time,
                $edit,
                $delete
            );
        }
        // Create a render array ($build) which will be themed as a table with a
        // pager.
        $build['pager_table'] = array(
            '#theme' => 'table',
            '#header' => $header,
            '#rows' => $rows,
            '#empty' => t('There are no date formats found in the db'),
        );
        // attach the pager theme
        $build['pager'] = array(
            '#type' => 'pager'
        );

        return $build;
    }

}
