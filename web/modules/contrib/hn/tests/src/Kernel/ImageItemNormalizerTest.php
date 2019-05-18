<?php

namespace Drupal\Tests\hn\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\image\Entity\ImageStyle;
use Drupal\Tests\image\Kernel\ImageItemTest;

/**
 * Tests for the ImageItemNormalizer.
 *
 * @group hn_image
 */
class ImageItemNormalizerTest extends ImageItemTest {

  public static $modules = ['hn_image', 'serialization'];

  /**
   * Test a normal image item, without and with image styles.
   */
  public function testImageItem() {
    // Create a test entity with the image field set.
    $entity = EntityTest::create();
    $entity->set('image_test', [
      'target_id' => $this->image->id(),
      'alt' => $this->randomMachineName(),
      'title' => $this->randomMachineName(),
    ]);
    $entity->set('name', $this->randomMachineName());
    $entity->save();

    $entity = EntityTest::load($entity->id());
    /** @var \Drupal\file\Plugin\Field\FieldType\FileFieldItemList $image_item_list */
    $image_item_list = $entity->get('image_test');
    /** @var \Drupal\image\Plugin\Field\FieldType\ImageItem $image_item */
    $image_item = $image_item_list->get(0);

    /** @var \Drupal\hn_image\Normalizer\ImageItemNormalizer $normalizer */
    $normalizer = $this->container->get('serializer.normalizer.hn_image.image_item');
    $this->assertFalse($normalizer->supportsNormalization($image_item_list));
    $this->assertFalse($normalizer->supportsDenormalization($image_item_list->getValue(), $image_item_list));
    $this->assertFalse($normalizer->supportsDenormalization($image_item->getValue(), $image_item));
    $this->assertTrue($normalizer->supportsNormalization($image_item));

    $normalizer->setSerializer($this->container->get('serializer'));
    $normalized = $normalizer->normalize($image_item);
    $this->assertEquals((string) $this->image->url(), $normalized['url']);
    $this->assertEmpty($normalized['image_styles']);

    // Create two image styles.
    // @see \Drupal\Tests\image\Kernel\ImageStyleIntegrationTest

    /** @var \Drupal\image\ImageStyleInterface $style */
    $style = ImageStyle::create(['name' => 'unchanged_style']);
    $style->save();

    /** @var \Drupal\image\ImageStyleInterface $crop_style */
    $resize_width = rand(1, 1000);
    $resize_height = rand(1, 1000);

    $effect_manager = $this->container->get('plugin.manager.image.effect');
    $resize_effect = $effect_manager->createInstance('image_crop', [
      'data' => [
        'width' => $resize_width,
        'height' => $resize_height,
      ],
    ]);

    $crop_style = ImageStyle::create(['name' => 'crop_style']);
    $crop_style->getEffects()->set($this->randomMachineName(), $resize_effect);
    $crop_style->save();

    $normalized = $normalizer->normalize($image_item);
    $this->assertEquals((string) $this->image->url(), $normalized['url']);
    $this->assertEquals([
      'unchanged_style' => [
        'url' => $style->buildUrl($this->image->getFileUri()),
        'width' => '88',
        'height' => '100',
      ],
      'crop_style' => [
        'url' => $crop_style->buildUrl($this->image->getFileUri()),
        'width' => $resize_width,
        'height' => $resize_height,
      ],
    ], $normalized['image_styles']);

  }

  /**
   * Don't execute, because otherwise it's parent runs.
   */
  public function testImageItemMalformed() {
    $this->markTestSkipped();
  }

}
