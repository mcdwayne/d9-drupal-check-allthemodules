<?php
/**
 * @file
 * Contains \Drupal\jwplayer_report\Controller\ListJwplayerReportController.
 */

namespace Drupal\jwplayer_report\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Datetime;


/**
 * Controller routines for fsa_vipandreviewclients routes.
 */
class ListJwplayerReportController extends ControllerBase{

   /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('form_builder')
    );
  }

  /**
   * Constructs a DbLogController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   A module handler.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
 public function __construct(Connection $database, ModuleHandlerInterface $module_handler, FormBuilderInterface $form_builder) {
    $this->moduleHandler = $module_handler;
    $this->formBuilder = $form_builder;
  }

/**
 * List/ Download JWPLayer analytical data.
 */
  public function list_jwplayerreport($filter_string) {

  $media_id = isset($_GET['media_id']) ? $_GET['media_id'] : '';
  $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
  $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
  $month_val = isset($_GET['month_val']) ? $_GET['month_val'] : '';
  $option = isset($_GET['option']) ? $_GET['option'] : '';
  $report_range = isset($_GET['report_range']) ? $_GET['report_range'] : '';
 
    $header = array(
      array('data' => t('Date')),
      array('data' => t('Media_id')),
      array('data' => t('Embeds')),
      array('data' => t('Views')),
      array('data' => t('Time Watched')),
    );

    if ($month_val == 6) {
    $startdate = date("Y-m-d");
    $enddate = date("Y-m-d", strtotime("-6 months"));
  }
  elseif ($month_val == 12) {
    $startdate = date("Y-m-d");
    $enddate = date("Y-m-d", strtotime("-12 months"));
  }
  elseif ($month_val == 24) {
    $startdate = date("Y-m-d");
    $enddate = date("Y-m-d", strtotime("-24 months"));
  }
  else {
    $startdate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $enddate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
  }

  if (empty($month_val)) {
    $month_val = 1;
  }

  for ($i = 0; $i < $month_val; $i++) {

    if ($report_range == 4) {
      $start_date = $date_array[$i]['start_date'];
      $end_date = $date_array[$i]['end_date'];
    }
    else {
      $start_date = date("Y-m-d", strtotime( date( 'Y-m-01' ) . " -$i months"));
      $end_date = date('Y-m-t', strtotime( " -$i months"));
    }
    $config = \Drupal::config('jwplayer_report.settings');
    $api_secret_key = $config->get('api_secret_key');
    $property_name_key = $config->get('property_name_key');
    //echo $api_secret_key ."===" . $property_name_key ."<br>";
    if ($media_id != '') {
     $media_id_arr = explode("-", $media_id);
      $output = array();
      $j = 0;
    foreach ($media_id_arr as $m_ids) {

      $data = array(
        "start_date" => $start_date,
        "end_date" => $end_date,
        "dimensions" => array("media_id"),
        "metrics" => array(
          array("operation" => "sum", "field" => "embeds"),
          array("operation" => "sum", "field" => "plays"),
          array("operation" => "sum", "field" => "time_watched"),
        ),
        "page_length" => 100,
        "sort" => array(
          array("field" => "plays", "order" => "DESCENDING"),
        ),
      );

      $data["filter"] = array(
        array("field" => "media_id", "operator" => "=", "value" => array($m_ids)),
      );
      $url = "https://api.jwplayer.com/v2/sites/$property_name_key/analytics/queries/";
      
      $data = json_encode($data);
       $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization:$api_secret_key", "Content-Type: application/json"));
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $output_data = curl_exec($ch);
      curl_close($ch);
      $output[$j] = json_decode($output_data, TRUE);
      $embed = '';
      $views = '';
      $timewatched = '';
      
      for ($date_push = 0; $date_push <= $i; $date_push++) {
        if (isset($output[$j]['data']['rows'][$date_push])) {
          $embed_data []= $output[$j]['data']['rows'][$date_push][1];
          $views_data []= $output[$j]['data']['rows'][$date_push][2];
          $timewatched_data[] = $output[$j]['data']['rows'][$date_push][3];
     
          if (!empty($output[$j]['data']['rows'][$date_push][3])) {
            $output[$j]['data']['rows'][$date_push][3] = $output[$j]['data']['rows'][$date_push][3];
          }

          $startdateval = date("m-Y", strtotime($start_date));

            array_unshift($output[$j]['data']['rows'][$date_push], $startdateval);
          }
         
      }
      $j++;
    }

    }
    else {

    $data = array(
        "start_date" => $start_date,
        "end_date" => $end_date,
        "dimensions" => array("media_id"),
        "metrics" => array(
          array("operation" => "sum", "field" => "embeds"),
          array("operation" => "sum", "field" => "plays"),
          array("operation" => "sum", "field" => "time_watched"),
        ),
        "page_length" => 100,
        "sort" => array(
          array("field" => "plays", "order" => "DESCENDING"),
        ),
      );

      $data = json_encode($data);
      $url = "https://api.jwplayer.com/v2/sites/$property_name_key/analytics/queries/";
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization:$api_secret_key", "Content-Type: application/json"));
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $output = curl_exec($ch);
      curl_close($ch);
      $output = json_decode($output, TRUE);
      $arrycount  = count($output['data']['rows']);
      $embed = '';
      $views = '';
      $timewatched = '';
      
      for ($date_push = 0; $date_push <= $arrycount; $date_push++) {
        if (isset($output['data']['rows'][$date_push])) {
          $embed_data[] = $output['data']['rows'][$date_push][1];
          $views_data[] = $output['data']['rows'][$date_push][2];
          $timewatched_data[] = $output['data']['rows'][$date_push][3];
         
          if (!empty($output['data']['rows'][$date_push][3])) {
            $output['data']['rows'][$date_push][3] = $output['data']['rows'][$date_push][3];
          }
          $startdateval = date("m-Y", strtotime($start_date));
          array_unshift($output['data']['rows'][$date_push], $startdateval);
        }
      }

   }

    if ($media_id != '') {
     foreach ($output as $output_data) {
        $rows[] =array('data' => $output_data['data']['rows'][0]);
      }
    }
    else {
      $rows[] = array('data' => $output_data['data']['rows'][0]);
    }

  }
  
   
   if ($option == 'Download') {
      $file = "jwPlayerReport.csv";
      $delimiter = ";";
      $header_media_id = str_replace('-', ',', $media_id);
      header('Content-Type: application/csv');
      header('Content-Disposition: attachment; filename="' . $file . '";');
      $f = fopen('php://output', 'w');
      fputcsv($f, array("Advertiser History"));
      fputcsv($f, array("MediaId(s)", "$header_media_id" ));
      fputcsv($f, array("Start date", "$startdate" ));
      fputcsv($f, array("End date", "$enddate" ));
      fputcsv($f, array("Month", "MediaId", "Embeds", "Views", "Time Watched"));
      foreach ($rows as $key => $data) {
        foreach ($data as $line) {
          fputcsv($f, $line);
        }
      }
      $embed = array_sum($embed_data);
      $views = array_sum($views_data);
      $timewatched = array_sum($timewatched_data);
      fputcsv($f, array("Total", "", "$embed", "$views", "$timewatched"));
      exit();
    }
    elseif ($option == 'List') {

    if (empty($rows)) {
      $rows[] = array(array('data' => t('No Data Available.'), 'colspan' => 5));
    }
    
    $build['pager_table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array( 'id' => 'jwplayer_report_form_id'),
      '#empty' => t('No records available.'),
     
    );

    // Pager.
    $build['pager_pager'] = array(
    '#type' => 'pager',
    '#element' => 0,
    );
    return $build;
  }
     
  }
  
}
?>
