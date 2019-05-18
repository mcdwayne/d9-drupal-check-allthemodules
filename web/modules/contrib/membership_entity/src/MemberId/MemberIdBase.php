<?php
declare(strict_types=1);

namespace Drupal\membership_entity\MemberId;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base definition of a MemberId Plugin.
 *
 * @see \Drupal\membership_entity\MemberId\MemberIdManager
 */
abstract class MemberIdBase implements MemberIdInterface {
  use StringTranslationTrait;

  /**
   * Plugin options.
   *
   * @var array
   */
  protected $options;

  /**
   * The entity object for the MemberID.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Constructs a MemberId plugin.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *  The entity object for the MemberID.
   */
   public function __construct(EntityInterface $entity) {
     $this->entity = $entity;
     $this->options = [];
   }

  /**
   * @inheritDoc
   */
  public function optionsForm(array $form, FormStateInterface $form_state): array {
    return $form;
  }

}
