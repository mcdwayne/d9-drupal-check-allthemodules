<?php

namespace Drupal\seasonal_product_recommendations\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;

/**
 * Class DeleteForm.
 *
 * @package Drupal\seasonal_product_recommendations\Form
 */
class DeleteForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'delete_form';
  }

  /**
   * Confirmation for deletion.
   */
  public function getQuestion() {
    $route_match = \Drupal::service('current_route_match');
    $hid = $route_match->getParameter('hid');

    $query_season = \Drupal::database()->select('hemisphere_seasons', 'hs');
    $query_season->fields('hs', ['hemisphere', 'season']);
    $query_season->condition('hs.hid', $hid, '=');
    $result = $query_season->execute()->fetch();
    $hemisphere_name = $result->hemisphere;
    $tid = $result->season;
    $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($tid);
    $title = $term->name->value;

    return t('Do you want to delete the season entry %season for %hemisphere hemisphere?', ['%hemisphere' => $hemisphere_name, '%season' => $title]);
  }

  /**
   * Redirection after canceling the deletion.
   */
  public function getCancelUrl() {
    return new Url('seasonal_product_recommendations.DisplayHemisphere');
  }

  /**
   * Warning: Only do this if you are sure.
   */
  public function getDescription() {
    return t('Only do this if you are sure!');
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
   */
  public function buildForm(array $form, FormStateInterface $form_state, $hid = NULL) {
    $this->id = $hid;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = \Drupal::database();
    $query->delete('hemisphere_seasons')->condition('hid', $this->id)->execute();
    drupal_set_message(t("Successfully deleted"));
    $form_state->setRedirect('seasonal_product_recommendations.DisplayHemisphere');
  }

}
