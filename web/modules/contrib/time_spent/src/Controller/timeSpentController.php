<?php

/**
 * @file
 * Contains \Drupal\time_spent\Controller\timeSpentController.
 */

namespace Drupal\time_spent\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Drupal\node\NodeInterface;
use \Drupal\user\Entity\User;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller routines for user routes.
 */
class timeSpentController extends ControllerBase {
  protected $database;

  protected $timespent_config;

  public function __construct() {
    $this->database = \Drupal::database();
    $this->timespent_config = \Drupal::config('time_spent.settings');
  }

  /**
   * Reporting time spent by a user on page
   */
  public function timeSpentReports() {
    $header = array($this->t('Post'), $this->t('User'), $this->t('Time Spent'));
    $rows = array();
    $output = array();
    $pager = $this->timespent_config->get('time_spent_pager_limit');

    $query = $this->database->select('time_spent_page', 'tsp');
    $query->fields('tsp', array('timespent'));
    $query->fields('u', array('uid'));
    $query->fields('node', array('nid', 'title'));
    $query->join('node_field_data', 'node', 'node.nid = tsp.nid');
    $query->join('users_field_data', 'u', 'u.uid = tsp.uid');

    $count_result= $query->execute();
    $count_result->allowRowCount = TRUE;
    $number = $count_result->rowCount();

    $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query->limit($pager);
    $query->addTag('node_access');

    $nodes = $query->execute()->fetchAllAssoc('nid');
    foreach ($nodes as $row) {
      $user = User::load($row->uid);
      $username = array(
        '#theme' => 'username',
        '#account' => $user,
      );
      $username = drupal_render($username);
      $url = Url::fromRoute('entity.node.canonical', array('node'=> $row->nid));
      $rows[] = array(
        $this->l($row->title, $url),
        $username,
        time_spent_sec2hms($row->timespent)
      );
    }

    $table = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array(
        'style' => 'width:630px'
      ),
      '#prefix' => $this->t("<h3>Time spent on each node page by users</h3>"),
    );

    $output[] = $table;
    pager_default_initialize($number, $pager);

    if($number > $pager) {
      $pager_output = array('#theme' => 'pager');
      $output[] = $pager_output;
    }

    $header = array($this->t('User'), $this->t('Time Spent'));
    $rows = array();
    $query = $this->database->select('time_spent_site', 'tss');
    $query->fields('tss', array('timespent'));
    $query->fields('u', array('uid', 'name'));
    $query->join('users_field_data', 'u', 'u.uid = tss.uid');

    $count_result= $query->execute();
    $count_result->allowRowCount = TRUE;
    $number = $count_result->rowCount();

    $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query->limit($pager);
    $query->addTag('node_access');

    $nodes = $query->execute()->fetchAllAssoc('name');
    $number = count($nodes);

    foreach ($nodes as $row) {
      $user = User::load($row->uid);
      $username = array(
        '#theme' => 'username',
        '#account' => $user,
      );
      $username = drupal_render($username);
      $rows[] = array(
        $username,
        time_spent_sec2hms($row->timespent)
      );
    }

    $table = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array(
        'style' => 'width:630px'
      ),
      '#prefix' => $this->t("<h3>Time spent on the entire site by each user</h3>"),
    );

    pager_default_initialize($number, $pager);
    $output[] = $table;

    if($number > $pager) {
      $pager = array('#theme' => 'pager');
      $output[] = $pager;
    }

    $header = array($this->t('Post'), $this->t('User'), $this->t('Time Spent'));
    $rows = array();
    $query = $this->database->select('time_spent_page', 'tsu');
    $query->fields('tsu', array('timespent'));
    $query->fields('u', array('uid'));
    $query->fields('node', array('nid', 'title'));
    $query->join('node_field_data', 'node', 'node.nid = tsu.nid');
    $query->join('users_field_data', 'u', 'u.uid = tsu.uid');
    $query->orderBy('node.nid', 'DESC');

    $count_result = $query->execute();
    $count_result->allowRowCount = TRUE;
    $number = $count_result->rowCount();

    $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query->limit($pager);
    $query->addTag('node_access');

    $nodes = $query->execute()->fetchAll();
    foreach ($nodes as $row) {
      $user = User::load($row->uid);
      $username = array(
        '#theme' => 'username',
        '#account' => $user,
      );
      $username = drupal_render($username);
      $url = Url::fromRoute('entity.node.canonical', array('node' => $row->nid));
      $rows[] = array(
        $this->l($row->title, $url),
        $username,
        time_spent_sec2hms($row->timespent),
      );
    }

    $table = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array(
        'style' => 'width:630px',
      ),
      '#prefix' => $this->t("<h3>Time spent by user on individual page</h3>"),
    );

    $output[] = $table;
    pager_default_initialize($number, $pager);

    if($number > $pager) {
      $pager = array('#theme' => 'pager');
      $output[] = $pager;
    }

    return $output;
  }

  /**
   * Time Spent Listing
   */
  public function timespentListUsers() {
    $output = array();
    $user_form = \Drupal::formBuilder()->getForm('Drupal\time_spent\Form\TimeSpentUserReportForm');
    $output[] = $user_form;
    $pager = $this->timespent_config->get('time_spent_pager_limit');
    $header = array($this->t('User'), $this->t('Time Spent'));
    $rows = array();
    $query = $this->database->select('time_spent_site', 'tss');
    $query->fields('tss', array('timespent'));
    $query->fields('u', array('uid', 'name'));
    $query->join('users_field_data', 'u', 'u.uid = tss.uid');

    $count_result= $query->execute();
    $count_result->allowRowCount = TRUE;
    $number = $count_result->rowCount();

    $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query->limit($pager);
    $query->addTag('user_access');

    $nodes = $query->execute()->fetchAllAssoc('name');

    foreach ($nodes as $row) {
      $user = User::load($row->uid);
      $url = Url::fromRoute('time_spent.user_report', array('user'=> $user->id()));
      $rows[] = array(
        $this->l($user->getUsername(), $url),
        time_spent_sec2hms($row->timespent)
      );
    }

    $table = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array(
        'style' => 'width:600px'
      ),
      '#prefix' => $this->t("<h3>Time spent on the entire site by each user</h3>"),
    );

    pager_default_initialize($number, $pager);
    $output[] = $table;

    if($number > $pager) {
      $pager = array('#theme' => 'pager');
      $output[] = $pager;
    }

    return $output;
  }

  /**
   * Time spent per user on each node
   */
  public function timespentUserDetail(UserInterface $user) {
    $userid = $user->id();
    $header = array($this->t('Post'), $this->t('User'), $this->t('Time Spent'));
    $rows = array();
    $output = array();
    $pager = $this->timespent_config->get('time_spent_pager_limit');

    $query = $this->database->select('time_spent_page', 'tsp');
    $query->fields('tsp', array('timespent'));
    $query->fields('u', array('uid'));
    $query->fields('node', array('nid', 'title'));
    $query->condition('u.uid',$userid,'=');
    $query->join('node_field_data', 'node', 'node.nid = tsp.nid');
    $query->join('users_field_data', 'u', 'u.uid = tsp.uid');

    $count_result= $query->execute();
    $count_result->allowRowCount = TRUE;
    $number = $count_result->rowCount();

    $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query->limit($pager);
    $query->addTag('user_access');

    $nodes = $query->execute()->fetchAllAssoc('nid');

    $username = array(
      '#theme' => 'username',
      '#account' => $user,
    );
    $username = render($username);

    foreach ($nodes as $row) {
      $url = Url::fromRoute('entity.node.canonical', array('node'=> $row->nid));
      $rows[] = array(
        $this->l($row->title, $url),
        $username,
        time_spent_sec2hms($row->timespent)
      );
    }

    $table = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array(
        'style' => 'width:600px'
      ),
      '#prefix' => $this->t("<h3>Time spent on each node page by user</h3>"),
    );

    pager_default_initialize($number, $pager);
    $output[] = $table;

    if($number > $pager) {
      $pager = array('#theme' => 'pager');
      $output[] = $pager;
    }

    return $output;
  }

  /**
   * Saving time spent on a node.
   * @param $nid
   * @param $token
   */
  public function timespentAjaxCheck($nid) {
    $response = array();
    $user= \Drupal::currentUser();
    $timespent_config = \Drupal::config('time_spent.settings');
    $timer = (int) $timespent_config->get('time_spent_timer');

    //monitors the time user spent on the site
    $query = $this->database->query("SELECT timespent FROM {time_spent_site} WHERE uid =" . $user->id());
    $timespent = $query->fetchField();

    if ($timespent) {
      //update the record, increments each time
      $result = $this->database->query("UPDATE {time_spent_site} SET timespent = :timespent  WHERE uid = :uid",
        array(':timespent' => ($timespent + $timer), ':uid' => $user->id()));
    }
    else {
      //record the first time for this user

      $this->database->insert('time_spent_site')
        ->fields(array(
          'uid' => (int)$user->id(),
          'timespent' => $timer,
        ))
        ->execute();
    }
    unset($timespent);
    unset($result);
    //monitors time spent by page
    if (is_numeric($nid) && $nid > 0) {
      //firstly detect if this node already has a record for this user
      $sql = "SELECT timespent FROM {time_spent_page} WHERE nid = :nid AND uid = :uid";
      $timespent = $this->database->query($sql, array(':nid' => $nid, ':uid' => $user->id()))->fetchField();

      if ($timespent) {
        //update the record, increments each time
        $result = $this->database->query("UPDATE {time_spent_page} SET timespent= :timespent WHERE nid = :nid AND uid = :uid",
          array(':timespent' => ($timespent + $timer), ':nid' => $nid, ':uid' => $user->id()));

        return array(
          '#type' => 'markup',
          'value' => t('Record updated successfully!')
        );
      }
      else {
        //record the first time for this user at this node page
        $this->database->insert('time_spent_page')
          ->fields(array(
            'uid' => (int)$user->id(),
            'timespent' => $timer,
            'nid' => $nid
          ))
          ->execute();

        return array(
          '#type' => 'markup',
          'value' => t('Record inserted successfully!')
        );
      }
    }
  }
}
