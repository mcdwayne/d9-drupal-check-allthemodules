<?php

namespace Drupal\content_type_dependency\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Default controller for the content_type_dependency module.
 */
class ContentTypeDependencyController extends ControllerBase {

  public function content_type_dependency_list($content = NULL) {
    // Table header for enabled rules.

    $header_table = array(
      'to_create' => $this->t('To Create'),
      'must_have' => $this->t('Must Have'),
      'no_of' => $this->t('No. of'),
      'message' => $this->t('Message'),
      'enable/disable' => $this->t('Enable/Disable'),
      'Edit' => $this->t('Edit'),
      'Delete' => $this->t('Delete'),
    );

    $query = \Drupal::database()->select('content_type_dependency', 'd')
      ->fields('d', ['to_create', 'must_have', 'no_of', 'message', 'cd_id'])
      ->condition('d.status', 1)->execute()->fetchAll();
    $row = [];
    foreach ($query as $rows) {
      $token_generator = \Drupal::csrfToken();
      $token = $token_generator->get();
      $relative_url = Url::fromUserInput('/admin/config/content/content_type_dependency/' . $rows->cd_id . '/disable?token=' . $token);
      $delete = Url::fromUserInput('/admin/config/content/content_type_dependency/' . $rows->cd_id . '/delete');
      $edit = Url::fromUserInput('/admin/config/content/content_type_dependency/list/modify?cd_id=' . $rows->cd_id);
      $row[] = array(
        $rows->to_create,
        $rows->must_have,
        $rows->no_of,
        $rows->message,
        Link::fromTextAndUrl('Disable', $relative_url),
        Link::fromTextAndUrl('Edit', $edit),
        Link::fromTextAndUrl('Delete', $delete),
      );
    }

    // Table header for disabled rules.
    $query1 = \Drupal::database()->select('content_type_dependency', 'd')
      ->fields('d', [
        'to_create',
        'must_have',
        'no_of',
        'message',
        'cd_id',
      ])
      ->condition('d.status', 0)->execute()->fetchAll();
    $row1 = [];
    foreach ($query1 as $rows1) {
      $token_generator1 = \Drupal::csrfToken();
      $token1 = $token_generator1->get();
      $relative_url = Url::fromUserInput('/admin/config/content/content_type_dependency/' . $rows1->cd_id . '/enable?token=' . $token1);
      $delete = Url::fromUserInput('/admin/config/content/content_type_dependency/' . $rows1->cd_id . '/delete');
      $edit = Url::fromUserInput('/admin/config/content/content_type_dependency/list/modify?cd_id=' . $rows1->cd_id);
      $row1[] = array(
        $rows1->to_create,
        $rows1->must_have,
        $rows1->no_of,
        $rows1->message,
        Link::fromTextAndUrl('Enable', $relative_url),
        Link::fromTextAndUrl('Edit', $edit),
        Link::fromTextAndUrl('Delete', $delete),
      );
    }

    // Table list for enabled rules.
    $table_enable = [
      '#theme' => 'table',
      '#header' => $header_table,
      '#rows' => $row,
      '#sticky' => TRUE,
      '#empty' => $this->t('No content type dependency is enabled.'),
      '#caption' => $this->t('Enabled'),
    ];
    // Table list for Disabled rules.
    $table_disable = [
      '#theme' => 'table',
      '#header' => $header_table,
      '#rows' => $row1,
      '#sticky' => TRUE,
      '#empty' => $this->t('No content type dependency is disabled.'),
      '#caption' => $this->t('Disabled'),
    ];
    return [$table_enable, $table_disable];
  }

  public function content_type_dependency_enable($cd) {
    $token_generator = \Drupal::csrfToken();
    if ($token_generator->validate($_GET['token'])) {
      drupal_set_message($this->t('Enabled!'));
      \Drupal::database()->update('content_type_dependency')
        ->fields(['status' => 1])
        ->condition('cd_id', $cd)
        ->execute();
      return $this->redirect('content_type_dependency.list');
    } else {
      throw new AccessDeniedHttpException();
    }
  }

  public function content_type_dependency_disable($cd) {
    $token_generator = \Drupal::csrfToken();
    if ($token_generator->validate($_GET['token'])) {
      drupal_set_message($this->t('Disabled!'));
      \Drupal::database()->update('content_type_dependency')
        ->fields(['status' => 0])
        ->condition('cd_id', $cd)
        ->execute();
      return $this->redirect('content_type_dependency.list');
    } else {
      throw new AccessDeniedHttpException();
    }
  }

}
