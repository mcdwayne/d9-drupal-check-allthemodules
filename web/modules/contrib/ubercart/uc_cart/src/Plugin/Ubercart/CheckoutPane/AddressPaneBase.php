<?php

namespace Drupal\uc_cart\Plugin\Ubercart\CheckoutPane;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_cart\CheckoutPanePluginBase;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_store\Address;

/**
 * Provides a generic address pane that can be extended as required.
 */
abstract class AddressPaneBase extends CheckoutPanePluginBase {

  /**
   * Source pane for "copy address" checkbox.
   *
   * @var string
   */
  protected static $sourcePaneId;

  /**
   * Returns additional text to display as a description.
   *
   * @return string
   *   The fieldset description.
   */
  abstract protected function getDescription();

  /**
   * Returns text to display for the 'copy address' field.
   *
   * @return string
   *   The text to display.
   */
  abstract protected function getCopyAddressText();

  /**
   * {@inheritdoc}
   */
  public function view(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $pane = $this->pluginDefinition['id'];
    $source = $this->sourcePaneId();

    $contents['#description'] = $this->getDescription();

    if ($source != $pane) {
      $contents['copy_address'] = [
        '#type' => 'checkbox',
        '#title' => $this->getCopyAddressText(),
        '#default_value' => $this->configuration['default_same_address'],
        '#ajax' => [
          'callback' => [$this, 'ajaxRender'],
          'wrapper' => $pane . '-address-pane',
          'progress' => [
            'type' => 'throbber',
          ],
        ],
      ];
    }

    if ($user->isAuthenticated() && $addresses = uc_select_addresses($user->id(), $pane)) {
      $contents['select_address'] = [
        '#type' => 'select',
        '#title' => $this->t('Saved addresses'),
        '#options' => $addresses['#options'],
        '#ajax' => [
          'callback' => [$this, 'ajaxRender'],
          'wrapper' => $pane . '-address-pane',
          'progress' => [
            'type' => 'throbber',
          ],
        ],
        '#states' => [
          'invisible' => [
            'input[name="panes[' . $pane . '][copy_address]"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    $contents['address'] = [
      '#type' => 'uc_address',
      '#default_value' => $order->getAddress($pane),
      '#parents' => ['panes', $pane],
      '#prefix' => '<div id="' . $pane . '-address-pane">',
      '#suffix' => '</div>',
    ];

    if ($form_state->hasValue(['panes', $pane, 'copy_address'])) {
      $contents['address']['#hidden'] = !$form_state->isValueEmpty(['panes', $pane, 'copy_address']);
    }
    elseif (isset($contents['copy_address'])) {
      $contents['address']['#hidden'] = $this->configuration['default_same_address'];
    }

    // If this was an Ajax request, update form input values for the
    // copy and select address features.
    if ($element = $form_state->getTriggeringElement()) {
      $input = $form_state->getUserInput();

      if ($element['#name'] == "panes[$pane][copy_address]") {
        $address = &$form_state->getValue(['panes', $source]);
        foreach ($address as $field => $value) {
          $input['panes'][$pane][$field] = $value;
        }
      }

      if ($element['#name'] == "panes[$pane][select_address]" && isset($addresses[$element['#value']])) {
        $address = $addresses[$element['#value']];
        foreach ($address as $field => $value) {
          $input['panes'][$pane][$field] = $value;
        }
        $contents['address']['#default_value'] = $order->getAddress($pane);
      }

      $form_state->setUserInput($input);
    }

    return $contents;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $pane = $this->pluginDefinition['id'];
    $source = $this->sourcePaneId();

    $address = Address::create();
    $panes = &$form_state->getValue('panes');
    foreach ($panes[$pane] as $field => $value) {
      if (isset($address->$field)) {
        if (!empty($panes[$pane]['copy_address'])) {
          $address->$field = $panes[$source][$field];
        }
        else {
          $address->$field = $value;
        }
      }
    }
    if (isset($panes[$pane]['select_address']) && $panes[$pane]['select_address'] >= 0) {
      $addresses = uc_select_addresses(\Drupal::currentUser()->id(), $pane);
      foreach ($addresses[$panes[$pane]['select_address']] as $field => $value) {
        $address->$field = $value;
      }
    }
    $order->setAddress($pane, $address);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function review(OrderInterface $order) {
    $pane = $this->pluginDefinition['id'];
    $address = $order->getAddress($pane);
    $review[] = ['title' => $this->t('Address'), 'data' => ['#markup' => $address]];
    if (uc_address_field_enabled('phone') && !empty($address->getPhone())) {
      $review[] = ['title' => $this->t('Phone'), 'data' => ['#plain_text' => $address->getPhone()]];
    }
    return $review;
  }

  /**
   * Returns the ID of the source (first) address pane for copying.
   */
  protected function sourcePaneId() {
    if (!isset(self::$sourcePaneId)) {
      self::$sourcePaneId = $this->pluginDefinition['id'];
    }
    return self::$sourcePaneId;
  }

  /**
   * Ajax callback to re-render the full address element.
   */
  public function ajaxRender(array $form, FormStateInterface $form_state) {
    $element = &$form;
    $triggering_element = $form_state->getTriggeringElement();
    foreach (array_slice($triggering_element['#array_parents'], 0, -1) as $field) {
      $element = &$element[$field];
    }

    $response = new AjaxResponse();
    $id = $this->pluginDefinition['id'] . '-address-pane';
    $response->addCommand(new ReplaceCommand('#' . $id, trim(drupal_render($element['address']))));
    $status_messages = ['#type' => 'status_messages'];
    $response->addCommand(new PrependCommand('#' . $id, drupal_render($status_messages)));
    return $response;
  }

}
