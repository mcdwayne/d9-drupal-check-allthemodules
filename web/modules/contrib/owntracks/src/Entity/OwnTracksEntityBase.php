<?php

namespace Drupal\owntracks\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Class OwnTracksEntityBase.
 */
abstract class OwnTracksEntityBase extends ContentEntityBase implements OwnTracksEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setDefaultValueCallback('Drupal\owntracks\Entity\OwnTracksEntityBase::getCurrentUserId');

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['lat'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Latitude'))
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'settings' => ['scale' => 8],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('suffix', '°')
      ->setSetting('precision', 10)
      ->setSetting('scale', 8)
      ->setRequired(TRUE)
      ->addPropertyConstraints('value', [
        'Range' => [
          'min' => -90,
          'max' => 90,
        ],
      ]);

    $fields['lon'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Longitude'))
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'settings' => ['scale' => 8],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('suffix', '°')
      ->setSetting('precision', 11)
      ->setSetting('scale', 8)
      ->setRequired(TRUE)
      ->addPropertyConstraints('value', [
        'Range' => [
          'min' => -180,
          'max' => 180,
        ],
      ]);

    $fields['tst'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Timestamp'))
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocation() {
    return [
      round(floatval($this->get('lat')->value), 8),
      round(floatval($this->get('lon')->value), 8),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->getEntityKey('uid');
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

}
