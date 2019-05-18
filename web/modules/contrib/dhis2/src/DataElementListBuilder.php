<?php

namespace Drupal\dhis;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Data element entities.
 *
 * @ingroup dhis
 */
class DataElementListBuilder extends EntityListBuilder
{

    use LinkGeneratorTrait;

    /**
     * {@inheritdoc}
     */
    public function buildHeader()
    {
        $header['id'] = $this->t('Data element ID');
        $header['name'] = $this->t('Display Name');
        $header['deuid'] = $this->t('DE uid');
        $header['status'] = $this->t('Synchronizable');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity)
    {
        /* @var $entity \Drupal\dhis\Entity\DataElement */
        $row['id'] = $entity->id();
        $row['name'] = $this->l(
            $entity->label(),
            new Url(
                'entity.data_element.edit_form', array(
                    'data_element' => $entity->id(),
                )
            )
        );
        $row['deuid'] = $entity->getDataElementUid();
        $row['status'] = $entity->isPublished();
        return $row + parent::buildRow($entity);
    }

    public function render()
    {
        $form = \Drupal::formBuilder()->getForm('Drupal\dhis\Form\DataElementFilterForm');
        $build['form'] = $form;

        $build += parent::render();
        return $build;
    }

    protected function getEntityIds()
    {
        $params = \Drupal::request()->query->all();
        $form_id = $params['form_id'];

        if ($form_id && $form_id === 'DataElementFilterForm') {
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
