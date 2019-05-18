<?php

namespace Drupal\datex\TranslationHack;

use Drupal\content_translation\ContentTranslationHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

class DatexContentTranslationHandler extends ContentTranslationHandler {

  use DatexTranslationHack;

}
