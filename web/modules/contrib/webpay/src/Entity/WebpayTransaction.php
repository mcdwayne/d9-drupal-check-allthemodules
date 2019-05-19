<?php

namespace Drupal\webpay\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Webpay transaction entity.
 *
 * @ingroup webpay
 *
 * @ContentEntityType(
 *   id = "webpay_transaction",
 *   label = @Translation("Webpay transaction"),
 *   handlers = {
 *     "view_builder" = "Drupal\webpay\Entity\WebpayTransactionViewBuilder",
 *     "list_builder" = "Drupal\webpay\WebpayTransactionListBuilder",
 *     "views_data" = "Drupal\webpay\Entity\WebpayTransactionViewsData",
 *     "access" = "Drupal\webpay\WebpayTransactionAccessControlHandler",
 *   },
 *   base_table = "webpay_transaction",
 *   admin_permission = "administer webpay transaction entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/webpay/webpay_transaction/{webpay_transaction}",
 *     "collection" = "/admin/config/webpay/webpay_transaction",
 *   },
 * )
 */
class WebpayTransaction extends ContentEntityBase implements WebpayTransactionInterface {

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [

    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }


  /**
   * Get the Payment type name.
   */
  public function getPaymentType() {
    $type_code = $this->get('payment_type_code')->value;
    $payment_type = self::getDefinitionPaymentType($type_code);

    return $payment_type['payment_type'];
  }

  /**
   * Get the Quota type name.
   */
  public function getQuotaType() {
    $type_code = $this->get('payment_type_code')->value;
    $payment_type = self::getDefinitionPaymentType($type_code);

    return $payment_type['quota_type'];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['config_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Configuration'))
      ->setSetting('target_type', 'webpay_config')
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['commerce_system_id'] = self::createFieldString(t('Commerce system ID'), 255, t('The commerce system used.'));
    $fields['token'] = self::createFieldString(t('Token'), 64, t('The token returned by webpay.'));
    $fields['order_number'] = self::createFieldString(t('Order number'), 26);
    $fields['session_id'] = self::createFieldString(t('Session ID'), 61);
    $fields['vci'] = self::createFieldString(t('VCI'), 3);
    $fields['card_number'] = self::createFieldString(t('Card Number'), 4);
    $fields['authorization_code'] = self::createFieldString(t('Authorization code'), 6);
    $fields['payment_type_code'] = self::createFieldString(t('Payment type code'), 2);

    $fields['transaction_date'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Transaction date'));
    $fields['response_code'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Response code'));
    $fields['amount'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Amount'));
    $fields['shares_number'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Shares number'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    return $fields;
  }

  /**
   * Helper method to create a simple string field.
   */
  protected static function createFieldString($label, $max_length = 255, $description = NULL) {
    return BaseFieldDefinition::create('string')
      ->setLabel($label)
      ->setRequired(TRUE)
      ->setSetting('max_length', $max_length)
      ->setDescription($description);
  }

  /**
   * Given a payment code returns the string that represents that code.
   *
   * @param string $payment_type_code
   *   payment type code, used by Webpay.
   * @param array $options
   *   An associative array of additional options, with the following elements:
   *   - 'langcode' (defaults to the current language): The language code to
   *     translate to a language other than what is used to display the page.
   *   - 'context' (defaults to the empty context): The context the source string
   *     belongs to.
   *
   * @return string
   *   Returns the equivalent text string.
   */
  public static function getDefinitionPaymentType($payment_type_code, array $options = []) {
    $credit = t("Credit", [], $options);
    switch ($payment_type_code) {
      case 'VN':
        return [
          'payment_type' => $credit,
          'quota_type' => t("Without quotas", [], $options),
        ];
      case 'VC':
        return [
          'payment_type' => $credit,
          'quota_type' => t("Quota normal", [], $options),
        ];
      case 'SI':
      case 'S2':
      case 'NC':
        return [
          'payment_type' => $credit,
          'quota_type' => t("No Interest", [], $options),
        ];
      case 'CI':
        return [
          'payment_type' => $credit,
          'quota_type' => t("Commerce Quotas", [], $options),
        ];
      case 'VD':
        return [
          'payment_type' => t("RedCompra", [], $options),
          'quota_type' => t("Debit", [], $options),
        ];
    }
  }
}
