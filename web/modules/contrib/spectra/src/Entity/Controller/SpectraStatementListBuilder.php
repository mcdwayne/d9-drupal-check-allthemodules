<?php

/**
 * @file
 * Contains \Drupal\spectra\Entity\Controller\SpectraStatementListBuilder.
 */

namespace Drupal\spectra\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for spectra_statement entity.
 *
 * @ingroup spectra
 */
class SpectraStatementListBuilder extends EntityListBuilder {

    /**
     * The url generator.
     *
     * @var \Drupal\Core\Routing\UrlGeneratorInterface
     */
    protected $urlGenerator;


    /**
     * {@inheritdoc}
     */
    public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
        return new static(
            $entity_type,
            $container->get('entity.manager')->getStorage($entity_type->id()),
            $container->get('url_generator')
        );
    }

    /**
     * Constructs a new SpectraStatementListBuilder object.
     *
     * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
     *   The entity type spectra_statement.
     * @param \Drupal\Core\Entity\EntityStorageInterface $storage
     *   The entity storage class.
     * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
     *   The url generator.
     */
    public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, UrlGeneratorInterface $url_generator) {
        parent::__construct($entity_type, $storage);
        $this->urlGenerator = $url_generator;
    }


    /**
     * {@inheritdoc}
     *
     * We override ::render() so that we can add our own content above the table.
     * parent::render() is where EntityListBuilder creates the table using our
     * buildHeader() and buildRow() implementations.
     */
    public function render() {
        $build['description'] = array(
            '#markup' => $this->t('You can manage the fields on the <a href="@adminlink">Spectra Statement Settings admin page</a>.', array(
                '@adminlink' => $this->urlGenerator->generateFromRoute('spectra.spectra_statement_settings'),
            )),
        );
        $build['table'] = parent::render();
        return $build;
    }

    /**
     * {@inheritdoc}
     *
     * Building the header and content lines for the spectra_statement list.
     *
     * Calling the parent::buildHeader() adds a column for the possible actions
     * and inserts the 'edit' and 'delete' links as defined for the entity type.
     */
    public function buildHeader() {
        $header['id'] = $this->t('ItemID');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity) {
        /* @var $entity \Drupal\spectra\Entity\SpectraStatement */
        $row['id'] = $entity->id();
        return $row + parent::buildRow($entity);
    }

}
