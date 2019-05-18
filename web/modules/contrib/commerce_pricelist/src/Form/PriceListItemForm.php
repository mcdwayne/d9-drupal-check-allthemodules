<?php

namespace Drupal\commerce_pricelist\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;

class PriceListItemForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    // The default form title is wrong because EntityController::doGetEntity()
    // takes the price list entity instead of the price list item entity.
    $form['#title'] = $this->t('Edit %label', ['%label' => $this->entity->label()]);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    if ($route_match->getRawParameter($entity_type_id) !== NULL) {
      $entity = $route_match->getParameter($entity_type_id);
    }
    else {
      // Price lists and price list items share the same bundle.
      $price_list = $route_match->getParameter('commerce_pricelist');
      $values = [
        'type' => $price_list->bundle(),
        'price_list_id' => $price_list->id(),
      ];
      $entity = $this->entityTypeManager->getStorage($entity_type_id)->create($values);
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $this->messenger()->addMessage($this->t('Saved the %label price.', ['%label' => $this->entity->label()]));
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

}
