<?php

namespace Drupal\global_gateway_ui\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\global_gateway\Mapper\MapperPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigEntityFormBase.
 *
 * Typically, we need to build the same form for both adding a new entity,
 * and editing an existing entity.
 */
abstract class MappingResetFormBase extends ConfirmFormBase {

  protected $languages;

  /**
   * {@inheritdoc}
   */
  public function __construct($mapper_plugin_id, MapperPluginManager $mapperManager) {
    $this->mapper = $mapperManager->createInstance($mapper_plugin_id);

    $region = \Drupal::routeMatch()->getParameter('region_code');
    $this->entity = $this->mapper
      ->setRegion($region)
      ->getEntity();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.global_gateway.mapper'));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Reset');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->getQuestion();

    $form['#attributes']['class'][] = 'confirmation';
    $form['description'] = ['#markup' => $this->t('This action cannot be undone.')];
    $form['description']['#markup'] .= '<br>';

    if (!isset($form['#theme'])) {
      $form['#theme'] = 'confirm_form';
    }

    $form += self::actions($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    return [
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->getConfirmText(),
        '#submit' => [
          [$this, 'submitForm'],
        ],
      ],
      'cancel' => ConfirmFormHelper::buildCancelLink($this, $this->getRequest()),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    $this->messenger()->addStatus($this->t('Settings %label was reset.', [
      '%label' => $this->entity->label(),
    ]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
