<?php

namespace Drupal\googlemap_block\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Returns responses for Google map Location module routes.
 */
class MapLocationDelete extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'googlemap_block.GoogleMapLocationDelete',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_map_location_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_url = Url::fromRoute('<current>');
    $current_path = $current_url->toString();
    $pathArr = explode('/', $current_path);
    $location_id = end($pathArr);
    $query = db_select('google_map_location_list', 'u');
    $query->fields('u');
    $query->condition('lid', $location_id);
    $results = $query->execute()->fetchAll();
    $location_name = $results[0]->location_name;
    $form['helptext'] = [
      '#type' => 'item',
      '#markup' => "Are you sure you want to delete $location_name location.",
    ];
    $form['delete_id'] = [
      '#type' => 'hidden',
      '#value' => $results[0]->lid,
    ];
    $form['delete_name'] = [
      '#type' => 'hidden',
      '#value' => $location_name,
    ];
    $form['delete'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete Location'),
      '#name' => 'locationDelete',
      '#attributes' => ['class' => ['button--primary']],
    ];
    $form['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#name' => 'locationCancel',
      '#attributes' => ['class' => ['button--primary']],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $triggering_element = $form_state->getTriggeringElement()['#name'];
    if ($triggering_element == 'locationDelete') {
      $delete_id = $values['delete_id'];
      $delete_name = $values['delete_name'];
      db_delete('google_map_location_list')
        ->condition('lid', $delete_id)
        ->execute();
      drupal_set_message($this->t('@location has been deleted', ['@location' => $delete_name]));
    }
    $form_state->setRedirectUrl(Url::fromRoute('googlemap_block.map_location'));
  }

}
