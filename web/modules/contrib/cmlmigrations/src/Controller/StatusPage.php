<?php

namespace Drupal\cmlmigrations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Controller routines for status page.
 */
class StatusPage extends ControllerBase {

  /**
   * Page.
   */
  public function page() {
    $migrations = \Drupal::service('cmlmigrations.migrate')->getCmlGroup();
    $output = "<h3>Актуальный обмен</h3>";
    $now = DrupalDateTime::createFromTimestamp(REQUEST_TIME);
    $cml_service = \Drupal::service('cmlapi.cml');
    if ($cml = $cml_service->actual()) {
      $created = DrupalDateTime::createFromTimestamp($cml->created->value);
      $changed = DrupalDateTime::createFromTimestamp($cml->changed->value);
      $id = $cml->id();
      $output .= "&mdash; [{$id}] - ";
      $output .= $cml->getState();
      $output .= " / Ago: ";
      $output .= $changed->diff($now)->format('%H:%i:%s');
      $output .= " / Time: ";
      $output .= $changed->diff($created)->format('%H:%i:%s');
      $output .= "<br>";
    }
    else {
      $output .= "&mdash; <br>";
    }
    $output .= "<h3>Текущий обмен</h3>";
    if ($cml = $cml_service->current()) {
      $created = DrupalDateTime::createFromTimestamp($cml->created->value);
      $changed = DrupalDateTime::createFromTimestamp($cml->changed->value);
      $id = $cml->id();
      $output .= "&mdash; [{$id}] - ";
      $output .= $cml->getState();
      $output .= " / Ago: ";
      $output .= $changed->diff($now)->format('%H:%i:%s');
      $output .= " / Time: ";
      $output .= $changed->diff($created)->format('%H:%i:%s');
      $output .= "<br>";
    }
    else {
      $output .= "&mdash; <br>";
    }
    $output .= "<h3>Следующий обмен</h3>";
    if ($cml = $cml_service->next()) {
      $created = DrupalDateTime::createFromTimestamp($cml->created->value);
      $changed = DrupalDateTime::createFromTimestamp($cml->changed->value);
      $id = $cml->id();
      $output .= "&mdash; [{$id}] - ";
      $output .= $cml->getState();
      $output .= "<br>";
    }
    else {
      $output .= "&mdash; <br>";
    }
    $output .= "<h3>Очередь обменов</h3>";
    if (!empty($cmllist = $cml_service->all())) {
      foreach ($cmllist as $id => $cml) {
        $output .= "&mdash; [{$id}] - ";
        $output .= $cml->getState();
        $output .= " / Date: ";
        $output .= format_date(REQUEST_TIME - $cml->created->value, 'custom', 'H:i:s');
        $output .= "<br>";
      }
    }
    else {
      $output .= "&mdash; <br>";
    }
    $rows = [];
    if ($migrations) {
      $output .= "<h3>Статус</h3>";
      if ($migrations['list']) {
        $output .= "Готов<br>";
      }
      else {
        $output .= "Занят<br>";
      }
      foreach ($migrations['list'] as $id => $migration) {
        $date_formatter = \Drupal::service('date.formatter');
        $last = 'N/A';
        if (is_numeric($migration['last'])) {
          $last = $date_formatter->format($migration['last'] / 1000, 'custom', 'dM H:i:s');
        }
        $rows[] = [
          'label' => $migration['label'],
          'status' => $migration['status'],
          'total' => $migration['total'],
          'imported' => $migration['imported'],
          'unprocessed' => $migration['unprocessed'],
          'messages' => $migration['messages'],
          'last' => $last,
        ];
      }
      $exec = 'Drupal\cmlmigrations\Form\ExecMigrations';
      $form = \Drupal::formBuilder()->getForm($exec, $migrations['list']);
    }

    return [
      'output' => ['#markup' => $output],
      'form' => $form,
      'migr-table' => [
        '#type' => 'table',
        '#header' => $this->buildHeader(),
        '#rows' => $rows,
      ],
    ];
  }

  /**
   * Header.
   */
  public function buildHeader() {
    $header = [
      'label' => $this->t('Migration'),
      'status' => $this->t('Status'),
      'total' => $this->t('Total'),
      'imported' => $this->t('Imported'),
      'unprocessed' => $this->t('Unprocessed'),
      'messages' => $this->t('Messages'),
      'last' => $this->t('Last'),
    ];
    return $header;
  }

}
