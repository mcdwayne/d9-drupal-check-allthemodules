<?php

namespace Drupal\drd\Plugin\views\field;

use Drupal\Core\Render\Markup;
use Drupal\drd\Entity\ReleaseInterface;
use Drupal\drd\Entity\UpdateStatusInterface;
use Drupal\drd\UpdateProcessor;
use Drupal\views\Plugin\views\field\Standard;
use Drupal\views\ResultRow;

/**
 * A handler to display the project update status.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("drd_update_status")
 */
class UpdateStatus extends Standard {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->realField = 'updatestatus';
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Render the update status for a release.
   *
   * @param \Drupal\drd\Entity\UpdateStatusInterface $entity
   *   The project or release entity.
   * @param string $status
   *   The status of the release.
   *
   * @return string
   *   Rendered string of the status.
   */
  private function statusToHtml(UpdateStatusInterface $entity, $status) {
    $statuses = UpdateProcessor::getStatuses();
    $type = $statuses['status'][$status]['type'];
    $title = $statuses['type'][$type]['title'];

    if ($entity instanceof ReleaseInterface) {
      $class = 'drd-update-info';
      $major = $entity->getMajor();
      $recommended = $major->getRecommendedRelease();
      $output = '<span class="drd-icon">&nbsp;</span><div class="label">' . $title . '</div>';
      if (!empty($recommended) && $recommended->id() != $entity->id()) {
        $link_recommended = \Drupal::linkGenerator()
          ->generate($recommended->getVersion(), $recommended->getReleaseLink());
        $link_download = \Drupal::linkGenerator()
          ->generate($this->t('Download'), $recommended->getDownloadLink());
        $output .=
          '<div class="release">' . $link_recommended . '</div>' .
          '<div class="download">' . $link_download . '</div>';
      }
    }
    else {
      $class = 'drd-update-status';
      $output = substr($title, 0, 3);
    }

    return '<div class="' . $class . ' ' . $type . '">' . $output . '</div>';
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $status = $this->getValue($values);
    if (empty($status)) {
      return '';
    }
    /* @var UpdateStatusInterface $entity */
    $entity = $values->_entity;

    $output = '';
    foreach (explode(',', $status) as $value) {
      $output .= $this->statusToHtml($entity, $value);
    }

    return Markup::create($output);
  }

}
