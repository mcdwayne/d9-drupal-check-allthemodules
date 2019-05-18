<?php

namespace Drupal\activity\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * List activities form.
 */
class ActivityListForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'list_activities_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $results = [];
    $query = \Drupal::service('database')->select('activity_events', 'act');
    $query->fields('act', ['hook', 'label', 'event_id']);
    $results = $query->execute();
    $countResults = $query->countQuery()->execute()->fetchField();
    if ($countResults > 0) {
      $rows = [];
      // Create pagination.
      // Element per page.
      $limit = 15;
      // Current page.
      if (!empty($_REQUEST['page'])) {
        $page = $_REQUEST['page'];
        $start = $page * $limit;
        $end = ($page + 1) * $limit;
      }
      else {
        $start = 0;
        $end = $limit;
      }
      foreach ($results as $key => $value) {
        if (($key >= $start) && ($key < $end)) {
          $row['label'] = $value->label;
          $row['hook'] = $value->hook;
          $configure_link = Link::fromTextAndUrl(t('configure'), URL::fromUri('internal:/admin/activity/configure/' . $value->event_id))
            ->toString();
          $delete_link = Link::fromTextAndUrl(t('delete'), URL::fromUri('internal:/admin/activity/delete/' . $value->event_id))
            ->toString();
          $mainLink = t('@configureLink | @deleteLink', [
            '@configureLink' => $configure_link,
            '@deleteLink' => $delete_link,
          ]);
          $row['operations'] = $mainLink;
          $rows[] = $row;
        }
      }
      // Initialize pager.
      pager_default_initialize($countResults, $limit);
      $form['activity_table'] = [
        '#type' => 'table',
        '#header' => [
          t('LABEL'),
          t('HOOK'),
          t('Operations'),
        ],
        '#attributes' => [
          'id' => 'activity_table',
          'class' => ['activity_table'],
        ],
        '#rows' => $rows,
      ];
      $form['pager'] = ['#type' => 'pager'];
    }
    else {
      $form['no_activities'] = [
        '#type' => 'markup',
        '#markup' => 'There are no Activity Templates created yet. ' . Link::fromTextAndUrl(t('Create one now.'), URL::fromUri('internal:/admin/activity/create'))->toString(),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    unset($form['table']['#rows']);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

}
