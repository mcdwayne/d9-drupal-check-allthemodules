<?php

namespace Drupal\Tests\monster_menus\Kernel\migrate;


/**
 * Test the migration of mm fields and types.
 *
 * @group monster_menus
 */
class FieldMigrationTest extends MonsterMenusMigrationTestBase {

    /**
     * {@inheritdoc}
     */
    public static $modules = [
        'comment',
        'datetime',
        'datetime_range',
        'entity_reference_revisions',
        'field',
        'file',
        'image',
        'link',
        'menu_ui',
        'migrate_drupal',
        'node',
        'options',
        'paragraphs',
        'system',
        'taxonomy',
        'telephone',
        'text',
        'user',
        'media',
        'monster_menus',
        'mm_fields'
    ];

    /**
     * Test that the field storage was migrated.
     */

    public function testMonsterMenusFieldMigration() {
        $this->executeMigration('d7_field');


        $this->assertNodeFieldExists('field_node_view', 'entity_reference');
        $this->assertNodeFieldExists('field_mm_events', 'entity_reference');
        $this->assertNodeFieldExists('field_mm_groups', 'mm_grouplist');
        $this->assertNodeFieldExists('field_mm_pages', 'mm_catlist');
        $this->assertNodeFieldExists('field_multimedia', 'entity_reference');
        $this->assertNodeFieldExists('field_mult_thumb', 'entity_reference');
        $this->assertNodeFieldExists('field_mm_college_event_reg_form', 'mm_catlist');
        $this->assertNodeFieldExists('field_news_story_link', 'entity_reference');
        $this->assertParagraphFieldExists('field_ac_academic_prof', 'mm_userlist');
        $this->assertParagraphFieldExists('field_cb_hours_cal', 'entity_reference');
        $this->assertParagraphFieldExists('field_fs_user', 'mm_userlist');
        $this->assertParagraphFieldExists('field_fs_user', 'mm_userlist');
        $this->assertNodeFieldExists('field_ue_calendar', 'entity_reference');
        $this->assertNodeFieldExists('field_ue_news', 'mm_catlist');
        $this->assertNodeFieldExists('field_ue_news_items', 'entity_reference');
    }

}
