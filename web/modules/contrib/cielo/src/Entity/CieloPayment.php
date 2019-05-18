<?php

namespace Drupal\cielo\Entity;

use Cielo\API30\Ecommerce\Address;
use Cielo\API30\Ecommerce\CieloEcommerce;
use Cielo\API30\Ecommerce\CreditCard;
use Cielo\API30\Ecommerce\Environment;
use Cielo\API30\Ecommerce\Payment;
use Cielo\API30\Ecommerce\Request\CieloError;
use Cielo\API30\Ecommerce\Request\CieloRequestException;
use Cielo\API30\Ecommerce\Sale;
use Cielo\API30\Merchant;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the Cielo payment entity.
 *
 * @ingroup cielo
 *
 * @ContentEntityType(
 *   id = "cielo_payment",
 *   label = @Translation("Cielo payment"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\cielo\CieloPaymentListBuilder",
 *     "views_data" = "Drupal\cielo\Entity\CieloPaymentViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\cielo\Form\CieloPaymentForm",
 *       "add" = "Drupal\cielo\Form\CieloPaymentForm",
 *       "edit" = "Drupal\cielo\Form\CieloPaymentForm",
 *       "delete" = "Drupal\cielo\Form\CieloPaymentDeleteForm",
 *     },
 *     "access" = "Drupal\cielo\CieloPaymentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\cielo\CieloPaymentHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "cielo_payment",
 *   admin_permission = "administer cielo credit card payment entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/e-commerce/cielo_payment/{cielo_payment}",
 *     "add-form" = "/admin/config/e-commerce/cielo_payment/add",
 *     "edit-form" =
 *   "/admin/config/e-commerce/cielo_payment/{cielo_payment}/edit",
 *     "delete-form" =
 *   "/admin/config/e-commerce/cielo_payment/{cielo_payment}/delete",
 *     "collection" = "/admin/config/e-commerce/cielo_payment",
 *   },
 *   field_ui_base_route = "cielo_payment.settings"
 * )
 */
class CieloPayment extends ContentEntityBase implements CieloPaymentInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Cielo credit card payment entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Cielo credit card payment entity.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Cielo credit card payment is published.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    // Cielo request info.
    $fields['merchant_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('MerchantId'))
      ->setDescription(t('Identificador da loja na Cielo.'))
      ->setSetting('max_length', 36)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 1,
      ]);

    $fields['merchant_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('MerchantKey'))
      ->setDescription(t('Chave Publica para Autenticação Dupla na Cielo.'))
      ->setSetting('max_length', 40)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 2,
      ]);

    $fields['request_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('RequestId'))
      ->setDescription(t('Identificador do Request, utilizado quando o lojista usa diferentes servidores para cada GET/POST/PUT.'))
      ->setSetting('max_length', 36)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 3,
      ]);

    $fields['merchant_order_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('MerchantOrderId'))
      ->setDescription(t('Numero de identificação do Pedido.'))
      ->setSetting('max_length', 50)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 4,
      ]);

    $fields['customer_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer.Name'))
      ->setDescription(t('	Nome do Comprador.'))
      ->setSetting('max_length', 255)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 5,
      ]);

    $fields['customer_status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer.Status'))
      ->setDescription(t('Status de cadastro do comprador na loja (NEW / EXISTING)'))
      ->setSetting('max_length', 255)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 6,
      ]);

    $fields['customer_identity'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer.Identity'))
      ->setDescription(t('	Número do RG, CPF ou CNPJ do Cliente.'))
      ->setSetting('max_length', 14)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 7,
      ]);

    $fields['customer_identity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer.IdentityType'))
      ->setDescription(t('Tipo de documento de identificação do comprador (CFP/CNPJ).'))
      ->setSetting('max_length', 255)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 8,
      ]);

    $fields['customer_email'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer.Email'))
      ->setDescription(t('Email do Comprador.'))
      ->setSetting('max_length', 255)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 9,
      ]);

    $fields['customer_birthdate'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Customer.Birthdate'))
      ->setDescription(t('Data de nascimento do Comprador.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 10,
      ]);

    $fields['customer_address_street'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer.Address.Street'))
      ->setDescription(t('Endereço do Comprador.'))
      ->setSetting('max_length', 255)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 11,
      ]);

    $fields['customer_address_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer.Address.Number'))
      ->setDescription(t('Número do endereço do Comprador.'))
      ->setSetting('max_length', 15)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 12,
      ]);

    $fields['customer_address_complement'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer.Address.Complement'))
      ->setDescription(t('Complemento do endereço do Comprador.'))
      ->setSetting('max_length', 50)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 13,
      ]);

    $fields['customer_address_zip_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer.Address.ZipCode'))
      ->setDescription(t('CEP do endereço do Comprador.'))
      ->setSetting('max_length', 9)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 14,
      ]);

    $fields['customer_address_city'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer.Address.City'))
      ->setDescription(t('Cidade do endereço do Comprador.'))
      ->setSetting('max_length', 50)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 15,
      ]);

    $fields['customer_address_district'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer.Address.District'))
      ->setDescription(t('Bairro do Comprador.'))
      ->setSetting('max_length', 50)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 15,
      ]);

    $fields['customer_address_state'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer.Address.State'))
      ->setDescription(t('Estado do endereço do Comprador.'))
      ->setSetting('max_length', 2)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 16,
      ]);

    $fields['customer_address_country'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer.Address.Country'))
      ->setDescription(t('	Pais do endereço do Comprador.'))
      ->setSetting('max_length', 35)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 17,
      ]);

    $fields['customer_delivery_address_street'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer.DeliveryAddress.Street'))
      ->setDescription(t('Endereço de entrega do Comprador.'))
      ->setSetting('max_length', 255)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 18,
      ]);

    $fields['customer_delivery_address_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer.Address.Number'))
      ->setDescription(t('Número do endereço de entrega do Comprador.'))
      ->setSetting('max_length', 15)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 19,
      ]);

    $fields['customer_delivery_address_complement'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer.DeliveryAddress.Complement'))
      ->setDescription(t('Complemento do endereço de entrega do Comprador.'))
      ->setSetting('max_length', 50)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 20,
      ]);

    $fields['customer_delivery_address_zip_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer.DeliveryAddress.ZipCode'))
      ->setDescription(t('CEP do endereço de entrega do Comprador.'))
      ->setSetting('max_length', 9)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 21,
      ]);

    $fields['customer_delivery_address_city'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer.DeliveryAddress.City'))
      ->setDescription(t('Cidade do endereço de entrega do Comprador.'))
      ->setSetting('max_length', 50)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 22,
      ]);

    $fields['customer_delivery_address_state'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer.DeliveryAddress.State'))
      ->setDescription(t('Estado do endereço de entrega do Comprador.'))
      ->setSetting('max_length', 2)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 23,
      ]);

    $fields['customer_delivery_address_country'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer.DeliveryAddress.Country'))
      ->setDescription(t('Pais do endereço de entrega do Comprador.'))
      ->setSetting('max_length', 35)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 24,
      ]);

    $fields['payment_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment.Type'))
      ->setDescription(t('Tipo do Meio de Pagamento.'))
      ->setSetting('max_length', 100)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 25,
      ]);

    $fields['payment_amount'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Payment.Amount'))
      ->setDescription(t('Valor do Pedido (ser enviado em centavos).'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 26,
      ]);

    $fields['payment_currency'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment.Currency'))
      ->setDescription(t('Moeda na qual o pagamento será feito (BRL).'))
      ->setSetting('max_length', 3)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 27,
      ]);

    $fields['payment_country'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment.Country'))
      ->setDescription(t('Pais na qual o pagamento será feito.'))
      ->setSetting('max_length', 3)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 28,
      ]);

    $fields['payment_provider'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment.Provider'))
      ->setDescription(t('Define comportamento do meio de pagamento (ver Anexo)/NÃO OBRIGATÓRIO PARA CRÉDITO.'))
      ->setSetting('max_length', 15)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 29,
      ]);

    $fields['payment_assignor'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment.Assignor'))
      ->setDescription(t('	Nome do Cedente.'))
      ->setSetting('max_length', 200)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 29,
      ]);

    $fields['payment_identification'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment.Identification'))
      ->setDescription(t('	Documento de identificação do Cedente.'))
      ->setSetting('max_length', 14)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 29,
      ]);

    $fields['payment_adress'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment.Adress'))
      ->setDescription(t('Endereço do Cedente.'))
      ->setSetting('max_length', 255)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 29,
      ]);

    $fields['payment_demonstrative'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment.Demonstrative'))
      ->setDescription(t('	Texto de Demonstrativo.'))
      ->setSetting('max_length', 255)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 29,
      ]);

    $fields['payment_expiration_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Payment.ExpirationDate'))
      ->setDescription(t('	Data de expiração do Boleto.'))
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 29,
      ]);

    $fields['payment_boleto_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment.BoletoNumber'))
      ->setDescription(t('Número do Boleto enviado pelo lojista. Usado para contar boletos emitidos (“NossoNumero”).'))
      ->setSetting('max_length', 50)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 29,
      ]);

    $fields['payment_instructions'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment.Instructions'))
      ->setDescription(t('Instruções do Boleto.'))
      ->setSetting('max_length', 255)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 29,
      ]);

    $fields['payment_service_tax_amount'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Payment.ServiceTaxAmount'))
      ->setDescription(t('Payment Service Tax Amount'))
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 30,
      ]);

    $fields['payment_soft_descriptor'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment.SoftDescriptor'))
      ->setDescription(t('Texto impresso na fatura bancaria comprador - Exclusivo para VISA/MASTER - não permite caracteres especiais'))
      ->setSetting('max_length', 13)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 31,
      ]);

    $fields['payment_installments'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Payment.Installments'))
      ->setDescription(t('Número de Parcelas.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 32,
      ]);

    $fields['payment_interest'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment.Interest'))
      ->setDescription(t('Tipo de parcelamento - Loja (ByMerchant) ou Cartão (ByIssuer).'))
      ->setSetting('max_length', 10)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 33,
      ]);

    $fields['payment_capture'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Payment.Capture'))
      ->setDescription(t('Booleano que identifica que a autorização deve ser com captura automática.'))
      ->setDefaultValue(FALSE)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 34,
      ]);

    $fields['payment_authenticate'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Payment.Authenticate'))
      ->setDescription(t('Define se o comprador será direcionado ao Banco emissor para autenticação do cartão'))
      ->setDefaultValue(FALSE)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 35,
      ]);

    $fields['payment_return_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment.ReturnUrl'))
      ->setDescription(t('URI para onde o usuário será redirecionado após o fim do pagamento'))
      ->setSetting('max_length', 1024)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 35,
      ]);

    $fields['debit_card_card_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('DebitCard.CardNumber'))
      ->setDescription(t('	Número do Cartão do Comprador..'))
      ->setSetting('max_length', 19)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 36,
      ]);

    $fields['debit_card_holder'] = BaseFieldDefinition::create('string')
      ->setLabel(t('DebitCard.Holder'))
      ->setDescription(t('Nome do Comprador impresso no cartão.'))
      ->setSetting('max_length', 25)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 37,
      ]);

    $fields['debit_card_expiration_date'] = BaseFieldDefinition::create('string')
      ->setLabel(t('DebitCard.ExpirationDate'))
      ->setDescription(t('Data de validade impresso no cartão'))
      ->setSetting('max_length', 7)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 38,
      ]);

    $fields['debit_card_security_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('DebitCard.SecurityCode'))
      ->setDescription(t('	Código de segurança impresso no verso do cartão'))
      ->setSetting('max_length', 4)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 38,
      ]);

    $fields['debit_card_brand'] = BaseFieldDefinition::create('string')
      ->setLabel(t('DebitCard.Brand'))
      ->setDescription(t('Bandeira do cartão (Visa / Master / Amex / Elo / Aura / JCB / Diners / Discover / Hipercard).'))
      ->setSetting('max_length', 10)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 39,
      ]);

    $fields['credit_card_card_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('CreditCard.CardNumber'))
      ->setDescription(t('	Número Cartão do Comprador.'))
      ->setSetting('max_length', 19)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 36,
      ]);

    $fields['credit_card_holder'] = BaseFieldDefinition::create('string')
      ->setLabel(t('CreditCard.Holder'))
      ->setDescription(t('Nome do Comprador impresso no cartão.'))
      ->setSetting('max_length', 25)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 37,
      ]);

    $fields['credit_card_expiration_date'] = BaseFieldDefinition::create('string')
      ->setLabel(t('CreditCard.ExpirationDate'))
      ->setDescription(t('Data de validade impresso no cartão'))
      ->setSetting('max_length', 7)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 38,
      ]);

    $fields['credit_card_security_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('CreditCard.SecurityCode'))
      ->setDescription(t('	Código de segurança impresso no verso do cartão'))
      ->setSetting('max_length', 4)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 38,
      ]);

    $fields['credit_card_brand'] = BaseFieldDefinition::create('string')
      ->setLabel(t('CreditCard Brand'))
      ->setDescription(t('Bandeira do cartão (Visa / Master / Amex / Elo / Aura / JCB / Diners / Discover / Hipercard).'))
      ->setSetting('max_length', 10)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 39,
      ]);

    $fields['credit_card_save_card'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('CreditCard.SaveCard'))
      ->setDescription(t('Booleano que identifica se o cartão será salvo para gerar o CardToken.'))
      ->setDefaultValue(FALSE)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 40,
      ]);

    // Cielo response info.
    $fields['credit_card_token'] = BaseFieldDefinition::create('string')
      ->setLabel(t('CreditCard Token'))
      ->setDescription(t('Token gerado pelo salvamento do Cartão de Crédito na cielo.'))
      ->setDefaultValue(FALSE)
      ->setSetting('max_length', 40)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 41,
      ]);

    $fields['authentication_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('AuthenticationUrl'))
      ->setDescription(t('URL para qual o Lojista deve redirecionar o Cliente para o fluxo de Débito.'))
      ->setSetting('max_length', 10)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 41,
      ]);

    $fields['proof_of_sale'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ProofOfSale'))
      ->setDescription(t('Número da autorização, identico ao NSU.'))
      ->setSetting('max_length', 10)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 42,
      ]);

    $fields['tid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tid'))
      ->setDescription(t('Id da transação na adquirente.'))
      ->setSetting('max_length', 20)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 43,
      ]);

    $fields['authorization_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('AuthorizationCode'))
      ->setDescription(t('Código de autorização.'))
      ->setSetting('max_length', 6)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 44,
      ]);

    $fields['soft_descriptor'] = BaseFieldDefinition::create('string')
      ->setLabel(t('SoftDescriptor'))
      ->setDescription(t('Texto que será impresso na fatura bancaria do portador - Disponivel apenas para VISA/MASTER - nao permite caracteres especiais'))
      ->setSetting('max_length', 13)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 45,
      ]);

    $fields['payment_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('PaymentId'))
      ->setDescription(t('Campo Identificador do Pedido.'))
      ->setSetting('max_length', 36)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 46,
      ]);

    $fields['instructions'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Instructions'))
      ->setDescription(t('Instruções do Boleto.'))
      ->setSetting('max_length', 255)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 46,
      ]);

    $fields['expiration_date'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ExpirationDate'))
      ->setDescription(t('Data de expiração.'))
      ->setSetting('max_length', 10)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 46,
      ]);

    $fields['url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Url'))
      ->setDescription(t('Url do Boleto gerado.'))
      ->setSetting('max_length', 256)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 46,
      ]);

    $fields['number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Number'))
      ->setDescription(t('“NossoNumero” gerado.'))
      ->setSetting('max_length', 50)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 46,
      ]);

    $fields['bar_code_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('BarCodeNumber'))
      ->setDescription(t('Representação numérica do código de barras.'))
      ->setSetting('max_length', 50)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 46,
      ]);

    $fields['digitable_line'] = BaseFieldDefinition::create('string')
      ->setLabel(t('DigitableLine'))
      ->setDescription(t('Linha digitável.'))
      ->setSetting('max_length', 256)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 46,
      ]);

    $fields['assignor'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Assignor'))
      ->setDescription(t('Nome do Cedente.'))
      ->setSetting('max_length', 256)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 46,
      ]);

    $fields['address'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Address'))
      ->setDescription(t('Endereço do Cedente.'))
      ->setSetting('max_length', 256)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 46,
      ]);

    $fields['identification'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Identification'))
      ->setDescription(t('CPF ou CNPJ do Cedente sem os caracteres especiais (., /, -)'))
      ->setSetting('max_length', 14)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 46,
      ]);

    $fields['eci'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ECI'))
      ->setDescription(t('Eletronic Commerce Indicator. Representa o quão segura é uma transação.'))
      ->setSetting('max_length', 2)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 47,
      ]);

    $fields['return_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('	ReturnUrl'))
      ->setDescription(t('Url de retorno do lojista. URL para onde o lojista vai ser redirecionado no final do fluxo.'))
      ->setSetting('max_length', 1024)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 48,
      ]);

    $fields['transaction_status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('	Status'))
      ->setDescription(t('	Status da Transação.'))
      ->setSetting('max_length', 1)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 48,
      ]);

    $fields['return_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ReturnCode'))
      ->setDescription(t('	Código de retorno da Adquirência.'))
      ->setSetting('max_length', 32)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 49,
      ]);

    $fields['return_message'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ReturnMessage'))
      ->setDescription(t('Mensagem de retorno da Adquirência.'))
      ->setSetting('max_length', 512)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 50,
      ]);

    // Error code and message.
    $fields['code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Code'))
      ->setDescription(t('Código de Erro da API.'))
      ->setSetting('max_length', 5)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 51,
      ]);

    $fields['message'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Message'))
      ->setDescription(t('Descrição do erro.'))
      ->setSetting('max_length', 255)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 51,
      ]);

    $fields['cancellation_status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('	Cancellation Status'))
      ->setDescription(t('	Status do cancelamento da Transação.'))
      ->setSetting('max_length', 10)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 52,
      ]);

    // Error code and message.
    $fields['cancellation_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Cancellation Code'))
      ->setDescription(t('Cancelamento: Código de Erro da API.'))
      ->setSetting('max_length', 5)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 55,
      ]);

    $fields['cancellation_message'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Cancellation Message'))
      ->setDescription(t('Descrição do erro de cancelamento.'))
      ->setSetting('max_length', 255)
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 56,
      ]);

    $fields['json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Json Response'))
      ->setDescription(t('Json response.'))
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 1000,
      ]);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);

    return $this;
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
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * The API environment (sandbox | production).
   *
   * @var Environment
   */
  private $environment;

  /**
   * The API merchant.
   *
   * @var Merchant
   */
  private $merchant;

  /**
   * Save transaction log.
   *
   * @var bool
   */
  private $saveLog;

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);

    return $this;
  }

  /**
   * Process the Cielo payment.
   *
   * @param string $paymentType
   *   The payment type.
   *
   * @throws \Exception
   */

  /**
   * Process the Cielo payment.
   *
   * @param string $paymentType
   *   The payment type.
   * @param \Drupal\cielo\Entity\CieloProfile $profile
   *   The cielo profile to be used.
   *
   * @return \Cielo\API30\Ecommerce\Sale
   *   The precessed cielo sale.
   *
   * @throws \Exception
   */
  public function processPayment($paymentType, CieloProfile $profile) {
    // Configure SDK with merchant and environment.
    $this->environment = $profile->getEnvironment();
    $this->merchant = new Merchant($profile->getMerchantId(), $profile->getMerchantKey());
    $this->saveLog = $profile->isSaveTransactionLog();

    if (!$this->environment) {
      throw new \Exception('Cielo API not configured yet. Please contact the site administrator.');
    }
    elseif ($this->environment == 'sandbox') {
      $this->environment = Environment::sandbox();
    }
    elseif ($this->environment == 'production') {
      $this->environment = Environment::production();
    }

    $pre_sale = new Sale($this->get('merchant_order_id')->value);

    // Set customer address.
    $customer_address = NULL;
    // Only fill this information if delivery country is informed.
    // This field is required by cielo API.
    if ($this->get('customer_address_country')->value) {
      $customer_address = new Address();
      $customer_address->setStreet($this->get('customer_address_street')->value)
        ->setNumber($this->get('customer_address_number')->value)
        ->setComplement($this->get('customer_address_complement')->value)
        ->setDistrict($this->get('customer_address_district')->value)
        ->setCity($this->get('customer_address_city')->value)
        ->setState($this->get('customer_address_state')->value)
        ->setCountry($this->get('customer_address_country')->value)
        ->setZipCode($this->get('customer_address_zip_code')->value);
    }

    // Set customer delivery adress.
    $customer_delivery_address = NULL;
    // Only fill this information if delivery country is informed.
    // This field is required by cielo API.
    if ($this->get('customer_delivery_address_country')->value) {
      $customer_delivery_address = new Address();
      $customer_delivery_address->setStreet($this->get('customer_delivery_address_street')->value)
        ->setNumber($this->get('customer_delivery_address_number')->value)
        ->setComplement($this->get('customer_delivery_address_complement')->value)
        ->setCity($this->get('customer_delivery_address_city')->value)
        ->setState($this->get('customer_delivery_address_state')->value)
        ->setCountry($this->get('customer_delivery_address_country')->value)
        ->setZipCode($this->get('customer_delivery_address_zip_code')->value);
    }

    $pre_sale->customer($this->get('customer_name')->value)
      ->setIdentity($this->get('customer_identity')->value)
      ->setBirthDate($this->get('customer_birthdate')->value)
      ->setEmail($this->get('customer_email')->value)
      ->setIdentityType($this->get('customer_identity_type')->value)
      ->setAddress($customer_address)
      ->setDeliveryAddress($customer_delivery_address);

    $payment = $pre_sale->payment($this->get('payment_amount')->value);
    $payment->setServiceTaxAmount($this->get('payment_service_tax_amount')->value);

    if ($paymentType == Payment::PAYMENTTYPE_CREDITCARD) {
      $save_card = $this->get('payment_capture')->value == 1 || $this->get('payment_capture')->value == TRUE || $this->get('payment_capture')->value == '1' || strtoupper($this->get('payment_capture')->value) == 'TRUE';

      $payment->setType(Payment::PAYMENTTYPE_CREDITCARD)
        ->creditCard(trim($this->get('credit_card_security_code')->value), $this->get('credit_card_brand')->value)
        ->setExpirationDate($this->get('credit_card_expiration_date')->value)
        ->setCardNumber($this->get('credit_card_card_number')->value)
        ->setHolder($this->get('credit_card_holder')->value)
        ->setSaveCard($save_card);
    }
    elseif ($paymentType == Payment::PAYMENTTYPE_DEBITCARD) {
      // TODO configure debit card.
      $pre_sale = new Sale('123');
      // Crie uma instância de Customer informando o nome do cliente
      $customer = $pre_sale->customer('Fulano de Tal');

      // Crie uma instância de Payment informando o valor do pagamento
      $payment = $pre_sale->payment(15700);

      // Defina a URL de retorno para que o cliente possa voltar para a loja
      // após a autenticação do cartão
      $payment->setReturnUrl('https://localhost/test');

      // Crie uma instância de Debit Card utilizando os dados de teste
      // esses dados estão disponíveis no manual de integração
      $payment->debitCard("123", CreditCard::VISA)
        ->setExpirationDate("12/2018")
        ->setCardNumber("0000000000000001")
        ->setHolder("Fulano de Tal");

    }
    elseif ($paymentType == Payment::PAYMENTTYPE_BOLETO) {
      $payment->setType(Payment::PAYMENTTYPE_BOLETO)
        ->setProvider($this->get('payment_provider')->value)
        ->setAddress($this->get('payment_adress')->value)
        ->setBoletoNumber($this->get('payment_boleto_number')->value)
        ->setAssignor($this->get('payment_assignor')->value)
        ->setDemonstrative($this->get('payment_demonstrative')->value)
        ->setExpirationDate($this->get('payment_expiration_date')->value)
        ->setIdentification($this->get('payment_identification')->value)
        ->setInstructions($this->get('payment_instructions')->value);
    }

    // Do payment.
    try {

      // Save sent message log.
      if ($this->saveLog) {
        $data_sent_to_cielo = json_encode($pre_sale);
        $data_sent_to_cielo = json_decode($data_sent_to_cielo);
        if ($paymentType == Payment::PAYMENTTYPE_CREDITCARD) {
          $data_sent_to_cielo->payment->creditCard = '***';
        }
        if ($paymentType == Payment::PAYMENTTYPE_DEBITCARD) {
          $data_sent_to_cielo->payment->debitCard = '***';
        }
        $data_sent_to_cielo = json_encode($data_sent_to_cielo);
        \Drupal::logger('cielo')
          ->notice('Data sent to cielo (credit card number removed for safe reasons). ' . serialize($data_sent_to_cielo));
      }

      $cielo_ecommerce = new CieloEcommerce($this->merchant, $this->environment);
      $sale = $cielo_ecommerce->createSale($pre_sale);

      // Capture payment.
      $paymentId = $sale->getPayment()->getPaymentId();
      if ($paymentType == Payment::PAYMENTTYPE_CREDITCARD) {
        $captureSale = (new CieloEcommerce($this->merchant, $this->environment))->captureSale($paymentId, $payment->getAmount(), $payment->getServiceTaxAmount());
      }

      $this->savePaymentData($sale, $profile, $data_sent_to_cielo, $captureSale);

      // Save received message log.
      if ($this->saveLog) {
        $data_received_from_cielo = json_encode($sale);
        $data = [
          'payment-data' => $data_received_from_cielo,
          'capture-data' => json_encode($captureSale),
        ];
        \Drupal::logger('cielo')
          ->notice('Data received from cielo. ' . serialize($data));
      }

      return $sale;
    }
    catch (\Exception $e) {
      // TODO enviar erro para tratamento, exibir tela de erro.
      $json = Json::encode($pre_sale);

      if ($e instanceof CieloRequestException) {
        // $error = $e->getCieloError();
        $code = $e->getCode();
        $message = $e->getMessage();
      }
      else {
        $code = $e->getCode();
        $message = $e->getMessage();
      }

      \Drupal::logger('cielo')
        ->error('Error processing cielo payment. code: ' . $code . ' message: ' . $message . ' Json: ' . $json);

      return $pre_sale;
    }
  }

  /**
   * Save the payment information on database.
   *
   * @param \Cielo\API30\Ecommerce\Sale $sale
   *   The cielo sale.
   * @param \Drupal\cielo\Entity\CieloProfile $profile
   *   The cielo Profile.
   * @param string $data_sent_to_cielo
   *   The json sent to cielo.
   * @param string $captureSale
   *   The json of the captured sale.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function savePaymentData(Sale $sale, CieloProfile $profile, $data_sent_to_cielo, $captureSale) {
    /** @var \Cielo\API30\Ecommerce\Payment $payment */
    $payment = $sale->getPayment();
    /** @var \Cielo\API30\Ecommerce\CreditCard $credit_card */
    $credit_card = $payment->getCreditCard();

    // Entity information.
    $this->set('name', $payment->getType() . ' - mId:' . $this->get('merchant_order_id')->value);
    $this->set('uuid', $this->uuidGenerator()->generate());
    $this->set('langcode', \Drupal::languageManager()
      ->getCurrentLanguage()
      ->getId());
    $this->set('user_id', \Drupal::currentUser()->id());
    $this->set('status', 1);
    $this->setCreatedTime(time());
    $this->setChangedTime(time());

    $this->set('merchant_id', $profile->getMerchantId());
    $this->set('merchant_key', $profile->getMerchantKey());

    $this->set('payment_id', $payment->getPaymentId());
    $this->set('payment_type', $payment->getType());
    $this->set('payment_amount', $payment->getAmount());
    $this->set('payment_currency', $payment->getCurrency());
    $this->set('payment_country', $payment->getCountry());
    $this->set('payment_provider', $payment->getProvider());
    $this->set('payment_service_tax_amount', $payment->getServiceTaxAmount());
    $this->set('payment_soft_descriptor', $payment->getSoftDescriptor());
    $this->set('payment_installments', $payment->getInstallments());
    $this->set('payment_interest', $payment->getInterest());
    $this->set('payment_capture', $payment->getCapture());
    $this->set('payment_authenticate', $payment->getAuthenticate());

    // Set payment_expiration_date to timestamp.
    if ($payment->getExpirationDate()) {
      $this->set('payment_expiration_date', strtotime($payment->getExpirationDate()));
    }

    if ($credit_card) {
      $this->set('credit_card_card_number', $credit_card->getCardNumber());
      $this->set('credit_card_holder', $credit_card->getHolder());
      $this->set('credit_card_expiration_date', $credit_card->getExpirationDate());
      $this->set('credit_card_token', $credit_card->getCardToken());
      $this->set('credit_card_save_card', $credit_card->getSaveCard());
      $this->set('credit_card_brand', $credit_card->getBrand());
    }

    $this->set('proof_of_sale', $payment->getProofOfSale());
    $this->set('tid', $payment->getTid());
    $this->set('authorization_code', $payment->getAuthorizationCode());
    $this->set('soft_descriptor', $payment->getSoftDescriptor());
    $this->set('transaction_status', $payment->getStatus());
    $this->set('return_code', $payment->getReturnCode());
    $this->set('return_message', $payment->getReturnMessage());
    $this->set('code', $payment->getReturnCode());
    $this->set('message', $payment->getReturnMessage());

    $this->set('url', $payment->getUrl());
    $this->set('number', $payment->getNumber());
    $this->set('bar_code_number', $payment->getBarCodeNumber());
    $this->set('digitable_line', $payment->getDigitableLine());
    $this->set('assignor', $payment->getAssignor());
    $this->set('address', $payment->getAddress());
    $this->set('identification', $payment->getIdentification());
    $this->set('payment_boleto_number', $payment->getBoletoNumber());

    $json = [
      'sent-data' => $data_sent_to_cielo,
      'received-data' => Json::encode($sale),
      'capture-data' => Json::encode($captureSale),
    ];

    $this->set('json', serialize($json));

    $this->save();
  }

  /**
   * Cancel an credit card payment order.
   *
   * @return \Cielo\API30\Ecommerce\Sale|\Exception
   *   The sale or the error.
   */
  public function cancelCreditCardOrder() {

    $payment_id = $this->id();

    $profile = \Drupal::entityTypeManager()
      ->getStorage('cielo_profile')
      ->loadByProperties([
        'merchant_id' => $this->get('merchant_id')->value,
        'merchant_key' => $this->get('merchant_key')->value,
      ]);

    /** @var \Drupal\cielo\Entity\CieloProfile $profile */
    $profile = $profile[array_keys($profile)[0]];
    $merchant = new Merchant($profile->getMerchantId(), $profile->getMerchantKey());
    $environment = $profile->getEnvironment() == 'production' ? Environment::production() : Environment::sandbox();
    $this->saveLog = $profile->isSaveTransactionLog();

    try {

      // Save sent message log.
      if ($this->saveLog) {
        \Drupal::logger('cielo')
          ->notice('Credit card cancellation. Payment id: ' . $payment_id);
      }

      $payment_id = $this->get('payment_id')->value;
      $amount = $this->get('payment_amount')->value;
      $sale = (new CieloEcommerce($merchant, $environment))->cancelSale($payment_id, $amount);

      // Save sent message log.
      if ($this->saveLog) {
        \Drupal::logger('cielo')
          ->notice('Credit card cancellation success. Payment id: ' . $payment_id);
      }

      $this->set('cancellation_status', $sale->getStatus());
      $this->set('cancellation_code', $sale->getReturnCode());
      $this->set('cancellation_message', $sale->getReturnMessage());
      $this->save();

      return $sale;
    }
    catch (\Exception $e) {

      if ($e instanceof CieloError) {
        $error = $e->getCieloError();
        $code = $error->getCode();
        $message = $error->getMessage();
      }
      else {
        $code = $e->getCode();
        $message = $e->getMessage();
      }

      $error_message = 'Credit card cancellation error. Code: ' . $code . ' Message: ' . $message;
      \Drupal::logger('cielo')
        ->error($error_message);

      $this->set('cancellation_status', -1);
      $this->set('cancellation_code', $code);
      $this->set('cancellation_message', $message);
      $this->save();

      return $e;
    }
  }

}
