<?php

namespace Drupal\track_da_files\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Database\Query\SelectExtender;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
//use Drupal\Core\File\FileSystem;
//use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TrackDaFilesController.
 *
 * @package Drupal\track_da_files
 */

  class TrackDaFilesController extends ControllerBase {

    /**
     * Builds a table which shows number of displays by file.
     */
    public function table($fid = NULL, $pid = NULL) {

      $output = '';
      $variables = '';

      if (!empty($fid) && !empty($pid)) {

        $build['table'] = $this->table_build_file_report($fid, $pid);
        $file = File::load($fid);
        $filename  = $file->getFilename();
        $build['table']['#prefix'] = '<p>' . t('Datas for @filename', array('@filename' => $filename)) . '</p>';
        //$build['table_action_form'] = \Drupal::formBuilder()->getForm('\Drupal\track_da_files\Form\TrackDaFilesTableActionForm'/*, $extra*/);
      }
      else {
        $build['table'] = $this->table_build();
        //$build['table_action_form'] = \Drupal::formBuilder()->getForm('\Drupal\track_da_files\Form\TrackDaFilesTableActionForm', 'buildForm');
      }

      return $build;
    }

	  /**
	   * Builds a table which shows datas for a specific user.
	   */
	  public function table_user_report($uid) {

	  	$output = '';

	    $account = \Drupal\user\Entity\User::load($uid);

	    if ($uid == '0') {
	      $output .= '<p>' . t('Datas for anonymous users') . '</p>';
	    }
	    else {
	    		$name = $account->getUsername();
	      $output .= '<p>' . t('Datas for @username', array('@username' => $name)) . '</p>';
	    }

	    $build = $this->table_build_user_report($uid);
	    //$build['form'] = \Drupal::formBuilder()->getForm('\Drupal\track_da_files\Form\TrackDaFilesUserTableActionForm'/*, $extra*/);

	    return $build;
	  }



    public function table_build() {

    	global $base_url;

      // We check avalaible optional datas.
      $displays_datas = \Drupal::config('track_da_files.settings')->get('displays_datas');
      $files_datas = \Drupal::config('track_da_files.settings')->get('files_datas');

      // We prepare main report table header.
      $header = array($this->t('Filename'), t('Counter'));

     // We put displays datas in main report table header.
	  foreach ($displays_datas as $data => $value) {
	    if (!empty($value)) {
	      if ($value == 'total_ips') {
	        $header[] = array(
	          'data' => $this->t('Total ips'),
	          'field' => $value,
	          'sort' => 'DESC',
	        );
	      }
	      elseif ($value == 'average_by_ip') {
	        $header[] = array(
	          'data' => $this->t('Average by ip'),
	          'field' => $value,
	          'sort' => 'DESC',
	        );
	      }
	      elseif ($value == 'last_display') {
	        $header[] = array(
	          'data' => $this->t('Last display'),
	          'field' => $value,
	          'sort' => 'DESC',
	        );
	      }
	    }
	  }

    foreach ($files_datas as $data => $value) {
	    if (!empty($value)) {
	      if ($value == 'timestamp') {
	        $header[] = array(
	          'data' => $this->t('Created'),
	          'field' => $value,
	          'sort' => 'DESC',
	        );
	      }
	      elseif ($value == 'filesize') {
	        $header[] = array(
	          'data' => $this->t('File size'),
	          'field' => $value,
	          'sort' => 'DESC',
	        );
	      }
	      elseif ($value == 'filemime') {
	        $header[] = array(
	          'data' => $this->t('File mime'),
	          'field' => $value,
	          'sort' => 'DESC',
	        );
	      }
	    }
  	}


    // We prepare fields.
    $fields = array('pid'/*, 'recid'*/);
    foreach ($displays_datas as $key => $value) {

      if ($value == 'average_by_ip') {
        $average_by_ip = $value;
      }
      elseif ($value == 'total_ips') {
        $total_ips = $value;
      }
      elseif ($value == 'last_display') {
        $last_display = $value;
      }
      else {
        $fields[] = $value;
      }
    }

    $fields2 = array('filename');
    foreach ($files_datas as $key => $value) {
      if ($value) {
        $fields2[] = $value;
      }
    }

    $header[] = array('data' => t('File datas'));

    // We select the datas in database.
    $query = \Drupal::database()->select('track_da_files_paths', 'p');
    $query->addExpression('COUNT(recid)', 'counter');
    $query->addExpression('COUNT(DISTINCT(ip))', 'total_ips');
    $query->addExpression('MAX(time)', 'last_display');
    $query->addExpression('ROUND(COUNT(recid) / COUNT(DISTINCT(ip)))', 'average_by_ip');
    $query->join('track_da_files', 't', 't.pid = p.pid');
    $query->join('file_managed', 'f', 'p.fid = f.fid');
    $query->extend('\Drupal\Core\Database\Query\PagerSelectExtender');
    $query->extend('\Drupal\Core\Database\Query\TableSortExtender');
    $query->fields('f', $fields2);
    $query->fields('t', $fields);
    $query->fields('p', array('fid', 'path'));
    $query->groupBy('p.fid');
    $query->groupBy('t.pid');
    $query->groupBy('f.filename');
    $query->groupBy('p.path');
    $query->groupBy('f.created');
    $query->groupBy('f.filesize');
    $query->groupBy('f.filemime');
    $query->range(0,20);

    $results = $query->execute();

    $rows = array();

      // We loop into the results to build table rows for the main report table.
      foreach ($results as $key => $record) {

        foreach ($record as $key2 => $row) {

          if ($key2 == 'filename') {

            //$rows[$key][$key2] = l($row, file_create_url($record->path));
            $record_uri = file_create_url($record->path);
            $rows[$key][$key2] = \Drupal::l($row, Url::fromUri($record_uri));
          }
          elseif (($key2 == 'pid') || ($key2 == 'path')) {
            unset($rows[$key][$key2]);
          }
          elseif (($key2 == 'last_display' && !empty($last_display)) || ($key2 == 'timestamp')) {
            $rows[$key][$key2] = format_date($row, 'short');
          }
          elseif ($key2 == 'filesize') {
            $rows[$key][$key2] = format_size($row);
          }
          elseif ((($key2 == 'filemime') || ($key2 == 'counter')) || (!empty($total_ips) && $key2 == 'total_ips')) {
            $rows[$key][$key2] = $row;
          }
          elseif ($key2 == 'fid') {
            //$rows[$key][$key2] = l(t('View more'), 'admin/reports/track_da_files/file_report/' . $record->fid . '/' . $record->pid);
           // $file_uri = file_build_uri('admin/reports/track_da_files/file_report/' . $record->fid . '/' . $record->pid);
          	$file_url = $base_url . '/admin/reports/track_da_files/file_report/' . $record->fid . '/' . $record->pid;
            $rows[$key][$key2] = \Drupal::l(t('View more'), Url::fromUri($file_url));
          }
          elseif ((!empty($average_by_ip)) && ($key2 == 'average_by_ip')) {
            $rows[$key][$key2] = $record->average_by_ip;
          }
        }
      }

      // We reorder rows to match table headers order.
      $rows_indexes_ordered = array(
        'filename',
        'counter',
        'total_ips',
        'average_by_ip',
        'last_display',
        'timestamp',
        'filesize',
        'filemime',
        'fid',
      );
      $rows_ordered = array();

      foreach ($rows as $row => $value) {
        foreach ($value as $key => $value2) {
          $keys[] = $key;
        }
      }

      $rows_final = array();
      $variables = array();

      foreach ($rows as $row) {
        foreach ($rows_indexes_ordered as $index) {
          if (in_array($index, $keys)) {
            $rows_ordered[$index] = $row[$index];
          }
        }
        $rows_final[] = $rows_ordered;
      }

      $build['track_da_files_table'] = array(
      		'#type' => 'table',
      		'#header' => $header,
      		'#rows' => $rows_final,
      		'#attributes' => array('id' => 'admin-track-da-files', 'class' => array('admin-track-da-files')),
      		'#attached' => array(
            'library' =>  array(
              'track_da_files/track_da_files'
            ),
          ),
      );

      $build['track_da_files_pager'] = array('#type' => 'pager');
      return $build['track_da_files_table'];
    }

    /**
     * Build variables used to create file report.
     */
    public function table_build_file_report($fid, $pid) {

    	global $base_url;

      // We retrieve configuration datas.
      $single_file_datas = \Drupal::config('track_da_files.settings')->get('single_file_datas');
      $user_report_enabled = \Drupal::config('track_da_files.settings')->get('user_report_enabled');


      // We prepare table header for file specific report.
      $header =  array(t('Time'));

      // Header datas.
      foreach ($single_file_datas as $data => $value) {
        if (!empty($value)) {
          if ($value == 'uid') {
            $header[] = $this->t('Username');
          }
          elseif ($value == 'id') {
            $header[] = $this->t('Related content');
          }
          elseif ($value == 'referer') {
            $header[] = $this->t('Displayed from');
          }
          elseif ($value == 'browser') {
            $header[] = $this->t('Browser');
          }
          elseif ($value == 'browser_version') {
            $header[] = $this->t('Browser version');
          }
          elseif ($value == 'browser_platform') {
            $header[] = $this->t('Platform');
          }
          elseif ($value == 'ip') {
            $header[] = $this->t('Ip');
          }
        }
      }

      // We build an array with header values to make some verifications.
      foreach ($header as $key => $value) {
        $header_values[]  = $value;
      }


      if ($user_report_enabled) {
        $header[] = $this->t('User datas');
      }

      // We prepare fields.
      $fields = array('time');

      if (isset($single_file_datas['id']) && $single_file_datas['id'] == '0') {
        unset($single_file_datas['id']);
      }

      foreach ($single_file_datas as $key => $value) {
        if (!empty($value)) {
          if ($value == 'id') {
            $fields[] = 'id';
            $fields[] = 'type';
          }
          else {
            $fields[] = $value;
          }
        }
      }

      $query = \Drupal::database()->select('track_da_files', 't');
      $query->extend('\Drupal\Core\Database\Query\PagerSelectExtender');
      $query->extend('\Drupal\Core\Database\Query\TableSortExtender');
      $query->fields('t', $fields);
      $query->condition('pid', $pid);
      $query->range(0,20);
      $results = $query->execute();
      $rows = array();

      // We loop into the results to build table rows for file report table.
      foreach ($results as $key => $record) {
        foreach ($record as $key2 => $row) {
          if ($key2 == 'time') {
            $rows[$key][$key2] = format_date($row, 'short');
          }
          elseif ($key2 == 'id') {
            if ($record->type == 'node') {
              $node = Node::load($row);
					    $rows[$key][$key2] = \Drupal::l($node->getTitle(), Url::fromUri($base_url . '/' . $record->type . '/' . $row));
            }
            elseif ($record->type == 'comment') {
              $comment = Comment::load($row);
              $rows[$key][$key2] = \Drupal::l($comment->getsSubject(), Url::fromUri($base_url  . '/' . $record->type . '/' . $row), array('fragment' => 'comment-' . $row));
            }
            else {
              $rows[$key][$key2] = '&nbsp;';
            }
          }
          elseif ($key2 == 'type') {
            unset ($rows[$key][$key2]);
          }
          elseif ($key2 == 'uid') {

            $uid = $row;
            $account = \Drupal\user\Entity\User::load($uid);
						$name = $account->getUsername();

            if ($user_report_enabled) {
            	//dpm($header_values);
              if (in_array('Username', $header_values)) {
                if (!empty($name)) {
                  $rows[$key][$key2] = $name;
                }
                else {
                  $rows[$key][$key2] = t('Anonymous user');
                }
              }

              $rows[$key]['view_more'] = \Drupal::l(t('View more'), Url::fromUri($base_url . '/admin/reports/track_da_files/user_report/' . $uid));

            }
            else {
              $rows[$key][$key2] = $name;
            }
          }
          else {
            $rows[$key][$key2] = $row;
          }
        }
      }
        $build['track_da_files_table'] = array(
      		'#type' => 'table',
      		'#header' => $header,
      		'#rows' => $rows,
      		'#attributes' => array('id' => 'admin-track-da-files', 'class' => array('admin-track-da-files')),
      		'#attached' => array(
            'library' =>  array(
              'track_da_files/track_da_files'
            ),
          ),
      );

      $build['track_da_files_pager'] = array('#type' => 'pager');

      return $build['track_da_files_table'];


   }

  /**
   * Build variables used to create table which shows datas for a specific user.
   */
  public function table_build_user_report($uid) {

    $user_report_enabled = \Drupal::config('track_da_files.settings')->get('user_report_enabled');
    $single_user_datas = \Drupal::config('track_da_files.settings')->get('single_user_datas');

    if ($user_report_enabled) {

      $header = array(t('Filename'), t('time'));

      // Header datas.
      foreach ($single_user_datas as $data => $value) {
        if (!empty($value)) {
          if ($value == 'referer') {
            $header[] = t('Displayed from');
          }
          elseif ($value == 'browser') {
            $header[] = t('Browser');
          }
          elseif ($value == 'browser_version') {
            $header[] = t('Browser version');
          }
          elseif ($value == 'browser_platform') {
            $header[] = t('Platform');
          }
          elseif ($value == 'ip') {
            $header[] = t('Ip');
          }
        }
      }

      // We prepare fields.
      $fields = array('time');

      foreach ($single_user_datas as $key => $value) {
        if ($value) {
          $fields[] = $value;
        }
      }

      $query = \Drupal::database()->select('track_da_files', 't');
      $query->join('track_da_files_paths', 'p', 't.pid = p.pid');
      $query->extend('\Drupal\Core\Database\Query\PagerSelectExtender');
      $query->extend('\Drupal\Core\Database\Query\TableSortExtender');
      $query->fields('p', array('fid'));
      $query->fields('t', $fields);
      $query->condition('uid', $uid);
      $query->range(0,20);
      $results = $query->execute();
      $rows = array();

      // We loop into results to build rows for user report table.
      foreach ($results as $key => $record) {
        foreach ($record as $key2 => $row) {
          if ($key2 == 'time') {
            $rows[$key][$key2] = format_date($row, 'short');
          }
          elseif ($key2 == 'fid') {

            //$file = file_load($row);
            $file = File::load($row);

            if (isset($file->filename)) {
              $rows[$key][$key2] = $file->getFilename();
            }
            else {
              $rows[$key][$key2] = t('No name');
            }
          }
          else {
            $rows[$key][$key2] = $row;
          }
        }
      }

      $build['track_da_files_table'] = array(
      	'#type' => 'table',
      	'#header' => $header,
      	'#rows' => $rows,
      	'#attributes' => array('id' => 'admin-track-da-files', 'class' => array('admin-track-da-files')),
       	'#attached' => array(
           'library' =>  array(
             'track_da_files/track_da_files'
           ),
         ),
      );

      return $build['track_da_files_table'];
    }
    else {
      return FALSE;
    }

  }

  public function tracking($filedir, $filename) {

    $current_uri = Url::fromRoute('<current>');

    $file_uri = file_build_uri($current_uri->getInternalPath());

	  $roles = \Drupal::config('track_da_files.settings')->get('specific_roles');

	  $account = \Drupal::currentUser();
	  $track = track_da_files_roles($account);

	  if (!isset($_GET['file'])) {
	    // Our menu hook wasn't called, so we should ignore this.
	    return;
	  }

	  $scheme = file_uri_scheme($file_uri);
	  $file_uri = strtok($file_uri, '?');
	  $file_name = basename($file_uri);
	  $parts = parse_url($current_uri->getInternalPath());
    $file_relative_path = str_replace('system/tdf/', '', $parts['path']);
    $uri = $scheme . '://' . $file_relative_path;

	  // Retrieve entity id information in query parameters.
	  if (isset($_GET['id'])) {
	    $id = $_GET['id'];
	  }
	  if (isset($_GET['type'])) {
	    $type = $_GET['type'];
	  }
	  if (isset($_GET['force'])) {
	    $force = $_GET['force'];
	  }

	  $query = "SELECT f.fid, f.filename, f.filemime, f.filesize FROM {file_managed} f WHERE f.uri = :uri";
	  $result = db_query($query, array(':uri' => $uri))->fetch();
    $filemime = '';
    $filesize = '';
		if (!empty($result)) {
	    $filename = isset($result->filename) ? $result->filename : '';
	    $fid = isset($result->fid) ? $result->fid : '';
	    $filemime = isset($result->filemime) ? $result->filemime : '';
	    $filesize = isset($result->filesize) ? $result->filesize : '';
	  }
	  // If uri exists and valid uri scheme interaction with database begins.
	    if (!empty($fid) && $track) {
	      if (!empty($id) && !empty($type)) {
	        track_da_files_register_new_display($uri, $fid, $id, $type);
	      }
	      else {
	        track_da_files_register_new_display($uri, $fid);
	      }
	    }

	    $headers = array(
	      'Content-Type' => $filemime,
	      'Content-Length' => $filesize,
	    );

	    if(isset($force) && $force == 1) {
	      $headers['Content-Disposition'] = 'attachment; filename="' . $filename . '"';
	    }

	  	$response = new Response();
			return new BinaryFileResponse($uri , 200, $headers);
  }


  public function tracking_private($file_uri, $uritest) {

  	$uri = $full_uri = '';

  	$request = \Drupal::request();

  	$querystring = $request->getQueryString();
  	$pathinfo = $request->getPathinfo();
    $file_uri = $request->getRequestUri();
	  $roles = \Drupal::config('track_da_files.settings')->get('specific_roles');
	  $account = \Drupal::currentUser();
	  $track = track_da_files_roles($account);

	  if (!empty($file)) {
	    // Our menu hook wasn't called, so we should ignore this.
	    return;
	  }

	  $file_name = basename($file_uri);
	  $file_relative_path = str_replace('/system/files/', '', $pathinfo);
	  $scheme = 'private';
    $uri = $scheme . '://' . $file_relative_path;
    $file = $request->get('file');
  	$id = $request->get('id');
  	$type = $request->get('type');

	  $query = "SELECT f.fid, f.filename, f.filemime, f.filesize FROM {file_managed} f WHERE f.uri = :uri";
	  $result = db_query($query, array(':uri' => $uri))->fetch();

		if (!empty($result)) {
	    $filename = isset($result->filename) ? $result->filename : '';
	    $fid = isset($result->fid) ? $result->fid : '';
	    $filemime = isset($result->filemime) ? $result->filemime : '';
	    $filesize = isset($result->filesize) ? $result->filesize : '';
	  }
	  // If uri exists and valid uri scheme interaction with database begins.
	    if (!empty($fid) && $track) {
	      if (!empty($id) && !empty($type)) {
	        track_da_files_register_new_display($uri, $fid, $id, $type);
	      }
	      else {
	        track_da_files_register_new_display($uri, $fid);
	      }
	    }

	    $headers = array(
	      'Content-Type' => $filemime,
	      'Content-Length' => $filesize,
	    	'Content-Disposition' => 'attachment; filename="' . $filename . '"',
	    );

	  	$response = new Response();
			return new BinaryFileResponse($uri , 200, $headers);

	  }
}







