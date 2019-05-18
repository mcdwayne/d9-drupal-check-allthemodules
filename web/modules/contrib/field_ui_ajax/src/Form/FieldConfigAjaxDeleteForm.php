<?php

/**
 * @file
 * Contains \Drupal\field_ui_ajax\Form\FieldConfigAjaxDeleteForm.
 */

namespace Drupal\field_ui_ajax\Form;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\field_ui\Form\FieldConfigDeleteForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\RestripeCommand;
use Drupal\field_ui_ajax\Component\Utility\HtmlExtra;

/**
 * Provides a form for removing a field from a bundle.
 */
class FieldConfigAjaxDeleteForm extends FieldConfigDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    if (HtmlExtra::getIsAjax()) {
      $form['intro'] = [
        '#weight' => -1000,
        '#markup' => '<h2>' . $this->getQuestion() . '</h2>',
      ];
    }
    $form['#field_ui_selector'] = 'js-' . str_replace(['.', '_'], '-', $this->entity->id());

    return $form;
  }

  /**
   * {@inheritdoc}
   * Add the cancel action and the AJAX submit handler.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    if (HtmlExtra::getIsAjax()) {
      $selector = $form['#field_ui_selector'];
      $actions['cancel']['#attributes']['class'][] = 'js-field-ui-toggle';
      $actions['cancel']['#attributes']['data-field-ui-show'] = '.' . $selector;
      $actions['cancel']['#attributes']['data-field-ui-hide'] = '.' . $selector . '-delete-form';
      $actions['submit']['#ajax'] = [
        'callback' => 'Drupal\field_ui_ajax\Controller\FieldUiHtmlEntityFormController::configAjaxDeleteFormSubmit',
      ];
    }

    return $actions;
  }

}
