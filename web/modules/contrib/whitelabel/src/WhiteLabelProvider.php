<?php

namespace Drupal\whitelabel;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Default implementation of the white label provider.
 */
class WhiteLabelProvider implements WhiteLabelProviderInterface {

  /**
   * The order storage.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The session.
   *
   * @var \Drupal\whitelabel\WhiteLabelSessionInterface
   */
  protected $whiteLabelSession;

  /**
   * The stored white label entity, for fast serve.
   *
   * @var \Drupal\whitelabel\Entity\WhiteLabel
   */
  protected $whiteLabel = NULL;

  /**
   * Constructs a new WhiteLabelProvider object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\whitelabel\WhiteLabelSessionInterface $whitelabel_session
   *   The white label session.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, WhiteLabelSessionInterface $whitelabel_session) {
    $this->entityTypeManager = $entity_type_manager;
    $this->whiteLabelSession = $whitelabel_session;
  }

  /**
   * {@inheritdoc}
   */
  public function getWhiteLabel() {
    if (empty($this->whiteLabel)) {
      $white_label_id = $this->getWhiteLabelId();

      if ($white_label_id) {
        // During enabling the entity type might not yet have been defined.
        try {
          $this->whiteLabel = $this->entityTypeManager->getStorage('whitelabel')
            ->load($white_label_id);
        }
        catch (\Exception $e) {
          return NULL;
        }
      }
    }

    if (!empty($this->whiteLabel) && $this->whiteLabel->access('serve', $this->whiteLabel->getOwner()) && $this->whiteLabel->access('view')) {
      return $this->whiteLabel;
    }
    else {
      return NULL;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getWhiteLabelId() {
    return $this->whiteLabelSession->getWhiteLabelId();
  }

  /**
   * {@inheritdoc}
   */
  public function setWhiteLabel(WhiteLabelInterface $white_label) {
    $this->whiteLabel = $white_label;
    $this->whiteLabelSession->setWhiteLabelId($white_label->id());
  }

  /**
   * {@inheritdoc}
   */
  public function resetWhiteLabel() {
    $this->whiteLabel = NULL;
    $this->whiteLabelSession->setWhiteLabelId(NULL);
  }

}
