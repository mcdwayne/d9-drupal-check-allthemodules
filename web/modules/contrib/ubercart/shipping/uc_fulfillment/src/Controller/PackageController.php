<?php

namespace Drupal\uc_fulfillment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\uc_fulfillment\Package;
use Drupal\uc_fulfillment\Shipment;
use Drupal\uc_order\OrderInterface;

/**
 * Controller routines for packaging.
 */
class PackageController extends ControllerBase {

  /**
   * Displays a list of an order's packaged products.
   *
   * @param \Drupal\uc_order\OrderInterface $uc_order
   *   The order.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array, or a redirect response if there are no packaged products.
   */
  public function listOrderPackages(OrderInterface $uc_order) {
    $shipping_type_options = uc_quote_shipping_type_options();
    $header = [
      $this->t('Package ID'),
      $this->t('Products'),
      $this->t('Shipping type'),
      $this->t('Package type'),
      $this->t('Shipment ID'),
      $this->t('Tracking number'),
      $this->t('Labels'),
      $this->t('Actions'),
    ];
    $rows = [];
    $packages = Package::loadByOrder($uc_order->id());
    foreach ($packages as $package) {
      $row = [];
      // Package ID.
      $row[] = ['data' => ['#plain_text' => $package->id()]];

      $product_list = [];
      foreach ($package->getProducts() as $product) {
        $product_list[] = $product->qty . ' x ' . $product->model;
      }
      // Products.
      $row[] = ['data' => ['#theme' => 'item_list', '#items' => $product_list]];

      // Shipping type.
      $row[] = isset($shipping_type_options[$package->getShippingType()]) ? $shipping_type_options[$package->getShippingType()] : strtr($package->getShippingType(), '_', ' ');

      // Package type.
      $row[] = ['data' => ['#plain_text' => $package->getPackageType()]];

      // Shipment ID.
      $row[] = $package->getSid() ?
        ['data' => [
          '#type' => 'link',
          '#title' => $package->getSid(),
          '#url' => Url::fromRoute('uc_fulfillment.view_shipment', ['uc_order' => $uc_order->id(), 'uc_shipment' => $package->getSid()]),
        ]] : '';

      // Tracking number.
      $row[] = $package->getTrackingNumber() ? ['data' => ['#plain_text' => $package->getTrackingNumber()]] : '';

      if ($package->getLabelImage() && $image = file_load($package->getLabelImage())) {
        $package->setLabelImage($image);
      }
      else {
        $package->setLabelImage('');
      }

      // Shipping label.
      if ($package->getSid() && $package->getLabelImage()) {
        $shipment = Shipment::load($package->getSid());
        $row[] = Link::fromTextAndUrl("image goes here",
     //     theme('image_style', [
     //       'style_name' => 'uc_thumbnail',
     //       'uri' => $package->getLabelImage()->uri,
     //       'alt' => $this->t('Shipping label'),
     //       'title' => $this->t('Shipping label'),
     //     ]),
          Url::fromUri('base:admin/store/orders/' . $uc_order->id() . '/shipments/labels/' . $shipment->getShippingMethod() . '/' . $package->getLabelImage()->uri, ['uc_order' => $uc_order->id(), 'method' => $shipment->getShippingMethod(), 'image_uri' => $package->getLabelImage()->uri])
        )->toString();
      }
      else {
        $row[] = '';
      }

      // Operations.
      $ops = [
        '#type' => 'operations',
        '#links' => [
          'edit' => [
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute('uc_fulfillment.edit_package', ['uc_order' => $uc_order->id(), 'uc_package' => $package->id()]),
          ],
          'ship' => [
            'title' => $this->t('Ship'),
            'url' => Url::fromRoute('uc_fulfillment.new_shipment', ['uc_order' => $uc_order->id()], ['query' => ['pkgs' => $package->id()]]),
          ],
          'delete' => [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('uc_fulfillment.delete_package', ['uc_order' => $uc_order->id(), 'uc_package' => $package->id()]),
          ],
        ],
      ];
      if ($package->getSid()) {
        $ops['#links']['cancel'] = [
          'title' => $this->t('Cancel'),
          'url' => Url::fromRoute('uc_fulfillment.cancel_package', ['uc_order' => $uc_order->id(), 'uc_package' => $package->id()]),
        ];
      }
      $row[] = ['data' => $ops];
      $rows[] = $row;
    }

    if (empty($rows)) {
      $this->messenger()->addWarning($this->t("This order's products have not been organized into packages."));
      return $this->redirect('uc_fulfillment.new_package', ['uc_order' => $uc_order->id()]);
    }

    $build['packages'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return $build;
  }

}
