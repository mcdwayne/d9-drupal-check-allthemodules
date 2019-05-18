<?php

namespace Drupal\bookkeeping\Entity;

use Drupal\bookkeeping\Plugin\Field\FieldType\BookkeepingEntryItem;
use Drupal\commerce_price\Price;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the Transaction entity.
 *
 * @ingroup bookkeeping
 *
 * @ContentEntityType(
 *   id = "bookkeeping_transaction",
 *   label = @Translation("Transaction"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\bookkeeping\TransactionListBuilder",
 *     "access" = "Drupal\bookkeeping\TransactionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\bookkeeping\TransactionHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "bookkeeping_transaction",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "description",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/bookkeeping/transactions/{bookkeeping_transaction}",
 *     "collection" = "/admin/bookkeeping/transactions",
 *   }
 * )
 */
class Transaction extends ContentEntityBase implements TransactionInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    if ($description = $this->get('description')->value) {
      return $description;
    }
    else {
      return new FormattableMarkup('Transaction @id', [
        '@id' => $this->id(),
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addEntry($account, Price $amount, int $type) {
    // If the amount is negative, change to positive and swap the type.
    if ($amount->isNegative()) {
      $amount = new Price(-$amount->getNumber(), $amount->getCurrencyCode());
      $type = $type == BookkeepingEntryItem::TYPE_DEBIT ? BookkeepingEntryItem::TYPE_CREDIT : BookkeepingEntryItem::TYPE_DEBIT;
    }

    // Add the entry.
    $this->get('entries')->appendItem([
      'target_id' => $account instanceof AccountInterface ? $account->id() : $account,
      'amount' => $amount->getNumber(),
      'currency_code' => $amount->getCurrencyCode(),
      'type' => $type,
    ]);

    // Allow chaining.
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addRelated(EntityInterface $entity) {
    $this->get('related')->appendItem($entity);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelated(string $entity_type_id = NULL): array {
    /** @var \Drupal\dynamic_entity_reference\Plugin\Field\FieldType\DynamicEntityReferenceFieldItemList $items */
    $items = $this->get('related');

    // If there's not filter, pass on directly.
    if (!$entity_type_id) {
      return $items->referencedEntities();
    }

    // Otherwise manually loop over the items to avoid loading all items.
    $entities = [];

    /** @var \Drupal\dynamic_entity_reference\Plugin\Field\FieldType\DynamicEntityReferenceItem $item */
    foreach ($items as $delta => $item) {
      if ($item->target_type == $entity_type_id) {
        $entities[$delta] = $item->entity;
      }
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotal(): Price {
    $amount = 0;
    foreach ($this->get('entries') as $item) {
      if ($item->type == BookkeepingEntryItem::TYPE_DEBIT) {
        $amount += $item->amount;
      }
    }
    return new Price((string) $amount, $item->currency_code);
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
  public function preSave(EntityStorageInterface $storage) {
    // Throw an exception if the net of the entries is non zero.
    $violations = $this->get('entries')->validate();
    if ($violations->count()) {
      throw new \Exception($violations->get(0)->getMessage());
    }

    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('The description for the transaction.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
      ])
      ->setRequired(FALSE);

    $fields['generator'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Generator'))
      ->setDescription(new TranslatableMarkup('The event this was generated for.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
      ])
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(new TranslatableMarkup('The time that the entity was created.'))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => 1,
      ]);

    $fields['batch'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Batch'))
      ->setDescription(new TranslatableMarkup('The batch this transaction is exported in.'))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => 2,
      ]);

    $fields['entries'] = BaseFieldDefinition::create('bookkeeping_entry')
      ->setLabel(new TranslatableMarkup('Entries'))
      ->setDescription(new TranslatableMarkup('The entries for this transaction.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'bookkeeping_entry_table',
        'weight' => 50,
      ])
      ->setRequired(TRUE)
      ->addConstraint('BookkeepingEntries');

    $fields['related'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(new TranslatableMarkup('Related'))
      ->setDescription(new TranslatableMarkup('Entities relating to this transaction.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'dynamic_entity_reference_label',
        'weight' => 90,
      ]);

    return $fields;
  }

}
