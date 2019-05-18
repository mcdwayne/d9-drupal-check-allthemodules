<?php

namespace Drupal\dblog_quick_filter\Controller;


use Drupal;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Zend\Diactoros\Response\JsonResponse;

/**
 * Class DefaultController.
 *
 * @package Drupal\dblog_quick_filter\Controller
 */
class DefaultController extends ControllerBase {

    protected $database;
    
    protected $dateFormatter;
    
    protected $userStorage;
    
  /**
   * {@inheritdoc}
   */
    public static function create(ContainerInterface $container) {
      return new static(
        $container->get('database'),
//        $container->get('module_handler'),
        $container->get('date.formatter')
//        $container->get('form_builder')
      );
    }
    
    public function __construct(Connection $database, DateFormatterInterface $date_formatter) {
        $this->database = $database;
        $this->dateFormatter = $date_formatter;
        $this->userStorage = $this->entityManager()->getStorage('user');
    }
  /**
   * Report Admin
   * 
   * @return [type] [description]
   */
  public function report() {

        $form = Drupal::formBuilder()->getForm('Drupal\dblog_quick_filter\Form\FilterForm');

        $page = array(
            '#type' => 'markup',
            '#markup' => render($form),
            '#prefix' => '<div ng-controller="myContrBasic">'
        );
        
        $page['table'] = array(
            '#theme' => 'dblog_filter_table',
            '#suffix' => '</div>'
        );
        

        $page['#attached']['library'][]  =  'dblog_quick_filter/dblog_quick_filter';
        $page['#attached']['library'][]  =  'dblog/drupal.dblog';
        

        return $page;
    }

  /**
   * get_dblog
   * 
   * @return [type] [description]
   */
  public function get_dblog(Request $request) {
      
        $rows = array();

        $classes = static::getLogLevelClassMap();
        $page = $request->get('page');
        $pager = isset($page) && trim($page) != "" ? (int) Xss::filter($page) : 1000;

        $header = array(
            array('data' => ''),
            array('data' => t('Type')),
            array('data' => t('Date'), 'field' => 'timestamp', 'sort' => 'DESC'),
            array('data' => t('Message')),
            array('data' => t('User')),
            array('data' => t('Operations')),
        );

        $result = static::dblog_quick_query($pager);


        foreach ($result as $dblog) {
            $message = $this->formatMessage($dblog);
            if ($message && isset($dblog->wid)) {
                $title = Unicode::truncate(Html::decodeEntities(strip_tags($message)), 256, TRUE, TRUE);
                $log_text = Unicode::truncate($title, 56, TRUE, TRUE);
                // The link generator will escape any unsafe HTML entities in the final
                // text.
                $message = $this->l($log_text, new Url('dblog.event', array('event_id' => $dblog->wid), array(
                    'attributes' => array(
                        // Provide a title for the link for useful hover hints. The
                        // Attribute object will escape any unsafe HTML entities in the
                        // final text.
                        'title' => $title,
                    ),
                )));
            }

            $rows[] = array(
                'data' => array(
                        
                        array('class' => 'icon'),
                        $dblog->type,
                        $this->dateFormatter->format($dblog->timestamp, 'short'),
                        $message,
                        $dblog->name,
                        $dblog->link,
                        $dblog->severity,
                        $dblog->wid
                    
                ),
                // Attributes for table row.
                'class' => array(
                    Html::getClass('dblog-' . $dblog->type), 
                    $classes[$dblog->severity]
                    ),
            );
            
        }
        $rows = array_reverse($rows);
        
        $build = array(
            'header' => $header,
            'rows' => $rows,
            'get' => $pager,
        );

        return new JsonResponse($build);
    }

  /**
   * get_event.
   *
   * @return string
   *   Return get_event string.
   */
  public function get_event($event_id) {
    
    $build = array();
    if ($dblog = $this->database->query('SELECT w.*, u.uid FROM {watchdog} w LEFT JOIN {users} u ON u.uid = w.uid WHERE w.wid = :id', array(':id' => $event_id))->fetchObject()) {
      $severity = RfcLogLevel::getLevels();
      $message = $this->formatMessage($dblog);
      $username = array(
        '#theme' => 'username',
        '#account' => $dblog->uid ? $this->userStorage->load($dblog->uid) : User::getAnonymousUser(),
      );
      $rows = array(
        array(
          array('data' => $this->t('Type'), 'header' => TRUE),
          $this->t($dblog->type),
        ),
        array(
          array('data' => $this->t('Date'), 'header' => TRUE),
          $this->dateFormatter->format($dblog->timestamp, 'long'),
        ),
        array(
          array('data' => $this->t('User'), 'header' => TRUE),
          array('data' => $username),
        ),
        array(
          array('data' => $this->t('Location'), 'header' => TRUE),
          $this->l($dblog->location, $dblog->location ? Url::fromUri($dblog->location) : Url::fromRoute('<none>')),
        ),
        array(
          array('data' => $this->t('Referrer'), 'header' => TRUE),
          $this->l($dblog->referer, $dblog->referer ? Url::fromUri($dblog->referer) : Url::fromRoute('<none>')),
        ),
        array(
          array('data' => $this->t('Message'), 'header' => TRUE),
          $message,
        ),
        array(
          array('data' => $this->t('Severity'), 'header' => TRUE),
          $severity[$dblog->severity],
        ),
        array(
          array('data' => $this->t('Hostname'), 'header' => TRUE),
          $dblog->hostname,
        ),
        array(
          array('data' => $this->t('Operations'), 'header' => TRUE),
          array('data' => array('#markup' => $dblog->link)),
        ),
      );
      $build['dblog_table'] = array(
        '#type' => 'table',
        '#rows' => $rows,
        '#attributes' => array('class' => array('dblog-event')),
        '#attached' => array(
          'library' => array('dblog/drupal.dblog'),
        ),
      );
    }

    return new JsonResponse(
            array(
                'data' => render($build), 
                'wid' => $event_id
            )
         );

  }
  
  public function tail_dblog($tail) {
    $classes = static::getLogLevelClassMap();
    $result = static::dblog_quick_query(100,  (int)$tail);
    $rows = array();
    
    if(count($result) > 0){
        foreach ($result as $dblog) {
            $message = $this->formatMessage($dblog);
            if ($message && isset($dblog->wid)) {
                $title = Unicode::truncate(Html::decodeEntities(strip_tags($message)), 256, TRUE, TRUE);
                $log_text = Unicode::truncate($title, 56, TRUE, TRUE);
                // The link generator will escape any unsafe HTML entities in the final
                // text.
                $message = $this->l($log_text, new Url('dblog.event', array('event_id' => $dblog->wid), array(
                    'attributes' => array(
                        // Provide a title for the link for useful hover hints. The
                        // Attribute object will escape any unsafe HTML entities in the
                        // final text.
                        'title' => $title,
                    ),
                )));
            }
            $rows[] = array(
              'data' =>
                array(

                  array('class' => 'icon'),
                  $dblog->type,
                  $this->dateFormatter->format($dblog->timestamp, 'short'),
                  $message,
                  $dblog->name,
                  $dblog->link,
                  $dblog->severity,
                  $dblog->wid
                ),

              'class' => array(
                Html::getClass('dblog-' . $dblog->type), 
                $classes[$dblog->severity],
              ),
            );
        }
    }
    
    $rows = array_reverse($rows);
    $build = array(
            'header' => array(),
            'rows' => $rows,
            'get' => 100,
        );

    return new JsonResponse($build);
  }

  public function dblog_quick_query($pager,$lastid=0) {
      $query = $this->database->select('watchdog', 'w')
                ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
                ->extend('\Drupal\Core\Database\Query\TableSortExtender');
       if($lastid > 0){
            $query->condition('wid', $lastid,'>');
       }
        $query->fields('w', array(
            'wid',
            'uid',
            'severity',
            'type',
            'timestamp',
            'message',
            'variables',
            'link',
        ))->addField('ufd', 'name');
        $query->leftJoin('users_field_data', 'ufd', 'w.uid = ufd.uid');


        $result = $query
                ->limit($pager)
                ->range(0)
                ->execute();
        return $result;
  }
  
  public function getLogLevelClassMap() {
        return array(
          RfcLogLevel::DEBUG => 'dblog-debug',
          RfcLogLevel::INFO => 'dblog-info',
          RfcLogLevel::NOTICE => 'dblog-notice',
          RfcLogLevel::WARNING => 'dblog-warning',
          RfcLogLevel::ERROR => 'dblog-error',
          RfcLogLevel::CRITICAL => 'dblog-critical',
          RfcLogLevel::ALERT => 'dblog-alert',
          RfcLogLevel::EMERGENCY => 'dblog-emergency',
        );
    }
      
  public function formatMessage($row) {
        // Check for required properties.
        if (isset($row->message, $row->variables)) {
          $variables = @unserialize($row->variables);
          // Messages without variables or user specified text.
          if ($variables === NULL) {
            $message = Xss::filterAdmin($row->message);
          }
          elseif (!is_array($variables)) {
            $message = $this->t('Log data is corrupted and cannot be unserialized: @message', ['@message' => Xss::filterAdmin($row->message)]);
          }
          // Message to translate with injected variables.
          else {
            $message = $this->t(Xss::filterAdmin($row->message), $variables);
          }
        }
        else {
          $message = FALSE;
        }
        return $message;
      }

}
