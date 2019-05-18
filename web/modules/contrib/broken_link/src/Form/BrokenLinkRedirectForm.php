<?php

namespace Drupal\broken_link\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Path\PathValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BrokenLinkRedirectForm.
 *
 * @package Drupal\broken_link\Form
 */
class BrokenLinkRedirectForm extends EntityForm {

  /**
   * Drupal\Core\Path\PathValidator definition.
   *
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;

  /**
   * Constructor.
   */
  public function __construct(PathValidator $pathValidator) {
    $this->pathValidator = $pathValidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $broken_link_redirect = $this->entity;

    $pattern = '';
    if ($broken_link_redirect->get('pattern')->get(0)) {
      $pattern = $broken_link_redirect->get('pattern')->get(0)->getValue()['value'];
    }

    $form['pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Broken link pattern'),
      '#maxlength' => 255,
      '#default_value' => $pattern,
      '#description' => $this->t("Regular expression pattern."),
      '#required' => TRUE,
    ];

    $redirect_path = '';
    if ($broken_link_redirect->get('redirect_path')->get(0)) {
      $redirect_path = $broken_link_redirect->get('redirect_path')->get(0)->getValue()['value'];
    }

    $form['redirect_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect path'),
      '#maxlength' => 2000,
      '#default_value' => $redirect_path,
      '#description' => $this->t("Redirect path for broken link."),
      '#required' => TRUE,
    ];

    $enabled = TRUE;
    if ($broken_link_redirect->get('enabled')->get(0)) {
      $enabled = $broken_link_redirect->get('enabled')->get(0)->getValue()['value'];
    }

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $enabled,
    ];

    $weight = '';
    if ($broken_link_redirect->get('weight')->get(0)) {
      $weight = $broken_link_redirect->get('weight')->get(0)->getValue()['value'];
    }

    $form['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => $weight,
      '#delta' => 10,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $redirect_path = $form_state->getValue('redirect_path');
    $is_valid = $this->pathValidator->isValid($redirect_path);
    if (!$is_valid) {
      $form_state->setError($form['redirect_path'], 'Please enter valid redirect path.');
    }
    elseif (UrlHelper::isExternal($redirect_path)) {
      $form_state->setError($form['redirect_path'], 'External redirect path is not allowed.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $broken_link_redirect = $this->entity;
    $status = $broken_link_redirect->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the new Broken link redirect entity.', []));
        break;

      default:
        drupal_set_message($this->t('Saved the Broken link redirect entity.', []));
    }
    $form_state->setRedirectUrl($broken_link_redirect->urlInfo('collection'));
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save');
    return $actions;
  }

}
