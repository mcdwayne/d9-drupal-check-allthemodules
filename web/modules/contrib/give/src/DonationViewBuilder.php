<?php

namespace Drupal\give;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\give\ProblemLog;

/**
 * Render controller for give donations.
 */
class DonationViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    parent::buildComponents($build, $entities, $displays, $view_mode);

    /** @var \Drupal\give\Entity\Donation $donation */
    $donation = $entities[0];

    // Some label adjustments.
    $build[0]['mail']['#title'] = $this->t('E-mail');
    $build[0]['name']['#title'] = $this->t('Name');
    $build[0]['label']['#label_display'] = 'hidden';
    $build[0]['changed']['#label_display'] = 'inline';
    $build[0]['changed'][0]['#markup'] = date('l, F, Y - H:i', $donation->getUpdatedTime());
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = parent::view($entity, $view_mode, $langcode);

    // We only add the problem log to view modes shown to administrators.
    if (!in_array($view_mode, ['full', 'notice'])) {
      return $build;
    }

    $result = ProblemLog::load($entity->uuid());

    if (!$result) {
      $build['extra'] = [
        '#type' => 'markup',
        '#markup' => '<p>No problems recorded.</p>',
        '#weight' => 199,
      ];
      return $build;
    }

    $result = ProblemLog::load($entity->uuid());
    $rows = [];
    foreach ($result as $row) {
      $rows[] = [
        $row->type,
        $row->detail,
        $row->user_agent,
        \Drupal::service('date.formatter')->format($row->timestamp, 'short'),
      ];
    }
    $build['errors'] = [
      '#type' => 'fieldset',
      '#title' => 'Problem log',
      '#weight' => 20,
    ];
    $build['errors']['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Problem type'),
        $this->t('Detail'),
        $this->t('Browser\'s user agent'),
        $this->t('Time'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No problems recorded.'),
      '#weight' => 200,
    ];

    return $build;
  }

}
