<?php

namespace Drupal\route_specific_breadcrumb\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\route_specific_breadcrumb\Controller\ListRecordsController;

/**
 * Class RouteSpecificBreadcrumbDeleteForm.
 *
 * @package Drupal\route_specific_breadcrumb\Form
 */
class RouteSpecificBreadcrumbDeleteForm extends ConfirmFormBase {

  protected $database;

  /**
   * {@inheritdoc}
   *
   * @param Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'route_specific_breadcrumb_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to delete %id?', array('%id' => $this->id));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('route_specific_breadcrumb.list_records_controller_update');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('This action can not be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete it!');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return t('Cancel');
  }

  /**
   * {@inheritdoc}
   *
   * @param int $rid
   *   (optional) The ID of the item to be deleted.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $rid = NULL) {
    $this->id = $rid;
    $result = ListRecordsController::routeData($this->database, $rid);
    if ($result === FALSE) {
      return $this->redirect('route_specific_breadcrumb.list_records_controller_update');
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $delete = ListRecordsController::routeDelete($this->database, $this->id);
    if ($delete === TRUE) {
      drupal_set_message(t('Data Deleted Successfully'));
      $url = Url::fromRoute('route_specific_breadcrumb.list_records_controller_update');
      $form_state->setRedirectUrl($url);
    }
  }

}
