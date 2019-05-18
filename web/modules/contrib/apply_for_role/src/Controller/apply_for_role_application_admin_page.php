<?php

/**
 * @FILE:
 *
 * Create the application administration page.
 */

namespace Drupal\apply_for_role\Controller;

use Drupal\apply_for_role\application_manager;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Component\Serialization;
use Drupal\Core\Url;
use Drupal\Core\Link;

class apply_for_role_application_admin_page extends ControllerBase{

  private $application_manager;
  private $applications_per_page;

  /**
   * Constructor, loads application manager and amount of applications per page is set here.
   */
  public function __construct()
  {
    $this->application_manager = new application_manager();
    $this->applications_per_page = 20;
  }

  /**
   * Content, renders table of applications, paged.
   */
  public function content(){
    $table_header = array(
      $this->t('Application id'),
      $this->t('User Name (UID)'),
      $this->t('Roles'),
      $this->t('Status'),
      $this->t('Created'),
      $this->t('Message'),
      $this->t('Operations')
    );

    $pager_number = \Drupal::request()->query->getInt('page');

    $total_application_count = Database::getConnection()->select('apply_for_role_applications', 'a')->countQuery()->execute()->fetchField();

    $results = Database::getConnection()->select('apply_for_role_applications', 'a')
      ->fields('a')
      ->orderBy('aid', 'DESC')
      ->range(($pager_number * 20), 20)
      ->execute();

    $page = pager_default_initialize($total_application_count, $this->applications_per_page);

    $table_render =  array(
      'table' => array(
        '#type' => 'table',
        '#header' => $table_header,
        '#empty' => $this->t('Currently there are no applications available for review.'),
      ),
      'page' => array(
        '#type' => 'pager',
      ),
    );

    while($result_row = $results->fetchAssoc()){
      $table_render['table'][$result_row['aid']] = array(
        'aid' => array('#plain_text'  => $result_row['aid']),
        'uid' => array('#plain_text' => $this->display_username($result_row['uid'])),
        'rids' => array('#plain_text'  => $this->application_manager->rids_to_text(Serialization\Json::decode($result_row['rids']))),
        'status' => array('#plain_text' => $this->display_status($result_row['status'])),
        'created' => array('#plain_text' => $result_row['created']),
        'message' => array('#plain_text' => $result_row['message']),
        'operations' => array('#markup' => $this->generate_operations_links($result_row['aid'], $result_row['status'])),
      );
    }

    return $table_render;
  }

  /**
   * Display a presentable username.
   */
  protected function display_username($uid){
    $account = \Drupal\user\Entity\User::load($uid);
    return $account->getDisplayName() . ' (' . $uid . ')';
  }

  /**
   * Helper function to display a clean text representation of a status.
   */
  protected function display_status($status){
    switch ($status){
      case 0:
        return 'Needs Review';
        break;
      case 1;
        return 'Accepted';
        break;
      case 2;
        return 'Denied';
        break;
      default:
        // @TODO: Add error handling here if desired.
        return FALSE;
    }
  }

  /**
   * Helper function that returns operations links based on AID and status.
   */
  protected function generate_operations_links($aid, $status){
    if($status == 0){
      $approval_url = Url::fromRoute('apply_for_role.application_approve', array('action' => 'approve', 'aid' => intval($aid)));
      $denial_url = Url::fromRoute('apply_for_role.application_deny', array('action' => 'deny', 'aid' => intval($aid)));
      $approval_link = Link::fromTextAndUrl($this->t('Approve Application'), $approval_url);
      $denial_link = Link::fromTextAndUrl($this->t('Deny Application'), $denial_url);

      $approval_link_rendered = $approval_link->toRenderable();
      $denial_link_rendered = $denial_link->toRenderable();

      return render($approval_link_rendered) . ' | ' . render($denial_link_rendered);
    }else{
      return '';
    }
  }
}
