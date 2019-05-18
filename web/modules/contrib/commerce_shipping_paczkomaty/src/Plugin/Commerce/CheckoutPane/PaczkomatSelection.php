<?php

namespace Drupal\commerce_shipping_paczkomaty\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormStateInterface;

/**
 * Exposes the paczkomaty selection map during checkout.
 *
 * @CommerceCheckoutPane(
 *   id = "commerce_shipping_paczkomaty_selection",
 *   label = @Translation("Wybierz Paczkomat"),
 *   wrapper_element = "container",
 *   default_step = "order_information"
 * )
 */
class PaczkomatSelection extends CheckoutPaneBase {

  public function isVisible() {
    $query = \Drupal::entityQuery('commerce_shipment_type');
    $nids = $query->execute();
    $entities = \Drupal::entityTypeManager()->getStorage('commerce_shipment_type')->loadMultiple($nids);
    foreach($entities as $entity) {
      $paczkomat = $entity->getThirdPartySetting('commerce_shipping_paczkomaty', 'paczkomat');
      if($paczkomat) return true;
    }
    return false;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'shipment' => null,
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    $shipment = $this->configuration['shipment'];
    $summary = '';
    if (!empty($shipment)) {
      $node = \Drupal\commerce_shipping\Entity\ShippingMethod::load($shipment);
      $summary = $this->t('Shipment: @text', ['@text' => $node->getName()]) . '<br/>';
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    if ($this->configuration['shipment']) {
      $node = \Drupal\commerce_shipping\Entity\ShippingMethod::load($this->configuration['shipment']);
    }
    else {
      $node = NULL;
    }
    $form['shipment'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'commerce_shipping_method',
      '#default_value' => $node,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['shipment'] = $values['shipment'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form['paczkomaty_mapa'] = ['#markup'=>'<div id="inpost_geowidget" data-shipment="'.$this->configuration['shipment'].'"></div>'];
    $pane_form['#attached']['library'][] = 'commerce_shipping_paczkomaty/inpost_geowidget';
    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    list($shipping_method) = explode('--',$form_state->getValue('shipping_information')['shipments'][0]['shipping_method'][0]);
    if($shipping_method==$this->configuration['shipment']) {
      $values = $form_state->getValues();
      if(!$values['shipping_information']['shipments'][0]['paczkomat'][0]['value']) $form_state->setErrorByName('shipping_information[shipments][0][paczkomat][0][value]',$this->t('Proszę wybrać paczkomat na mapie klikając w niego i zatwierdzając przyciskiem wybierz.'));
    }
    parent::validatePaneForm($pane_form, $form_state, $complete_form);
  }
}
