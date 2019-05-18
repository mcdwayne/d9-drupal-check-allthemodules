<?php

namespace Drupal\carto_sync\Form;

use Drupal\carto_sync\CartoSyncApiInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\ViewEntityInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class CartoSyncConfirmFormBase extends ConfirmFormBase {

  /**
   * @var \Drupal\views\ViewEntityInterface
   */
  protected $view;

  /**
   * @var string
   */
  protected $displayId;

  /**
   * @var string
   */
  protected $dataset;

  /**
   * @var \Drupal\carto_sync\CartoSyncApiInterface
   */
  protected $cartoSyncApi;

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('carto_sync.carto_sync_dashboard');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ViewEntityInterface $view = NULL, $display_id = NULL) {
    if (!$view->getDisplay($display_id)) {
      throw new NotFoundHttpException();
    }
    $this->view = $view;
    $this->displayId = $display_id;
    $this->dataset = $this->view->getDisplay($this->displayId)['display_options']['dataset_name'];
    $this->cartoSyncApi = \Drupal::service('carto_sync.api');
    return parent::buildForm($form, $form_state);
  }

}