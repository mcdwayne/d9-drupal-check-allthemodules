<?php

namespace Drupal\drd\Plugin\Block;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;

/**
 * Abstract class for DRD entity widget blocks.
 */
abstract class WidgetEntities extends WidgetBase {

  protected $accessView = FALSE;
  protected $accessCreate = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, $context = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $context);
    $this->accessView = \Drupal::currentUser()->hasPermission('drd.view published ' . $this->type() . ' entities');
    $this->accessCreate = \Drupal::currentUser()->hasPermission('drd.add ' . $this->type() . ' entities');
  }

  /**
   * Get the DRD entity type.
   *
   * @return string
   *   The DRD entity type.
   */
  abstract protected function type();

  /**
   * {@inheritdoc}
   */
  protected function content() {
    /* @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = \Drupal::database()->select('drd_' . $this->type(), 'h')
      ->condition('h.status', 1);
    $active = $query
      ->countQuery()
      ->execute()
      ->fetchField();
    $query = \Drupal::database()->select('drd_' . $this->type(), 'h')
      ->condition('h.status', 0);
    $inactive = $query
      ->countQuery()
      ->execute()
      ->fetchField();

    $args = ['@type' => $this->type()];
    if ($active == 0) {
      $message = 'You currently have no active @type in DRD!';
      $name = $this->t('Create your first @type', $args);
    }
    else {
      $name = $this->t('Create another @type', $args);
      $args['%countactive'] = $active;
      $message = '<p class="message">You have %countactive active @types.</p>';
      if ($this->accessView) {
        $args['@list'] = (new Url('entity.drd_' . $this->type() . '.collection'))->toString();
        $message .= '<p>Get all the details in your <a href="@list">@type list</a>.';
      }
    }
    if ($inactive > 0) {
      $args['%countinactive'] = $inactive;
      $message .= '<p>In addition, there are %countinactive inactive @types available as well.</p>';
    }

    if ($this->accessCreate && $this->type() != 'domain') {
      $message .= '<p class="action"><span class="button">' .
        \Drupal::linkGenerator()->generate($name, new Url('entity.drd_' . $this->type() . '.add_form')) . '</span></p>';
    }

    return new FormattableMarkup($message, $args);
  }

}
