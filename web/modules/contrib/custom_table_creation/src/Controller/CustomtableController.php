<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\custom_table_creation\Controller;

use Drupal\Core\Url;

class CustomtableController {

  public function cus_table_list() {
    $create_table_url = Url::fromRoute('table_creation_form');
    $create_table_link = \Drupal::l(t('+Create Table'), $create_table_url);

    $header = array('TITLE', 'NAME', 'DELETE');
    $sql = db_select('cus_table_list', 'ctl');
    $sql->fields('ctl', array());
    $rel = $sql->execute();
    foreach ($rel as $val) {
//      $edit_url = Url::fromRoute('table_creation_form');
//      $edit_link = \Drupal::l(t('Edit'), $edit_url);
      $delete_url = Url::fromRoute('table_creation_form');
      $delete_link = \Drupal::l(t('Delete'), $delete_url);

      $rows[] = array(
        $val->label,
        $val->table_name,
        //$edit_link , 
        $delete_link
      );
    }
    if (empty($rows)) {
      $rows[] = array($create_table_link, '', '', '');
    }
    $form['table'] = array(
      '#caption' => $create_table_link,
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      //'#prefix' => '<div>'.$create_table_link.'</div>',
      '#attributes' => array('style' => array('border:solid 1px',))
    );
    return $form;
  }

}
