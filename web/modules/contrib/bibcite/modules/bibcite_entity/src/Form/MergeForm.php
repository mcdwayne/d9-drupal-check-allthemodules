<?php

namespace Drupal\bibcite_entity\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Merge bibliographic entities.
 */
class MergeForm extends FormBase {

  /**
   * The entity object to merge.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('current_route_match'));
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(RouteMatchInterface $route_match) {
    $parameter_name = $route_match->getRouteObject()->getOption('_bibcite_entity_type_id');
    $this->entity = $route_match->getParameter($parameter_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bibcite_entity_merge';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['target'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Select target'),
      '#description' => $this->t('@entity_type_label to be merged into.', [
        '@entity_type_label' => $this->entity->getEntityType()->getLabel(),
      ]),
      '#target_type' => $this->entity->getEntityTypeId(),
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Merge'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($form_state->getValue('target') == $this->entity->id()) {
      $form_state->setErrorByName('target', $this->t('@label cannot be merged into oneself', ['@label' => $this->entity->label()]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect("entity.{$this->entity->getEntityTypeId()}.bibcite_merge_form_confirm", [
      $this->entity->getEntityTypeId() => $this->entity->id(),
      "{$this->entity->getEntityTypeId()}_target" => $form_state->getValue('target'),
    ]);
  }

  /**
   * Title callback.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Page title.
   */
  public function getTitle() {
    return $this->t('Merge @label', ['@label' => $this->entity->label()]);
  }

}
