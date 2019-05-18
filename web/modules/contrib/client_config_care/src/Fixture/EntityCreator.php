<?php

namespace Drupal\client_config_care\Fixture;

use Drupal\client_config_care\Entity\ConfigBlockerEntity;
use Drupal\Component\Utility\Random;
use Drupal\Core\Language\LanguageInterface;


class EntityCreator {

	private const NUM_ENTITY_ITEMS = 5;

	public function createEntities(): void {
		$random = new Random();

		$userOperation = ['save', 'delete'];

		for ($i = 1; $i <= self::NUM_ENTITY_ITEMS; ++$i) {
      ConfigBlockerEntity::create([
        'name'           => $random->name(),
        'langcode'       => LanguageInterface::LANGCODE_DEFAULT,
        'user_operation' => array_rand($userOperation),
      ])->save();
		}
	}

}
