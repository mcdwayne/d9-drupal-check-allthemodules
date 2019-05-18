<?php

namespace Drupal\forena\Controller;

use Drupal\Core\Ajax\AfterCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\BeforeCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\forena\DocManager;
use Drupal\forena\File\ReportFileSystem;
use Drupal\forena\Frx;
use Drupal\forena\ReportManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ForenaController extends ControllerBase {

  /**
   * Render a single report.
   * 
   * @param $report
   * @return array | string
   */
  public function report($report) {
    $content = ReportManager::instance()->report($report, $_GET);
    $d = DocManager::instance();
    $doc_type = $d->getDocumentType();
    switch ($doc_type) {
      case 'drupal':
      case 'email':
        if ($content === FALSE) throw new AccessDeniedHttpException();
        if (!$content) throw new NotFoundHttpException();
        return $content;
        break;
      default:
        $response = new Response();
        $doc = $d->getDocument();
        if ($doc->headers) {
          foreach ($doc->headers as $k => $header) {
            $response->headers->set($k, $header);
          }
        }
        $response->setContent($content);
        return $response;
    }
  }

  /**
   * Ajax callback handler for ajax requests.
   *
   * @param String $report
   *   Name of Report.
   * @param String $js_mode
   *   Either nojs to imply disabled javascript or ajax to perform replacement.
   * @param string $command
   *   Type of replacement that is to be performed.
   * @param string $id
   *   ID to replace.
   * @return \Drupal\Core\Ajax\AjaxResponse|array
   */
  public function ajaxReport($report, $js_mode, $id='report', $command='html') {
    $report = $this->report($report);
    if ($js_mode == 'ajax') {
      $reponse = new AjaxResponse();
      $commands = Frx::instance()->getDocument()->getAjaxCommands();
      if (isset($commands['pre'])) foreach($commands['pre'] as $cmd) {
        $reponse->addCommand($cmd);
      }
      switch ($command) {
        case 'after':
          $reponse->addCommand(new AfterCommand("#$id", $report));
          break;
        case 'append':
          $reponse->addCommand(new AppendCommand("#$id", $report));
          break;
        case 'modal':
          $reponse->addCommand(new OpenModalDialogCommand($report['title'], $report));
          break;
        case 'before':
          $reponse->addCommand(new BeforeCommand("#$id", $report));
          break;
        case 'html':
          $reponse->addCommand(new HtmlCommand("#$id", $report));
          break;
        case 'replace':
          $reponse->addCommand(new ReplaceCommand("#$id", $report));
          break;
      }
      if (isset($commands['post'])) foreach($commands['post'] as $cmd) {
        $reponse->addCommand($cmd);
      }
      return $reponse;

    }
    else {
      return $report;
    }
  }

  /**
   * Generate the list of user reports.
   * 
   * @return string
   */
  public function listUserReports() {
    $content=[];
    $reports = ReportFileSystem::instance()->reportsByCategory();

    if (!$reports) {
      $content = ['#type' => 'html_tag', '#value' => 'No Reports Found', '#tag' => 'p'];
    }
    else {

      $links = [];
      $output = '';
      foreach ($reports as $category => $cat_reports) {
        $links[] =  '<li><a href="#' . urlencode($category) . '">' . $category . '</a></li> ';
        $output .= '<h3 id="' . urlencode($category) . '">' . $category . '</h3>';
        $output .= '<ul>';
        foreach ($cat_reports as $r) {
          $report = str_replace('/', '.', $r['report_name']);
          $parms = ['report' => $report];
          $output .= '<li>' . $this->l($r['title'], Url::fromRoute('forena.report', $parms)) . '</li>';
        }
        $output .= '</ul>';
      }
      $content['#markup'] = $output;
    }
     return $content;
  }
}
