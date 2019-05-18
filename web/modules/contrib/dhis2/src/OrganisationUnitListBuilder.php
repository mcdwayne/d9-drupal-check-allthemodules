<?php

namespace Drupal\dhis;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Organisation unit entities.
 *
 * @ingroup dhis
 */
class OrganisationUnitListBuilder extends EntityListBuilder
{

    use LinkGeneratorTrait;

    /**
     * {@inheritdoc}
     */
    public function buildHeader()
    {
        $header['id'] = $this->t('Organisation unit ID');
        $header['name'] = $this->t('Display Name');
        $header['orgunituid'] = $this->t('Org unit uid');
        $header['status'] = $this->t('Synchronizable');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity)
    {
        /* @var $entity \Drupal\dhis\Entity\OrganisationUnit */
        $row['id'] = $entity->id();
        $row['name'] = $this->l(
            $entity->label(),
            new Url(
                'entity.organisation_unit.edit_form', array(
                    'organisation_unit' => $entity->id(),
                )
            )
        );
        $row['orgunituid'] = $entity->getOrgunitUid();
        $row['status'] = $entity->isPublished();
        return $row + parent::buildRow($entity);
    }

    public function render()
    {
        $form = \Drupal::formBuilder()->getForm('Drupal\dhis\Form\OrganisationUnitFilterForm');
        $build['form'] = $form;

        $build += parent::render();
        return $build;
    }

    protected function getEntityIds()
    {
        $params = \Drupal::request()->query->all();
        $form_id = $params['form_id'];

        if ($form_id && $form_id === 'OrganisationUnitFilterForm') {
            $query = \Drupal::entityQuery($this->entityTypeId);
            $query->condition('name', $params['name'], 'CONTAINS');
            if ($this->limit) {
                $query->pager($this->limit);
            }
            $result = $query->execute();
        } else {
            $result = parent::getEntityIds();
        }
        return $result;
    }
}
