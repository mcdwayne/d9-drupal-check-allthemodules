<?php

namespace Drupal\alipay;

interface AlipayGatewayInterface {

  const CLIENT_TYPE_WEBSITE = 'website';
  const CLIENT_TYPE_NATIVE_APP = 'native_app';
  const CLIENT_TYPE_H5 = 'h5';

  public function getClientLaunchConfig($commerce_order);
}