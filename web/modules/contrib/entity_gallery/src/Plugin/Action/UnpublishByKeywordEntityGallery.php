<?php

namespace Drupal\entity_gallery\Plugin\Action;

use Drupal\Component\Utility\Tags;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Unpublishes an entity gallery containing certain keywords.
 *
 * @Action(
 *   id = "entity_gallery_unpublish_by_keyword_action",
 *   label = @Translation("Unpublish content containing keyword(s)"),
 *   type = "entity_gallery"
 * )
 */
class UnpublishByKeywordEntityGallery extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity_gallery = NULL) {
    foreach ($this->configuration['keywords'] as $keyword) {
      $elements = entity_gallery_view(clone $entity_gallery);
      if (strpos(drupal_render($elements), $keyword) !== FALSE || strpos($entity_gallery->label(), $keyword) !== FALSE) {
        $entity_gallery->setPublished(FALSE);
        $entity_gallery->save();
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'keywords' => array(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['keywords'] = array(
      '#title' => t('Keywords'),
      '#type' => 'textarea',
      '#description' => t('The content will be unpublished if it contains any of the phrases above. Use a case-sensitive, comma-separated list of phrases. Example: funny, bungee jumping, "Company, Inc."'),
      '#default_value' => Tags::implode($this->configuration['keywords']),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['keywords'] = Tags::explode($form_state->getValue('keywords'));
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\entity_gallery\EntityGalleryInterface $object */
    $access = $object->access('update', $account, TRUE)
      ->andIf($object->status->access('edit', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
