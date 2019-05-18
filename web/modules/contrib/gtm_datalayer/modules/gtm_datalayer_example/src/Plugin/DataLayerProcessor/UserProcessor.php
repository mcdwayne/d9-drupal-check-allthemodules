<?php

namespace Drupal\gtm_datalayer_example\Plugin\DataLayerProcessor;

use Drupal\gtm_datalayer\Plugin\DataLayerProcessorEntityBase;
use Drupal\user\UserInterface;

/**
 * Provides a UNICEF dataLayer processor for user entities.
 *
 * @DataLayerProcessor(
 *   id = "unicef_datalayer_user",
 *   label = @Translation("User"),
 *   description = @Translation("Provides a processor for user entities."),
 *   group = @Translation("Global"),
 *   category = @Translation("Entity"),
 * )
 */
class UserProcessor extends DataLayerProcessorEntityBase {

  /**
   * {@inheritdoc}
   */
  public function render() {
    parent::render();

    if (!$this->isRequestException() && $this->getEntity() instanceof UserInterface) {
      $this->addTag(['entity_roles'], $this->getEntity()->getRoles());
      $this->addTag(['entity_created'], $this->dateFormatter->format($this->getEntity()->getCreatedTime(), 'gtm_datalayer'));
    }

    return $this->getTags();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityFromContext() {
    $this->setEntity($this->currentRouteMatch->getParameter('user'));
  }

}
