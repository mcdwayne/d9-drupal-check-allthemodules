<?php

namespace Drupal\cloudwords;

interface CloudwordsSourceControllerInterface {
  public function __construct($type);

  public function typeLabel();

  public function textGroup();

  public function textGroupLabel();

  public function targetLabel(\Drupal\cloudwords\Entity\CloudwordsTranslatable $translatable);

  public function uri(\Drupal\cloudwords\Entity\CloudwordsTranslatable $translatable);

  public function data(\Drupal\cloudwords\Entity\CloudwordsTranslatable $translatable);

  public function save(\Drupal\cloudwords\Entity\CloudwordsTranslatable $translatable);
}
