<?php

namespace Drupal\migrate_qa\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\TypedData\TranslationStatusInterface;

interface TrackerInterface extends EntityChangedInterface, RevisionLogInterface, ContentEntityInterface, TranslationStatusInterface {

}
