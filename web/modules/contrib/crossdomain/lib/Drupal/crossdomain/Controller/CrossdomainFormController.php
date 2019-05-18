<?php

namespace Drupal\crossdomain\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFormController;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CrossdomainFormController extends EntityFormController {

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  public function __construct(QueryFactory $query_factory) {

    $this->queryFactory = $query_factory;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * Builds the form.
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);

    $domain = $this->entity;

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Domain'),
      '#maxlength' => 255,
      '#default_value' => $domain->label(),
      '#description' => $this->t('Domain to add to the xml file.'),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $domain->id(),
      '#machine_name' => array(
        'exists' => array($this, 'exists'),
      ),
      '#disabled' => !$domain->isNew(),
    );

    // You will need additional form elements for your custom properties.

    return $form;
  }

  /**
   * Save the domain.
   */
  public function save(array $form, array &$form_state) {
    $domain = $this->entity;
    $status = $domain->save();

    if ($status) {
      drupal_set_message($this->t('Saved %domain.', array(
        '%domain' => $domain->label(),
      )));
    }
    else {
      drupal_set_message($this->t('The %domain was not saved.', array(
        '%domain' => $domain->label(),
      )));
    }

    $form_state['redirect'] = 'admin/config/media/crossdomain';
  }

  /**
   * Delete the domain.
   */
  public function delete(array $form, array &$form_state) {
    $destination = array();
    $request = $this->getRequest();
    if ($request->query->has('destination')) {
      $destination = drupal_get_destination();
      $request->query->remove('destination');
    }

    $entity = $this->getEntity($form_state);
    $form_state['redirect'] = array('admin/config/media/crossdomain/' . $entity->id() . '/delete', array('query' => $destination));
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
    return $this;
  }

  /**
   * Determines if the domain already exists.
   *
   * @param string $entity_id
   *   The entity ID
   *
   * @return bool
   *   TRUE if the entity exists, FALSE otherwise.
   */
  public function exists($entity_id) {
    return (bool) $this->queryFactory
      ->get('crossdomain')
      ->condition('id', $entity_id)
      ->execute();
  }

}
