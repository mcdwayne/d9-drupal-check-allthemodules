<?php

namespace Drupal\blockchain_test\Entity;

use Drupal\blockchain\Entity\BlockchainBlock;

/**
 * Defines the Blockchain test Block entity.
 *
 * @ingroup blockchain
 *
 * @ContentEntityType(
 *   id = "blockchain_test_block",
 *   label = @Translation("Blockchain Test Block"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\blockchain\BlockchainBlockListBuilder",
 *     "views_data" = "Drupal\blockchain\Entity\BlockchainBlockViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\blockchain\Form\BlockchainBlockForm",
 *       "add" = "Drupal\blockchain\Form\BlockchainBlockForm",
 *     },
 *     "access" = "Drupal\blockchain\BlockchainBlockAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\blockchain\BlockchainBlockHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "blockchain_test_block",
 *   admin_permission = "administer blockchain block entities",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   links = {
 *     "canonical" = "/blockchain_test_block/{blockchain_test_block}",
 *     "add-form" = "/blockchain_test_block/add",
 *     "collection" = "/admin/structure/blockchain_test_block/collection",
 *   },
 *   field_ui_base_route = "blockchain_test_block.settings",
 *   blockchain_entity = TRUE,
 * )
 */
class BlockchainTestBlock extends BlockchainBlock {

}
