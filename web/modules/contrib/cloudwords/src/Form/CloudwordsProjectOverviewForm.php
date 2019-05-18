<?php

namespace Drupal\cloudwords\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class CloudwordsProjectOverviewForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cloudwords_project_overview_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $projects = FALSE;
    try {
      $projects = cloudwords_get_api_client()->get_open_projects();
    }
    
      catch (CloudwordsApiException $e) {
    }

//    $existing_ids = db_query("SELECT pid FROM {cloudwords_project} WHERE status NOT IN (:canceled)", [
//      ':canceled' => cloudwords_project_closed_statuses()
//      ])->fetchCol();
    $rows = [];

    if ($projects && $existing_ids) {
      foreach ($projects as $project) {
        if (in_array($project->getId(), $existing_ids)) {
          $params = $project->getParams();

          // Gather target languages. Wrap every 4 languages.
          $langs = [];
          $key = 0;
          foreach ($params['targetLanguages'] as $delta => $lang) {
            if ($delta % 4 === 0) {
              $key++;
            }
            $langs[$key][] = $lang['display'];
          }
          foreach ($langs as $delta => $lang) {
            $langs[$delta] = implode(', ', $langs[$delta]);
          }
//          $target_language = implode('<br />', $langs);
          // @FIXME
          // l() expects a Url object, created from a route name or external URI.
          // $row = array(
          //           'name' => l($params['name'], 'admin/structure/cloudwords/project/' . $params['id']),
          //           'status' => $params['status']['display'],
          //           'source_language' => isset($params['sourceLanguage']) ? $params['sourceLanguage']['display'] : '',
          //           'target_language' => $target_language,
          //         );
          $rows[] = $row;
        }
      }
    }

    $header = [
      ['data' => $this->t('Name'), 'field' => 'name'],
      [
        'data' => $this->t('Status'),
        'field' => 'status',
      ],
      ['data' => $this->t('Source language'), 'field' => 'source_language'],
      [
        'data' => $this->t('Target languages'),
        'field' => 'target_language',
        'sort' => 'desc',
      ],
    ];


    $order = tablesort_get_order($header);
    $sort = tablesort_get_sort($header);

    if (isset($order['sql'])):
      $sql = $order['sql'];
      if ($sort == 'desc') {
        usort($rows, function($a, $b) use($sql) {
          return strip_tags($a[$sql]) > strip_tags($b[$sql]) ? -1 : 1;
        });
      }
      if ($sort == 'asc') {
        usort($rows, function($a, $b) use ($sql) {
          return strip_tags($a[$sql]) < strip_tags($b[$sql]) ? -1 : 1;
        });
      }
    endif;


    $form['table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => [
        'id' => 'cloudwords-project-table'
        ],
      '#empty' => $this->t('No projects available.'),
    ];

    return $form;
  }
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate submitted form data.
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handle submitted form data.
  }

}
