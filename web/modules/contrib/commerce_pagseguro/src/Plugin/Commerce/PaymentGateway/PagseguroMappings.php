<?php

namespace Drupal\commerce_pagseguro\Plugin\Commerce\PaymentGateway;

/**
 * Provides mappings for Pagseguro's status codes.
 **/
class PagseguroMappings {

  public static function mapPagseguroStatus($status) {
    switch ($status) {
      // Aguardando pagamento.
      case '1':
        return 'pending';
        break;

      // Em análise.
      case '2':
        return 'processing';
        break;

      // Paga.
      case '3':
        return 'completed';
        break;

      // Em disputa.
      case '5':
        return 'dispute';
        break;

      // Devolvida.
      case '6':
        return 'refunded';
        break;

      // Cancelada.
      case '7':
        return 'canceled';
        break;
    }
  }

  // Map Pagseguro errors.
  public static function mapPagseguroErrors($status) {
    switch ($status) {
      case 53004:
        return 'Items invalid quantity.';
        break;
      case 53005:
        return 'Currency is required.';
        break;
      case 53006:
        return 'Currency invalid value';
        break;
      case 53007:
        return '';
        break;
      case 53008:
        return '';
        break;
      case 53009:
        return '';
        break;
      case 53010:
        return '';
        break;
      case 53011:
        return '';
        break;
      case 53012:
        return '';
        break;
      case 53013:
        return '';
        break;
      case 53014:
        return '';
        break;
      case 53015:
        return '';
        break;
      case 53017:
        return '';
        break;
      case 53018:
        return '';
        break;
      case 53019:
        return '';
        break;
      case 53020:
        return '';
        break;
      case 53021:
        return '';
        break;
      case 53022:
        return '';
        break;
      case 53023:
        return '';
        break;
      case 53024:
        return '';
        break;
      case 53025:
        return '';
        break;
      case 53026:
        return '';
        break;
      case 53027:
        return '';
        break;
      case 53028:
        return '';
        break;
      case 53029:
        return '';
        break;
      case 53030:
        return '';
        break;
      case 53031:
        return '';
        break;
      case 53032:
        return '';
        break;
      case 53033:
        return '';
        break;
      case 53034:
        return '';
        break;
      case 53035:
        return '';
        break;
      case 53036:
        return '';
        break;
      case 53037:
        return '';
        break;
      case 53038:
        return '';
        break;
      case 53039:
        return '';
        break;
      case 53040:
        return '';
        break;
      case 53041:
        return '';
        break;
      case 53042:
        return '';
        break;
      case 53043:
        return '';
        break;
      case 53044:
        return '';
        break;
      case 53045:
        return '';
        break;
      case 53046:
        return '';
        break;
      case 53047:
        return '';
        break;
      case 53048:
        return '';
        break;
      case 53049:
        return '';
        break;
      case 53050:
        return '';
        break;
      case 53051:
        return '';
        break;
      case 53052:
        return '';
        break;
      case 53053:
        return '';
        break;
      case 53054:
        return '';
        break;
      case 53055:
        return '';
        break;
      case 53056:
        return '';
        break;
      case 53057:
        return '';
        break;
      case 53058:
        return '';
        break;
      case 53059:
        return '';
        break;
      case 53060:
        return '';
        break;
      case 53061:
        return '';
        break;
      case 53062:
        return '';
        break;
      case 53063:
        return '';
        break;
      case 53064:
        return '';
        break;
      case 53065:
        return '';
        break;
      case 53066:
        return '';
        break;
      case 53067:
        return '';
        break;
      case 53068:
        return '';
        break;
      case 53069:
        return '';
        break;
      case 53070:
        return '';
        break;
      case 53071:
        return '';
        break;
      case 53072:
        return '';
        break;
    }
  }
}