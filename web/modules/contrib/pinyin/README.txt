This module will generate the Pinyin short code(拼音简码) based on the entity label field.

Code example:

    $fields['pinyin'] = BaseFieldDefinition::create('pinyin_shortcode')
      ->setLabel('拼音码')
      ->setSetting('max_length', 16);

