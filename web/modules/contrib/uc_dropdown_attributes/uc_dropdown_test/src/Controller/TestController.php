<?php

namespace Drupal\uc_dropdown_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Additional test of Dropdown Attributes UI.
 */
class TestController extends ControllerBase {

  protected $vals = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

  /**
   * {@inheritdoc}
   */
  public function product($user, $type) {
    $node = $this->createProduct();

    switch ($type) {
      default:
      case 'select':
        $display = 1;
        break;

      case 'radios':
        $display = 2;
        break;

      case 'checkboxes':
        $display = 3;
        break;

    }
    $data = array(
      'display' => $display,
      'name' => 'parent',
      'label' => 'Parent',
      'required' => TRUE,
    );
    $parent_attribute = $this->createAttribute($data);
    $data = array(
      'display' => $display,
      'name' => 'child',
      'label' => 'Child',
    );
    $child_attribute = $this->createAttribute($data);
    if ($type == 'textfield') {
      // Textfields are only supported as children.
      $display = 0;
    }
    $data = array(
      'display' => $display,
      'name' => 'grandchild',
      'label' => 'Grandchild',
    );
    $grandchild_attribute = $this->createAttribute($data);

    // Add some options.
    $parent_options = array();
    $options1 = array();
    for ($i = 0; $i < 3; $i++) {
      $option = $this->createAttributeOption(array(
        'aid' => $parent_attribute->aid,
      ));
      $parent_options[$option->oid] = $option;
      if ($i == 0) {
        $show_child = $option->oid;
      }
      if ($i < 2) {
        $options1[$option->oid] = $option->oid;
      }
      if ($i == 0) {
        $oid = $option->oid;
      }
    }
    $child_options = array();
    $options2 = array();
    for ($i = 0; $i < 3; $i++) {
      $option = $this->createAttributeOption(array(
        'aid' => $child_attribute->aid,
      ));
      $child_options[$option->oid] = $option;
      if ($i < 2) {
        $options2[$option->oid] = $option->oid;
      }
    }
    if ($type != 'textfield') {
      $grandchild_options = array();
      for ($i = 0; $i < 3; $i++) {
        $option = $this->createAttributeOption(array(
          'aid' => $grandchild_attribute->aid,
        ));
        $grandchild_options[$option->oid] = $option;
      }
    }

    // Attach the attributes to a product.
    uc_attribute_subject_save($parent_attribute, 'product', $node->id());
    uc_attribute_subject_save($child_attribute, 'product', $node->id());
    uc_attribute_subject_save($grandchild_attribute, 'product', $node->id());

    foreach ($parent_options as $parent_option) {
      $this->attachProductOption($parent_option->oid, $node->id());
    }
    foreach ($child_options as $child_option) {
      $this->attachProductOption($child_option->oid, $node->id());
    }
    foreach ($grandchild_options as $grandchild_option) {
      $this->attachProductOption($grandchild_option->oid, $node->id());
    }

    // Create dependent attribute.
    uc_dropdown_attributes_product_create_dependency(
      $node->id(),
      $child_attribute->aid,
      $parent_attribute->aid,
      $options1,
      1
    );
    uc_dropdown_attributes_product_create_dependency(
      $node->id(),
      $grandchild_attribute->aid,
      $child_attribute->aid,
      $options2,
      1
    );

    $response = array();
    $response['status'] = TRUE;
    $response['user'] = $user;
    $response['nid'] = $node->id();
    return new JsonResponse($response);
  }

  /**
   * {@inheritdoc}
   */
  public function productClass($user, $type) {
    $class = $this->randGen(12);

    $values = array('type' => strtolower($class));
    $product_class = new NodeType($values, 'node_type');
    $product_class->set('entityTypeId', 'node_type');
    $product_class->set('name', $class);
    $product_class->setThirdPartySetting('uc_product', 'product', TRUE);
    $product_class->original = new NodeType($values, 'node_type');
    $product_class->save();

    $node = $this->createProduct(array('type' => $product_class->id()));

    switch ($type) {
      default:
      case 'select':
        $display = 1;
        break;

      case 'radios':
        $display = 2;
        break;

      case 'checkboxes':
        $display = 3;
        break;

    }
    $data = array(
      'display' => $display,
      'name' => 'parent',
      'label' => 'Parent',
      'required' => TRUE,
    );
    $parent_attribute = $this->createAttribute($data);
    $data = array(
      'display' => $display,
      'name' => 'child',
      'label' => 'Child',
    );
    $child_attribute = $this->createAttribute($data);
    if ($type == 'textfield') {
      // Textfields are only supported as children.
      $display = 0;
    }
    $data = array(
      'display' => $display,
      'name' => 'grandchild',
      'label' => 'Grandchild',
    );
    $grandchild_attribute = $this->createAttribute($data);

    // Add some options.
    $parent_options = array();
    $options1 = array();
    for ($i = 0; $i < 3; $i++) {
      $option = $this->createAttributeOption(array(
        'aid' => $parent_attribute->aid,
      ));
      $parent_options[$option->oid] = $option;
      if ($i == 0) {
        $show_child = $option->oid;
      }
      if ($i < 2) {
        $options1[$option->oid] = $option->oid;
      }
      if ($i == 0) {
        $oid = $option->oid;
      }
    }
    $child_options = array();
    $options2 = array();
    for ($i = 0; $i < 3; $i++) {
      $option = $this->createAttributeOption(array(
        'aid' => $child_attribute->aid,
      ));
      $child_options[$option->oid] = $option;
      if ($i < 2) {
        $options2[$option->oid] = $option->oid;
      }
    }
    if ($type != 'textfield') {
      $grandchild_options = array();
      for ($i = 0; $i < 3; $i++) {
        $option = $this->createAttributeOption(array(
          'aid' => $grandchild_attribute->aid,
        ));
        $grandchild_options[$option->oid] = $option;
      }
    }

    // Attach the attributes to a product.
    uc_attribute_subject_save($parent_attribute, 'class', $product_class->id());
    uc_attribute_subject_save($child_attribute, 'class', $product_class->id());
    uc_attribute_subject_save($grandchild_attribute, 'class', $product_class->id());

    foreach ($parent_options as $parent_option) {
      $this->attachClassOption($parent_option->oid, $product_class->id());
    }
    foreach ($child_options as $child_option) {
      $this->attachClassOption($child_option->oid, $product_class->id());
    }
    foreach ($grandchild_options as $grandchild_option) {
      $this->attachClassOption($grandchild_option->oid, $product_class->id());
    }

    // Create dependent attribute.
    uc_dropdown_attributes_class_create_dependency(
      $product_class->id(),
      $child_attribute->aid,
      $parent_attribute->aid,
      $options1,
      1
    );
    uc_dropdown_attributes_class_create_dependency(
      $product_class->id(),
      $grandchild_attribute->aid,
      $child_attribute->aid,
      $options2,
      1
    );

    $response = array();
    $response['status'] = TRUE;
    $response['user'] = $user;
    $response['nid'] = $node->id();
    return new JsonResponse($response);
  }

  /**
   * {@inheritdoc}
   */
  public function productKit($user, $type) {
    $node1 = $this->createProduct();

    // Add a product class.
    $class = $this->randGen(12);
    $values = array('type' => strtolower($class));
    $product_class = new NodeType($values, 'node_type');
    $product_class->set('entityTypeId', 'node_type');
    $product_class->set('name', $class);
    $product_class->setThirdPartySetting('uc_product', 'product', TRUE);
    $product_class->original = new NodeType($values, 'node_type');
    $product_class->save();
    $node2 = $this->createProduct(array('type' => $product_class->id()));

    $kit = $this->createProductKit(array($node1, $node2));

    switch ($type) {
      default:
      case 'select':
        $display = 1;
        break;

      case 'radios':
        $display = 2;
        break;

      case 'checkboxes':
        $display = 3;
        break;

    }
    $data = array(
      'display' => $display,
      'name' => 'parent',
      'label' => 'Parent',
      'required' => TRUE,
    );
    $parent_attribute = $this->createAttribute($data);
    $data = array(
      'display' => $display,
      'name' => 'child',
      'label' => 'Child',
    );
    $child_attribute = $this->createAttribute($data);
    if ($type == 'textfield') {
      // Textfields are only supported as children.
      $display = 0;
    }
    $data = array(
      'display' => $display,
      'name' => 'grandchild',
      'label' => 'Grandchild',
    );
    $grandchild_attribute = $this->createAttribute($data);

    // Add some options.
    $parent_options = array();
    $options1 = array();
    for ($i = 0; $i < 3; $i++) {
      $option = $this->createAttributeOption(array(
        'aid' => $parent_attribute->aid,
      ));
      $parent_options[$option->oid] = $option;
      if ($i == 0) {
        $show_child = $option->oid;
      }
      if ($i < 2) {
        $options1[$option->oid] = $option->oid;
      }
      if ($i == 0) {
        $oid = $option->oid;
      }
    }
    $child_options = array();
    $options2 = array();
    for ($i = 0; $i < 3; $i++) {
      $option = $this->createAttributeOption(array(
        'aid' => $child_attribute->aid,
      ));
      $child_options[$option->oid] = $option;
      if ($i < 2) {
        $options2[$option->oid] = $option->oid;
      }
    }
    if ($type != 'textfield') {
      $grandchild_options = array();
      for ($i = 0; $i < 3; $i++) {
        $option = $this->createAttributeOption(array(
          'aid' => $grandchild_attribute->aid,
        ));
        $grandchild_options[$option->oid] = $option;
      }
    }

    // Attach the attributes to a product.
    uc_attribute_subject_save($parent_attribute, 'product', $node1->id());
    uc_attribute_subject_save($child_attribute, 'product', $node1->id());
    uc_attribute_subject_save($grandchild_attribute, 'product', $node1->id());

    foreach ($parent_options as $parent_option) {
      $this->attachProductOption($parent_option->oid, $node1->id());
    }
    foreach ($child_options as $child_option) {
      $this->attachProductOption($child_option->oid, $node1->id());
    }
    foreach ($grandchild_options as $grandchild_option) {
      $this->attachProductOption($grandchild_option->oid, $node1->id());
    }

    // Create dependent attribute.
    uc_dropdown_attributes_product_create_dependency(
      $node1->id(),
      $child_attribute->aid,
      $parent_attribute->aid,
      $options1,
      1
    );
    uc_dropdown_attributes_product_create_dependency(
      $node1->id(),
      $grandchild_attribute->aid,
      $child_attribute->aid,
      $options2,
      1
    );

    switch ($type) {
      default:
      case 'select':
        $display = 1;
        break;

      case 'radios':
        $display = 2;
        break;

      case 'checkboxes':
        $display = 3;
        break;

    }
    $data = array(
      'display' => $display,
      'name' => 'classparent',
      'label' => 'Class parent',
      'required' => TRUE,
    );
    $class_parent_attribute = $this->createAttribute($data);
    $data = array(
      'display' => $display,
      'name' => 'classchild',
      'label' => 'Class child',
    );
    $class_child_attribute = $this->createAttribute($data);
    if ($type == 'textfield') {
      // Textfields are only supported as children.
      $display = 0;
    }
    $data = array(
      'display' => $display,
      'name' => 'classgrandchild',
      'label' => 'Class grandchild',
    );
    $class_grandchild_attribute = $this->createAttribute($data);

    // Add some options.
    $class_parent_options = array();
    $class_options1 = array();
    for ($i = 0; $i < 3; $i++) {
      $option = $this->createAttributeOption(array(
        'aid' => $class_parent_attribute->aid,
      ));
      $class_parent_options[$option->oid] = $option;
      if ($i == 0) {
        $show_child = $option->oid;
      }
      if ($i < 2) {
        $class_options1[$option->oid] = $option->oid;
      }
      if ($i == 0) {
        $oid = $option->oid;
      }
    }
    $class_child_options = array();
    $class_options2 = array();
    for ($i = 0; $i < 3; $i++) {
      $option = $this->createAttributeOption(array(
        'aid' => $class_child_attribute->aid,
      ));
      $class_child_options[$option->oid] = $option;
      if ($i < 2) {
        $class_options2[$option->oid] = $option->oid;
      }
    }
    if ($type != 'textfield') {
      $class_grandchild_options = array();
      for ($i = 0; $i < 3; $i++) {
        $option = $this->createAttributeOption(array(
          'aid' => $class_grandchild_attribute->aid,
        ));
        $class_grandchild_options[$option->oid] = $option;
      }
    }

    // Attach the attributes to a product.
    uc_attribute_subject_save($class_parent_attribute, 'class', $product_class->id());
    uc_attribute_subject_save($class_child_attribute, 'class', $product_class->id());
    uc_attribute_subject_save($class_grandchild_attribute, 'class', $product_class->id());

    foreach ($class_parent_options as $parent_option) {
      $this->attachClassOption($parent_option->oid, $product_class->id());
    }
    foreach ($class_child_options as $child_option) {
      $this->attachClassOption($child_option->oid, $product_class->id());
    }
    foreach ($class_grandchild_options as $grandchild_option) {
      $this->attachClassOption($grandchild_option->oid, $product_class->id());
    }

    // Create dependent attribute.
    uc_dropdown_attributes_class_create_dependency(
      $product_class->id(),
      $class_child_attribute->aid,
      $class_parent_attribute->aid,
      $class_options1,
      1
    );
    uc_dropdown_attributes_class_create_dependency(
      $product_class->id(),
      $class_grandchild_attribute->aid,
      $class_child_attribute->aid,
      $class_options2,
      1
    );

    $response = array();
    $response['status'] = TRUE;
    $response['user'] = $user;
    $response['nid'] = $kit->id();
    return new JsonResponse($response);
  }

  /**
   * Generate a random string.
   *
   * @param int $length
   *   Length of string to return.
   *
   * @return string
   *   Random string.
   */
  protected function randGen($length) {
    $i = 0;
    $result = "";
    while ($i <= $length) {
      $num = rand() % strlen($this->vals);
      $tmp = substr($this->vals, $num, 1);
      $result = $result . $tmp;
      $i++;
    }
    return $result;
  }

  /**
   * Create a product.
   *
   * @param array $data
   *   Optional data for the product.
   *
   * @return object
   *   Product node object.
   */
  protected function createProduct(array $data = array()) {
    $weight_units = array('lb', 'kg', 'oz', 'g');
    $length_units = array('in', 'ft', 'cm', 'mm');
    $product = $data + array(
      'type' => 'product',
      'model' => $this->randGen(8),
      'cost' => mt_rand(1, 9999),
      'price' => mt_rand(1, 9999),
      'weight' => array(
        0 => array(
          'value' => mt_rand(1, 9999),
          'units' => array_rand(array_flip($weight_units)),
        ),
      ),
      'dimensions' => array(
        0 => array(
          'length' => mt_rand(1, 9999),
          'width' => mt_rand(1, 9999),
          'height' => mt_rand(1, 9999),
          'units' => array_rand(array_flip($length_units)),
        ),
      ),
      'pkg_qty' => mt_rand(1, 99),
      'default_qty' => 1,
      'shippable' => 1,
    );
    $product['model'] = array(array('value' => $product['model']));
    $product['price'] = array(array('value' => $product['price']));
    $product += array(
      'body'      => array(
        array(
          'value' => $this->randGen(32),
          'format' => filter_default_format(),
        ),
      ),
      'title'     => $this->randGen(8),
      'type'      => 'page',
      'uid'       => \Drupal::currentUser()->id(),
    );

    $node = entity_create('node', $product);
    $node->save();
    return $node;
  }

  /**
   * Attach an option to a product.
   *
   * @param int $oid
   *   Option ID.
   * @param int $nid
   *   Product node ID.
   */
  protected function attachProductOption($oid, $nid) {
    \Drupal::database()->merge('uc_product_options')
      ->key(array('nid' => $nid, 'oid' => $oid))
      ->fields(array(
        'cost' => 0.00,
        'price' => 0.00,
        'weight' => 0,
        'ordering' => 0,
      ))
      ->execute();
  }

  /**
   * Attach an option to a product class.
   *
   * @param int $oid
   *   Option ID.
   * @param int $pcid
   *   Product class ID.
   */
  protected function attachClassOption($oid, $pcid) {
    \Drupal::database()->merge('uc_class_attribute_options')
      ->key(array('pcid' => $pcid, 'oid' => $oid))
      ->fields(array(
        'cost' => 0.00,
        'price' => 0.00,
        'weight' => 0,
        'ordering' => 0,
      ))
      ->execute();
  }

  /**
   * Creates an attribute.
   *
   * @param array $data
   *   Attribute data.
   * @param bool $save
   *   TRUE if attribute should be saved.
   */
  protected function createAttribute(array $data = array(), $save = TRUE) {
    $attribute = $data + array(
      'name' => $this->randGen(8),
      'label' => $this->randGen(8),
      'description' => $this->randGen(8),
      'required' => mt_rand(0, 1) ? TRUE : FALSE,
      'display' => mt_rand(0, 3),
      'ordering' => mt_rand(-10, 10),
    );
    $attribute = (object) $attribute;

    if ($save) {
      uc_attribute_save($attribute);
    }
    return $attribute;
  }

  /**
   * Creates an attribute option.
   *
   * @param array $data
   *   Attribute data.
   * @param bool $save
   *   TRUE if attribute should be saved.
   */
  protected function createAttributeOption(array $data = array(), $save = TRUE) {
    $max_aid = \Drupal::database()->select('uc_attributes', 'a')
      ->fields('a', array('aid'))
      ->orderBy('aid', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchField();
    $option = $data + array(
      'aid' => $max_aid,
      'name' => $this->randGen(8),
      'cost' => mt_rand(0, 500),
      'price' => mt_rand(0, 500),
      'weight' => mt_rand(0, 500),
      'ordering' => mt_rand(-10, 10),
    );
    $option = (object) $option;
    if ($save) {
      uc_attribute_option_save($option);
    }
    return $option;
  }

  /**
   * Create a product kit.
   *
   * @param array $products
   *   Array of product objects.
   * @param array $data
   *   Optional data for the product kit.
   *
   * @return object
   *   Product kit node object.
   */
  protected function createProductKit(array $products, array $data = array()) {
    $weight_units = array('lb', 'kg', 'oz', 'g');
    $length_units = array('in', 'ft', 'cm', 'mm');
    $product_kit = $data + array(
      'type' => 'product_kit',
      'default_qty' => 1,
      'model' => $this->randGen(8),
      'cost' => 0,
      'price' => 0,
      'weight' => array(
        0 => array(
          'value' => mt_rand(1, 9999),
          'units' => array_rand(array_flip($weight_units)),
        ),
      ),
      'dimensions' => array(
        0 => array(
          'length' => mt_rand(1, 9999),
          'width' => mt_rand(1, 9999),
          'height' => mt_rand(1, 9999),
          'units' => array_rand(array_flip($length_units)),
        ),
      ),
      'shippable' => 0,
      'mutable' => 0,
      'weight_units' => 'lb',
    );
    $product_kit += array(
      'body'      => array(
        array(
          'value' => $this->randGen(32),
          'format' => filter_default_format(),
        ),
      ),
      'title'     => $this->randGen(8),
      'uid'       => \Drupal::currentUser()->id(),
    );

    $node = Node::create($product_kit);
    $node->save();
    $model = $node->model->value;
    $cost = $node->cost->value;
    $price = $node->price->value;
    $weight = $node->weight->value;
    $shippable = $node->shippable->value;
    foreach ($products as $product) {
      \Drupal::database()->insert('uc_product_kits')
        ->fields(array(
          'vid' => $node->getRevisionId(),
          'nid' => $node->id(),
          'product_id' => $product->id(),
          'mutable' => $node->mutable,
          'qty' => 1,
          'synchronized' => 1,
        ))
        ->execute();
      $model .= $product->model->value . ' / ';
      $cost += $product->cost->value;
      $price += $product->price->value;
      $weight += $product->weight->value * uc_weight_conversion($product->weight->units, $obj->weight_units);
      if ($product->shippable->value) {
        $shippable = TRUE;
      }
    }
    $model = rtrim($model, ' / ');
    $node->price->setValue($price);
    \Drupal::database()->merge('uc_products')
      ->key(array('vid' => $node->vid->value))
      ->fields(array(
        'nid' => $node->id(),
        'model' => $model,
        'cost' => $cost,
        'price' => $price,
        'weight' => $weight,
        'weight_units' => $node->weight_units,
        'default_qty' => $node->default_qty->value,
        'shippable' => $shippable,
      ))
      ->execute();
    $node = Node::load($node->id());
    return $node;
  }

}
