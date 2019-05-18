<?php

namespace Drupal\elastic_search\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FieldableEntityMapFormCallbacks.
 *
 * This class contains all the static callbacks for the FieldableEntityMapForm.
 * This marginally reduces the size of the form, and as there are a lot of
 * callbacks and ajax in this form it seems wise to clearly handle all of these
 * in one place
 *
 * @package Drupal\elastic_search\Form
 */
class FieldableEntityMapFormCallbacks extends EntityForm {

  /**
   * Make it impossible to instantiate this class
   */
  private function __construct() {
  }

  /**
   * @param array                                $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public static function viewMappingCallback(array &$form,
                                             FormStateInterface $form_state) {

    $route_match = \Drupal::routeMatch();//Has to be taken like this as its a static call

    $bundleData = $route_match->getParameter('fieldable_entity_map');

    $form_state->setRedirect('elastic_search.controller.fem.view',
                             ['mapping' => $bundleData->getId()]);
  }

  /**
   * @param array                                $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public static function incrementMappingCountCallback(array &$form,
                                                       FormStateInterface $form_state) {
    $count = &$form_state->get(self::getAddButtonId($form_state) .
                               FieldableEntityMapForm::FORM_COUNTER_SUFFIX);
    $count++;
    $form_state->setRebuild();
  }

  /**
   * @param array                                $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public static function decrementMappingCountCallback(array &$form,
                                                       FormStateInterface $form_state) {

    $count = &$form_state->get(self::getRemoveButtonId($form_state) .
                               FieldableEntityMapForm::FORM_COUNTER_SUFFIX);
    if ($count > 1) {
      $count--;
    }
    $form_state->setRebuild();
  }

  /**
   * @param array                                $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public static function addMoreCallback(array &$form,
                                         FormStateInterface $form_state) {
    $buttonId = self::getAddButtonId($form_state);
    $buttonArrayId = explode('__', $buttonId);
    return NestedArray::getValue($form, $buttonArrayId);
  }

  /**
   * @param array                                $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public static function removeCallback(array &$form,
                                        FormStateInterface $form_state) {
    $buttonId = self::getRemoveButtonId($form_state);
    $buttonArrayId = explode('__', $buttonId);
    return NestedArray::getValue($form, $buttonArrayId);
  }

  /**
   * @param array                                $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public static function addObjectMappingCallback(array &$form,
                                                  FormStateInterface $form_state) {
    $trig = $form_state->getTriggeringElement();
    $parents = $trig['#parents'];
    $key = array_search('map', array_reverse($parents, TRUE), TRUE);
    $actual = array_slice($parents, 0, $key);
    return NestedArray::getValue($form, $actual);
  }

  /**
   * @param array                                $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public static function advancedDynamicMappingCallback(array &$form,
                                                        FormStateInterface $form_state) {
    return $form['fields'];
  }

  /**
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return string
   */
  protected static function getAddButtonId(FormStateInterface $form_state) {
    $trig = $form_state->getTriggeringElement();
    return substr($trig['#name'], 0, -7);//chop off _button
  }

  /**
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return string
   */
  protected static function getRemoveButtonId(FormStateInterface $form_state) {
    $trig = $form_state->getTriggeringElement();
    return substr($trig['#name'], 0, -14);//chop off _remove_button
  }

}
