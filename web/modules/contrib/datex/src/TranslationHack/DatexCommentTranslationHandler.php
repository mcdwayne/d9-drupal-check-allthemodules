<?php

namespace Drupal\datex\TranslationHack;

use Drupal\comment\CommentTranslationHandler;

class DatexCommentTranslationHandler extends CommentTranslationHandler {

  use DatexTranslationHack;

}
