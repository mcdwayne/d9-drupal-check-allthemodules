<?php

namespace Drupal\entity_language_fallback;

use Drupal\Core\Entity\ContentEntityInterface;

interface FallbackControllerInterface  {

  /**
   * @param string $lang_code
   *
   * @return []
   *   Array of language codes for the fallback chain, most preferred languages first.
   */
  public function getFallbackChain($lang_code);

  /**
   * @param string $lang_code
   *   Language code
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity
   * @return mixed
   *   fallback entity translation, or FALSE if nothing was found.
   */
  public function getTranslation($lang_code, ContentEntityInterface $entity);

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return []
   *   Array of entity translations, including fallback content.
   *
   */
  public function getTranslations(ContentEntityInterface $entity);

}
