<?php

namespace Drupal\customers_canvas\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines HelloController class.
 */
class Builder extends ControllerBase {

  /**
   * Display the builder for a particular user and entity.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The creation belongs to a user, usually the current but not always.
   * @param \Drupal\Core\Entity\EntityInterface $cc_entity
   *   The entity that stores the JSON builder string specific to the Customers
   *   Canvas implementation.
   * @param string $state_id
   *   The unique identifier for a Customer's Canvas state. Can be used to load
   *   a specific object.
   *
   * @return array
   *   Return markup array.
   */
  public function content(AccountInterface $user, EntityInterface $cc_entity, $state_id) {
    $product_json = $cc_entity->get('cc_product_json');
    if ($product_json) {
      $product_json = $product_json->getValue()[0]['value'];
      if ($state_id !== '') {
        $product_json = HTML::escape($state_id);
      }
      $builder_json = $this->config('customers_canvas.settings')->get('builder_json');

      // Inject settings for user id.
      // See https://customerscanvas.com/docs/cc/customerscanvas.iframeapi.configuration.iconfiguration.htm
      $builder_json = json_decode($builder_json, TRUE);
      $builder_json['userId'] = $user->id();
      $builder_json = json_encode($builder_json);

      return [
        '#theme' => 'customers_canvas_builder',
        '#owner' => $user,
        '#entity' => $cc_entity,
        '#finish_form' => $this->formBuilder()->getForm('Drupal\customers_canvas\Form\Builder', [
          'cc_entity' => $cc_entity,
          'owner' => $user,
          'state_id' => $state_id,
        ]),
        '#attached' => [
          'library' => ['customers_canvas/builder'],
          'drupalSettings' => [
            'customersCanvas' => [
              'productJson' => $product_json,
              'builderJson' => $builder_json,
            ],
          ],
        ],
      ];
    }
  }

}
