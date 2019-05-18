<?php
/**
 * Created by:
 * User: jfeltkamp
 * Date: 09.03.16
 * Time: 22:24
 */

namespace Drupal\cronpub\Plugin\Cronpub\Action;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\cronpub\Plugin\Cronpub\CronpubActionInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;

/**
 * Plugin implementation of the 'publish' actions.
 *
 * @CronpubAction(
 *   id = "publishing",
 *   label = @Translation("Publish/Unpublish"),
 *   description = @Translation("Publish and unpublish the parent entity of this field."),
 *   start = {
 *     "label" = @Translation("Publish"),
 *     "description" = @Translation("Date and time at which the content will be published automatically.")
 *   },
 *   end = {
 *     "label" = @Translation("Unpublish"),
 *     "description" = @Translation("Date and time at which the content will be unpublished automatically.")
 *   },
 *   permission = "administer nodes",
 * )
 */
class CronpubPublish implements CronpubActionInterface {

  /**
   * {@inheritdoc}
   */
  public function startAction(ContentEntityBase $content_entity) {
    return $this->setStatus($content_entity, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function endAction(ContentEntityBase $content_entity) {
    return $this->setStatus($content_entity, FALSE);
  }

  /**
   * Set publishing status of content entity.
   *
   * @param \Drupal\core\Entity\ContentEntityBase $content_entity
   *   The Entity to set status.
   * @param int $new_status
   *   The value to set.
   *
   * @return bool
   *   Success of action.
   */
  private function setStatus(ContentEntityBase $content_entity, $new_status) {
    // Check current status.
    if (method_exists($content_entity, 'isPublished')) {
      // Nodes and may be other entity types too have their own check methods;
      /* @var NodeInterface $content_entity */
      $old_status = $content_entity->isPublished();
    }
    elseif ($content_entity->hasField('status')) {
      // Check status field of entity (paragraph, terms, custom entities).
      $status = $content_entity->get('status')->getValue();
      $old_status = (is_array($status)) ? (bool) $status[0]['value'] : FALSE;
    }
    else {
      // Neither a status field nor a isPublished method found to en-/disable entity.
      $message = new TranslatableMarkup(
        "Tried to change publishing status of entity %entity, but found no status field.",
        [
          '%entity' => $content_entity->getEntityTypeId() . '/' . $content_entity->id()
        ]
      );
      \Drupal::logger('Cronpub')->error($message);
      return FALSE;
    }

    // Set new status.
    $time = (int) time();
    if ($new_status !== $old_status) {
      try {
        if (method_exists($content_entity, 'setPublished')) {
          $content_entity->setPublished($new_status);
        }
        else {
          $content_entity->set('status', $new_status);
        }
        // Set status.
        $time = $content_entity->save();
        $message = new TranslatableMarkup('%action the entity %link on %date.', [
          '%action' => ($new_status) ? t('Published') : t('Unpublished'),
          '%link' => $content_entity->getEntityTypeId() . '/' . $content_entity->id(),
          '%date' => \Drupal::service('date.formatter')->format($time),
        ]);
      } catch (AccessDeniedException $e) {
        $message = new TranslatableMarkup(
          "Error occurred by setting publishing status of entity %entity.", [
            '%entity' => $content_entity->getEntityTypeId(),
          ]
        );
        \Drupal::logger('Cronpub')->error($e->getMessage());
      }
    }
    else {
      $changed = $content_entity->get('changed')->getValue();
      $changed = (count($changed)) ? $changed : $content_entity->get('created')->getValue();
      $changed = (int) $changed[0]['value'];
      $message = new TranslatableMarkup("Cronpub couldn't %action the entity %link on %date. Because it was already %actioned at least since %changed", [
        '%action' => ($new_status) ? t('publish') : t('unpublish'),
        '%link' => $content_entity->getEntityTypeId() . '/' . $content_entity->id(),
        '%date' => \Drupal::service('date.formatter')->format($time),
        '%changed' => \Drupal::service('date.formatter')->format($changed),
      ]);
    }
    \Drupal::logger('Cronpub')->notice($message);
    return ($time >= 0);
  }

}
