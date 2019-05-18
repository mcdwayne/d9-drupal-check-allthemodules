<?php
namespace Drupal\corporatelogin\Controller;
use Drupal\Core\Controller\ControllerBase;
class ManageCAController extends ControllerBase {

    public function __construct() {

    }

    public function list() {
        $header = array(
            array('data' => t('ID'), 'field' => 'st.id'),
            array('data' => t('Email'), 'field' => 'st.email'),
            array('data' => t('Created'), 'field' => 'st.created'),
        );
        $query = db_select('corporate_login_details', 'st')
        ->fields('st', array('id', 'email', 'created'))
        ->extend('Drupal\Core\Database\Query\TableSortExtender')
        ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
        ->orderByHeader($header);
        $data = $query->execute();
        $rows = array();
        foreach ($data as $row) {
            $rows[] = array('data' => (array) $row);
        }
        $build['table_pager'][] = array(
            '#type' => 'table',
            '#header' => $header,
            '#rows' => $rows,
        );
        $build['table_pager'][] = array(
            '#type' => 'pager',
        );
        return $build;
    }
}