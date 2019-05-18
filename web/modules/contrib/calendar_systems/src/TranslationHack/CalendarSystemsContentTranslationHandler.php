<?php

namespace Drupal\calendar_systems\TranslationHack;

use Drupal\content_translation\ContentTranslationHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

class CalendarSystemsContentTranslationHandler extends ContentTranslationHandler {

  use CalendarSystemsTranslationHack;

}
