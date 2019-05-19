<?php

/**
 * @file
 * Contains \Drupal\time_spent_all\Controller\timeSpentController.
 */

namespace Drupal\time_spent_all\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Drupal\node\NodeInterface;
use \Drupal\user\Entity\User;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation;
use Drupal\Core\Database\Database;

/**
 * Controller routines for user routes.
 */
class timeSpentAllController extends ControllerBase {
  protected $database;

  protected $timespentall_config;

  public function __construct() {
    $this->database = \Drupal::database();
    $this->timespentall_config = \Drupal::config('time_spent_all.settings');
  }

  /**
   * Reporting time spent by a user on page
   */
  public function timeSpentallReports() {
    $ip_address =  \Drupal::request()->getClientIp();
   // $result = time_spent_all_detail_mail($ip_address);
// print_r( $result);
    $header = array($this->t('Page'), $this->t('User'), $this->t('IP Address'),$this->t('Post Date'), $this->t('Time Spent'));
    $rows = array();
    $output = array();
    $pager = $this->timespentall_config->get('time_spent_all_pager_limit');

    $query = $this->database->select('time_spent_all_page', 'tsp');
    $query->fields('tsp', array('timespent','ip','url','postdate'));
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
        $row->ip,
        $row->postdate,
        time_spent_all_sec2hms($row->timespent)
      );
    }

    $table = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array(
        'style' => 'width:630px'
      ),
      '#prefix' => $this->t("<h3>Time spent on each node page by IP</h3>"),
    );

    $output[] = $table;
    pager_default_initialize($number, $pager);

    if($number > $pager) {
      $pager_output = array('#theme' => 'pager');
      $output[] = $pager_output;
    }

    $header = array($this->t('User'), $this->t('IP'), $this->t('Time Spent'));
    $rows = array();
    $query = $this->database->select('time_spent_all_site', 'tss');
    $query->fields('tss', array('timespent',"ip"));
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
        $row->ip,
        time_spent_all_sec2hms($row->timespent)
      );
    }

    $table = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array(
        'style' => 'width:630px'
      ),
      '#prefix' => $this->t("<h3>Time spent on the entire site by each IP</h3>"),
    );

    pager_default_initialize($number, $pager);
    $output[] = $table;

    if($number > $pager) {
      $pager = array('#theme' => 'pager');
      $output[] = $pager;
    }

    $header = array($this->t('Page'), $this->t('User'), $this->t('IP Address'), $this->t('URL'),$this->t('Post Date'), $this->t('Time Spent'));
    $rows = array();
    $query = $this->database->select('time_spent_all_page', 'tsu');
      $query->fields('tsu', array('timespent','ip','url','postdate'));
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
    $postdate = $row->postdate;
    $postdate=date_create($postdate);
$postdate =  date_format($postdate,"d M ,Y h:i:sa");

      $rows[]  = array(
        $this->l($row->title, $url),
        $username,
        $row->ip,
        \Drupal::l($row->title, $url),
       $postdate,
        time_spent_all_sec2hms($row->timespent)
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

      //$userid = $account->uid;

    return $output;
  }

  /**
   * Time Spent Listing
   */
  public function timespentallListUsers() {
     $ip = "";
    if(isset($_GET["ip"])){
    $ip = htmlspecialchars($_GET["ip"]);
   }
    $output = array();
    $user_form = \Drupal::formBuilder()->getForm('Drupal\time_spent_all\Form\TimeSpentAllUserReportForm');
    $output[] = $user_form;
     /*$pager = $this->timespentall_config->get('time_spent_all_pager_limit');
    $header = array($this->t('User'), $this->t('Time Spent'));
    $rows = array();
    $query = $this->database->select('time_spent_all_site', 'tss');
    $query->fields('tss', array('timespent','ip'));
     
    $count_result= $query->execute();
    $count_result->allowRowCount = TRUE;
    $number = $count_result->rowCount();

    $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query->limit($pager);
    $query->addTag('user_access');

    $nodes = $query->execute()->fetchAllAssoc('ip');

    foreach ($nodes as $row) {
    
      $rows[] = array($row->ip,
        time_spent_all_sec2hms($row->timespent)
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
*/
    if(strlen($ip)>0){
    $tot_time = 0;
  $header = array(t("Date & Time"), t("Page"), t('Time Spent'));
  $rows = array();
   
  $pager = 10000000000;
  


  $header = array($this->t('Page'),$this->t('Date'), $this->t('Time Spent'));
    $rows = array();
    $query = $this->database->select('time_spent_all_page', 'tsu');
      $query->fields('tsu', array('timespent','ip','url','postdate'));
    $query->fields('u', array('uid'));
    $query->fields('node', array('nid', 'title'));
    $query->condition('ip',$ip,'=');
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
        $tot_time +=$row->timespent;
      $user = User::load($row->uid);
      $username = array(
        '#theme' => 'username',
        '#account' => $user,
      );
      $username = drupal_render($username);
      $url = Url::fromRoute('entity.node.canonical', array('node' => $row->nid));
      $rows[]  = array(
        $this->l($row->title, $url), 
        $row->postdate,
        time_spent_all_sec2hms($row->timespent)
      );
    }
$rows[] = array(array(
              'data' => 'Totlal Hours Spent(HH:MM:SS)',
              'colspan' => 2,
              'style' => 'text-align: center;background-color:#ed2028;color:#fff;font-weight: bold;'
            ),array('data' =>time_spent_all_sec2hms($tot_time),'style' => 'background-color:#ed2028;color:#fff;font-weight: bold;text-align: center;'));
    $table = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array(
        'style' => 'width:630px',
      ), 
    );

    $output[] = $table;
    pager_default_initialize($number, $pager);

    if($number > $pager) {
      $pager = array('#theme' => 'pager');
      $output[] = $pager;
    }


 
 
}
    return $output;
  }
  /**
   * Time Spent delete
   */
  public function timespentallDeleteUsers() {
    $output = array();
    $user_form = \Drupal::formBuilder()->getForm('Drupal\time_spent_all\Form\TimeSpentAllUserDeleteForm');
    $output[] = $user_form;
    

    return $output;
  }

  /**
   * Time spent per user on each node
   */
  public function timespentallUserDetail($user) {
    /*$userid = $user->id();
    $header = array($this->t('Post'), $this->t('User'), $this->t('Time Spent'));
    $rows = array();
    $output = array();
    $pager = $this->timespentall_config->get('time_spent_all_pager_limit');

    $query = $this->database->select('time_spent_all_page', 'tsp');
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
        time_spent_all_sec2hms($row->timespent)
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
*/
   
    $output[] = "";
        
 
    return $output;
  }

   /**
   * Saving time spent on a node.
   * @param $nid
   * @param $token
   */
  public function timespentallAjaxCheck($nid) {
    \Drupal::service('page_cache_kill_switch')->trigger();
     $ip = "";
  $ip = "";
  $ip = \Drupal::request()->getClientIp();
//$ip = "1:1:1:3";
$url = "";
    $postdate = date("Y-m-d h:i:sa");
    if(isset($_SERVER['HTTP_REFERER'])){
       $url = htmlspecialchars($_SERVER['HTTP_REFERER']);
    }
   
    $response = array();
    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $timespentall_config = \Drupal::config('time_spent_all.settings');
    $timer = (int) $timespentall_config->get('time_spent_all_timer');

    //monitors the time user spent on the site
    $query = $this->database->query("SELECT timespent FROM {time_spent_all_site} WHERE uid =" . $user->id()." AND ip = '".$ip."'");
    $timespent = $query->fetchField();

    if ($timespent) {
      //update the record, increments each time
      $result = $this->database->query("UPDATE {time_spent_all_site} SET timespent = :timespent  WHERE uid = :uid AND ip = :ip",
        array(':timespent' => ($timespent + $timer), ':uid' => $user->id, ':ip' => $ip));
    }
    else {
      //record the first time for this user

      $this->database->insert('time_spent_all_site')
        ->fields(array(
          'uid' => (int)$user->id(),
          'timespent' => $timer,
          'ip' => $ip,
        ))
        ->execute();
    }
    unset($timespent);
    unset($result);
    //monitors time spent by page
    $timespent = 0;
    if (is_numeric($nid) && $nid > 0) {
      //firstly detect if this node already has a record for this user
      $sql = "SELECT SQL_NO_CACHE timespent FROM {time_spent_all_page} WHERE nid = :nid  AND ip = :ip";
      $timespent = $this->database->query($sql, array(':nid' => $nid,':ip' => $ip))->fetchField();
  $connection = Database::getConnection();
        $query = $connection->select('time_spent_all_page', 'tsp')
            ->fields('tsp', array( 'timespent'))
            ->condition('ip',$ip,'=')
            ->condition('nid',$nid,'=');


$result = $query->execute();
$test_time = 0;
        foreach ($result as $file) {
         $test_time =  $file->timespent;
          }



 $norc = \Drupal::database()->merge('time_spent_all_page')
  ->key(array('nid' => $nid , 'ip' => $ip ,'uid' =>  $user->id()))
  ->insertFields(array(
             'uid' => $user->id(),
            'timespent' => $timer,
            'nid' => $nid,
            'ip' => $ip,
            'url' => $url,
            'postdate' => $postdate,
  ))
  ->updateFields(array(
    'timespent' => ($test_time + $timer), 
  ))->execute();

 




return array(
          '#type' => 'markup',
          '#markup' => t('Insert/Record updated successfully'),
          '#cache' => ['max_age' => 0],
        );


     
    }
  }
}