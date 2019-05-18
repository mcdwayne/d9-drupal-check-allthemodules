<?php

namespace Drupal\drd_pi;

use Drupal\drd\Entity\BaseInterface;
use Drupal\key_value_field\Plugin\Field\FieldType\KeyValueItem;

/**
 * Provides abstract class for platform based entities.
 */
abstract class DrdPiEntity implements DrdPiEntityInterface {

  /**
   * Account to which this entity is attached.
   *
   * @var DrdPiAccountInterface
   */
  protected $account;

  /**
   * Label of this entity.
   *
   * @var string
   */
  protected $label;

  /**
   * ID of this entity.
   *
   * @var string
   */
  protected $id;

  /**
   * DrdEntity which matches this DrdPiEntity.
   *
   * @var \Drupal\drd\Entity\BaseInterface
   */
  protected $entity;

  /**
   * Header values for the DRD entity.
   *
   * @var array
   */
  protected $header = [];

  /**
   * Construct a DrdPiEntity object.
   *
   * @param DrdPiAccountInterface $account
   *   Account to which this entity is attached.
   * @param string $label
   *   Label of this entity.
   * @param string $id
   *   ID of this entity.
   */
  public function __construct(DrdPiAccountInterface $account, $label, $id) {
    $this->account = $account;
    $this->label = $label;
    $this->id = $id;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function setDrdEntity(BaseInterface $entity) {
    $this->entity = $entity;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrdEntity() {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function hasDrdEntity() {
    return !empty($this->entity);
  }

  /**
   * {@inheritdoc}
   */
  public function update() {
    if ($this->hasDrdEntity()) {
      $changed = FALSE;
      $foundAuth = FALSE;
      $hasAuth = isset($this->header['Authorization']);
      $headerItems = [];
      /* @var \Drupal\key_value_field\Plugin\Field\FieldType\KeyValueItem $item */
      foreach ($this->entity->get('header') as $item) {
        $value = $item->getValue();
        if ($value['key'] == 'Authorization' && !$hasAuth) {
          // Ignore this item.
          $changed = TRUE;
        }
        else {
          if ($value['key'] == 'Authorization') {
            $foundAuth = TRUE;
            if ($value['value'] != $this->header['Authorization']) {
              $value['value'] = $this->header['Authorization'];
              $changed = TRUE;
            }
          }
          $headerItems[] = $value;
        }
      }
      if ($hasAuth && !$foundAuth) {
        $headerItems[] = [
          'key' => 'Authorization',
          'value' => $this->header['Authorization'],
        ];
        $changed = TRUE;
      }
      if ($changed) {
        $this->entity->set('header', $headerItems);
        $this->entity->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setHeader($key, $value) {
    $this->header[$key] = $value;
  }

}
