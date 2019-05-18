<?php

namespace Drupal\migrate_gathercontent\Form;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityDeleteForm;

// TODO: When you delete a mapping you also need to delete any migration tables.
/**
 * Provides a deletion confirmation form for taxonomy term.
 *
 * @internal
 */
class MappingDeleteForm extends EntityDeleteForm {

    /**
     * {@inheritdoc}
     */
    public function getCancelUrl() {
        return new Url('migrate_gathercontent.group.collection');
    }

    /**
     * {@inheritdoc}
     */
    protected function getRedirectUrl() {
        return $this->getCancelUrl();
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription() {
        return $this->t('Delete this mapping? This action cannot be undone.');
    }

    /**
     * {@inheritdoc}
     */
    protected function getDeletionMessage() {
        return $this->t('Deleted mapping %name.', ['%name' => $this->entity->label()]);
    }
}
