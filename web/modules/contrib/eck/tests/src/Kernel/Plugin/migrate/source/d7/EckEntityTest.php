<?php

namespace Drupal\Tests\eck\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D7 ECK entity source plugin.
 *
 * @covers \Drupal\eck\Plugin\migrate\source\d7\EckEntity
 *
 * @group eck
 */
class EckEntityTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['eck', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['database'] = [
      'eck_simple_entity' => [
        [
          'id' => '1',
          'type' => 'simple_entity',
          'title' => 'Simple entity 1',
          'uid' => '1',
          'created' => '1611393950',
          'changed' => '1611395400',
        ],
        [
          'id' => '2',
          'type' => 'simple_entity',
          'title' => 'Simple entity 2',
          'uid' => '1',
          'created' => '1611393960',
          'changed' => '1611395400',
        ],
      ],
      'eck_complex_entity' => [
        [
          'id' => '1',
          'type' => 'complex_entity',
          'title' => 'Complex entity 1',
          'uid' => '1',
          'created' => '1611395444',
          'changed' => '1611396304',
          'language' => 'en',
          'description' => '',
        ],
        [
          'id' => '2',
          'type' => 'complex_entity',
          'title' => 'Complex entity 2',
          'uid' => '1',
          'created' => '1611396265',
          'changed' => '1611396265',
          'language' => 'en',
          'description' => '',
        ],
        [
          'id' => '3',
          'type' => 'complex_entity',
          'title' => 'Complex entity 3',
          'uid' => '1',
          'created' => '1611396297',
          'changed' => '1611396297',
          'language' => 'fr',
          'description' => '',
        ],
        [
          'id' => '4',
          'type' => 'another_bundle',
          'title' => 'Entity of another complex bundle 1',
          'uid' => '1',
          'created' => '1611397060',
          'changed' => '1611397101',
          'language' => 'en',
          'description' => '',
        ],
        [
          'id' => '5',
          'type' => 'another_bundle',
          'title' => 'Entity of another complex bundle 2',
          'uid' => '1',
          'created' => '1611397089',
          'changed' => '1611397089',
          'language' => 'en',
          'description' => '',
        ],
      ],
      'entity_translation' => [
        [
          'entity_type' => 'complex_entity',
          'entity_id' => '1',
          'revision_id' => '1',
          'language' => 'en',
          'source' => '',
          'uid' => '1',
          'status' => '1',
          'translate' => '0',
          'created' => '1611395496',
          'changed' => '1611396304',
        ],
        [
          'entity_type' => 'complex_entity',
          'entity_id' => '1',
          'revision_id' => '1',
          'language' => 'fr',
          'source' => 'en',
          'uid' => '1',
          'status' => '1',
          'translate' => '0',
          'created' => '1611396204',
          'changed' => '1611396204',
        ],
        [
          'entity_type' => 'complex_entity',
          'entity_id' => '2',
          'revision_id' => '2',
          'language' => 'en',
          'source' => '',
          'uid' => '1',
          'status' => '1',
          'translate' => '0',
          'created' => '1611396265',
          'changed' => '1611396265',
        ],
        [
          'entity_type' => 'complex_entity',
          'entity_id' => '3',
          'revision_id' => '3',
          'language' => 'fr',
          'source' => '',
          'uid' => '1',
          'status' => '1',
          'translate' => '0',
          'created' => '1611396297',
          'changed' => '1611396297',
        ],
        [
          'entity_type' => 'complex_entity',
          'entity_id' => '4',
          'revision_id' => '4',
          'language' => 'en',
          'source' => '',
          'uid' => '1',
          'status' => '1',
          'translate' => '0',
          'created' => '1611397101',
          'changed' => '1611397101',
        ],
        [
          'entity_type' => 'complex_entity',
          'entity_id' => '5',
          'revision_id' => '5',
          'language' => 'en',
          'source' => '',
          'uid' => '1',
          'status' => '1',
          'translate' => '0',
          'created' => '1611397089',
          'changed' => '1611397089',
        ],
      ],
      'field_config' => [
        [
          'id' => '1',
          'field_name' => 'field_text',
          'type' => 'text',
          'module' => 'text',
          'active' => '1',
          'storage_type' => 'field_sql_storage',
          'storage_module' => 'field_sql_storage',
          'storage_active' => '1',
          'locked' => '0',
          'data' => 'a:7:{s:12:"translatable";s:1:"1";s:12:"entity_types";a:0:{}s:8:"settings";a:2:{s:10:"max_length";s:3:"255";s:23:"entity_translation_sync";b:0;}s:7:"storage";a:5:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";s:1:"1";s:7:"details";a:1:{s:3:"sql";a:2:{s:18:"FIELD_LOAD_CURRENT";a:1:{s:21:"field_data_field_text";a:2:{s:5:"value";s:16:"field_text_value";s:6:"format";s:17:"field_text_format";}}s:19:"FIELD_LOAD_REVISION";a:1:{s:25:"field_revision_field_text";a:2:{s:5:"value";s:16:"field_text_value";s:6:"format";s:17:"field_text_format";}}}}}s:12:"foreign keys";a:1:{s:6:"format";a:2:{s:5:"table";s:13:"filter_format";s:7:"columns";a:1:{s:6:"format";s:6:"format";}}}s:7:"indexes";a:1:{s:6:"format";a:1:{i:0;s:6:"format";}}s:2:"id";s:1:"1";}',
          'cardinality' => '1',
          'translatable' => '1',
          'deleted' => '0',
        ],
        [
          'id' => '3',
          'field_name' => 'field_simple_entities',
          'type' => 'entityreference',
          'module' => 'entityreference',
          'active' => '1',
          'storage_type' => 'field_sql_storage',
          'storage_module' => 'field_sql_storage',
          'storage_active' => '1',
          'locked' => '0',
          'data' => 'a:7:{s:12:"translatable";s:1:"0";s:12:"entity_types";a:0:{}s:8:"settings";a:3:{s:11:"target_type";s:13:"simple_entity";s:7:"handler";s:4:"base";s:16:"handler_settings";a:2:{s:14:"target_bundles";a:1:{s:13:"simple_entity";s:13:"simple_entity";}s:4:"sort";a:1:{s:4:"type";s:4:"none";}}}s:7:"storage";a:5:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";s:1:"1";s:7:"details";a:1:{s:3:"sql";a:2:{s:18:"FIELD_LOAD_CURRENT";a:1:{s:32:"field_data_field_simple_entities";a:1:{s:9:"target_id";s:31:"field_simple_entities_target_id";}}s:19:"FIELD_LOAD_REVISION";a:1:{s:36:"field_revision_field_simple_entities";a:1:{s:9:"target_id";s:31:"field_simple_entities_target_id";}}}}}s:12:"foreign keys";a:1:{s:17:"eck_simple_entity";a:2:{s:5:"table";s:17:"eck_simple_entity";s:7:"columns";a:1:{s:9:"target_id";s:2:"id";}}}s:7:"indexes";a:1:{s:9:"target_id";a:1:{i:0;s:9:"target_id";}}s:2:"id";s:1:"3";}',
          'cardinality' => '-1',
          'translatable' => '0',
          'deleted' => '0',
        ],
        [
          'id' => '4',
          'field_name' => 'field_node',
          'type' => 'entityreference',
          'module' => 'entityreference',
          'active' => '1',
          'storage_type' => 'field_sql_storage',
          'storage_module' => 'field_sql_storage',
          'storage_active' => '1',
          'locked' => '0',
          'data' => 'a:7:{s:12:"translatable";s:1:"0";s:12:"entity_types";a:0:{}s:8:"settings";a:3:{s:11:"target_type";s:4:"node";s:7:"handler";s:4:"base";s:16:"handler_settings";a:2:{s:14:"target_bundles";a:0:{}s:4:"sort";a:1:{s:4:"type";s:4:"none";}}}s:7:"storage";a:5:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";s:1:"1";s:7:"details";a:1:{s:3:"sql";a:2:{s:18:"FIELD_LOAD_CURRENT";a:1:{s:21:"field_data_field_node";a:1:{s:9:"target_id";s:20:"field_node_target_id";}}s:19:"FIELD_LOAD_REVISION";a:1:{s:25:"field_revision_field_node";a:1:{s:9:"target_id";s:20:"field_node_target_id";}}}}}s:12:"foreign keys";a:1:{s:4:"node";a:2:{s:5:"table";s:4:"node";s:7:"columns";a:1:{s:9:"target_id";s:3:"nid";}}}s:7:"indexes";a:1:{s:9:"target_id";a:1:{i:0;s:9:"target_id";}}s:2:"id";s:1:"4";}',
          'cardinality' => '1',
          'translatable' => '0',
          'deleted' => '0',
        ],
        [
          'id' => '5',
          'field_name' => 'field_complex_entity',
          'type' => 'entityreference',
          'module' => 'entityreference',
          'active' => '1',
          'storage_type' => 'field_sql_storage',
          'storage_module' => 'field_sql_storage',
          'storage_active' => '1',
          'locked' => '0',
          'data' => 'a:7:{s:12:"translatable";s:1:"0";s:12:"entity_types";a:0:{}s:8:"settings";a:3:{s:11:"target_type";s:14:"complex_entity";s:7:"handler";s:4:"base";s:16:"handler_settings";a:2:{s:14:"target_bundles";a:0:{}s:4:"sort";a:1:{s:4:"type";s:4:"none";}}}s:7:"storage";a:5:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";s:1:"1";s:7:"details";a:1:{s:3:"sql";a:2:{s:18:"FIELD_LOAD_CURRENT";a:1:{s:31:"field_data_field_complex_entity";a:1:{s:9:"target_id";s:30:"field_complex_entity_target_id";}}s:19:"FIELD_LOAD_REVISION";a:1:{s:35:"field_revision_field_complex_entity";a:1:{s:9:"target_id";s:30:"field_complex_entity_target_id";}}}}}s:12:"foreign keys";a:1:{s:18:"eck_complex_entity";a:2:{s:5:"table";s:18:"eck_complex_entity";s:7:"columns";a:1:{s:9:"target_id";s:2:"id";}}}s:7:"indexes";a:1:{s:9:"target_id";a:1:{i:0;s:9:"target_id";}}s:2:"id";s:1:"5";}',
          'cardinality' => '1',
          'translatable' => '0',
          'deleted' => '0',
        ],
        [
          'id' => '6',
          'field_name' => 'body',
          'type' => 'text_with_summary',
          'module' => 'text',
          'active' => '1',
          'storage_type' => 'field_sql_storage',
          'storage_module' => 'field_sql_storage',
          'storage_active' => '1',
          'locked' => '0',
          'data' => 'a:6:{s:12:"entity_types";a:1:{i:0;s:4:"node";}s:12:"translatable";b:0;s:8:"settings";a:0:{}s:7:"storage";a:4:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";i:1;}s:12:"foreign keys";a:1:{s:6:"format";a:2:{s:5:"table";s:13:"filter_format";s:7:"columns";a:1:{s:6:"format";s:6:"format";}}}s:7:"indexes";a:1:{s:6:"format";a:1:{i:0;s:6:"format";}}}',
          'cardinality' => '1',
          'translatable' => '0',
          'deleted' => '0',
        ],
      ],
      'field_config_instance' => [
        [
          'id' => '1',
          'field_id' => '1',
          'field_name' => 'field_text',
          'entity_type' => 'simple_entity',
          'bundle' => 'simple_entity',
          'data' => 'a:7:{s:5:"label";s:4:"Text";s:6:"widget";a:5:{s:6:"weight";s:1:"1";s:4:"type";s:14:"text_textfield";s:6:"module";s:4:"text";s:6:"active";i:1;s:8:"settings";a:1:{s:4:"size";s:2:"60";}}s:8:"settings";a:2:{s:15:"text_processing";s:1:"0";s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:12:"text_default";s:8:"settings";a:0:{}s:6:"module";s:4:"text";s:6:"weight";i:0;}}s:8:"required";i:0;s:11:"description";s:0:"";s:13:"default_value";N;}',
          'deleted' => '0',
        ],
        [
          'id' => '3',
          'field_id' => '1',
          'field_name' => 'field_text',
          'entity_type' => 'complex_entity',
          'bundle' => 'complex_entity',
          'data' => 'a:7:{s:5:"label";s:4:"Text";s:6:"widget";a:5:{s:6:"weight";s:1:"2";s:4:"type";s:14:"text_textfield";s:6:"module";s:4:"text";s:6:"active";i:1;s:8:"settings";a:1:{s:4:"size";s:2:"60";}}s:8:"settings";a:3:{s:15:"text_processing";s:1:"0";s:18:"user_register_form";b:0;s:23:"entity_translation_sync";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:12:"text_default";s:8:"settings";a:0:{}s:6:"module";s:4:"text";s:6:"weight";i:0;}}s:8:"required";i:1;s:11:"description";s:0:"";s:13:"default_value";N;}',
          'deleted' => '0',
        ],
        [
          'id' => '4',
          'field_id' => '3',
          'field_name' => 'field_simple_entities',
          'entity_type' => 'complex_entity',
          'bundle' => 'complex_entity',
          'data' => 'a:7:{s:5:"label";s:15:"Simple entities";s:6:"widget";a:5:{s:6:"weight";s:1:"3";s:4:"type";s:28:"entityreference_autocomplete";s:6:"module";s:15:"entityreference";s:6:"active";i:1;s:8:"settings";a:3:{s:14:"match_operator";s:8:"CONTAINS";s:4:"size";s:2:"60";s:4:"path";s:0:"";}}s:8:"settings";a:1:{s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:21:"entityreference_label";s:8:"settings";a:2:{s:4:"link";b:0;s:13:"bypass_access";b:0;}s:6:"module";s:15:"entityreference";s:6:"weight";i:1;}}s:8:"required";i:0;s:11:"description";s:0:"";s:13:"default_value";N;}',
          'deleted' => '0',
        ],
        [
          'id' => '5',
          'field_id' => '4',
          'field_name' => 'field_node',
          'entity_type' => 'complex_entity',
          'bundle' => 'complex_entity',
          'data' => 'a:7:{s:5:"label";s:4:"Node";s:6:"widget";a:5:{s:6:"weight";s:1:"4";s:4:"type";s:28:"entityreference_autocomplete";s:6:"module";s:15:"entityreference";s:6:"active";i:1;s:8:"settings";a:3:{s:14:"match_operator";s:8:"CONTAINS";s:4:"size";s:2:"60";s:4:"path";s:0:"";}}s:8:"settings";a:1:{s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:21:"entityreference_label";s:8:"settings";a:2:{s:4:"link";b:0;s:13:"bypass_access";b:0;}s:6:"module";s:15:"entityreference";s:6:"weight";i:2;}}s:8:"required";i:1;s:11:"description";s:0:"";s:13:"default_value";N;}',
          'deleted' => '0',
        ],
        [
          'id' => '6',
          'field_id' => '5',
          'field_name' => 'field_complex_entity',
          'entity_type' => 'complex_entity',
          'bundle' => 'complex_entity',
          'data' => 'a:7:{s:5:"label";s:14:"Complex entity";s:6:"widget";a:5:{s:6:"weight";s:1:"5";s:4:"type";s:28:"entityreference_autocomplete";s:6:"module";s:15:"entityreference";s:6:"active";i:1;s:8:"settings";a:3:{s:14:"match_operator";s:8:"CONTAINS";s:4:"size";s:2:"60";s:4:"path";s:0:"";}}s:8:"settings";a:1:{s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:21:"entityreference_label";s:8:"settings";a:2:{s:4:"link";b:0;s:13:"bypass_access";b:0;}s:6:"module";s:15:"entityreference";s:6:"weight";i:3;}}s:8:"required";i:0;s:11:"description";s:0:"";s:13:"default_value";N;}',
          'deleted' => '0',
        ],
        [
          'id' => '7',
          'field_id' => '6',
          'field_name' => 'body',
          'entity_type' => 'node',
          'bundle' => 'article',
          'data' => 'a:6:{s:5:"label";s:4:"Body";s:6:"widget";a:4:{s:4:"type";s:26:"text_textarea_with_summary";s:8:"settings";a:2:{s:4:"rows";i:20;s:12:"summary_rows";i:5;}s:6:"weight";i:-4;s:6:"module";s:4:"text";}s:8:"settings";a:3:{s:15:"display_summary";b:1;s:15:"text_processing";i:1;s:18:"user_register_form";b:0;}s:7:"display";a:2:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:12:"text_default";s:8:"settings";a:0:{}s:6:"module";s:4:"text";s:6:"weight";i:0;}s:6:"teaser";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:23:"text_summary_or_trimmed";s:8:"settings";a:1:{s:11:"trim_length";i:600;}s:6:"module";s:4:"text";s:6:"weight";i:0;}}s:8:"required";b:0;s:11:"description";s:0:"";}',
          'deleted' => '0',
        ],
        [
          'id' => '8',
          'field_id' => '1',
          'field_name' => 'field_text',
          'entity_type' => 'complex_entity',
          'bundle' => 'another_bundle',
          'data' => 'a:7:{s:5:"label";s:4:"Text";s:6:"widget";a:5:{s:6:"weight";s:1:"2";s:4:"type";s:14:"text_textfield";s:6:"module";s:4:"text";s:6:"active";i:1;s:8:"settings";a:1:{s:4:"size";s:2:"60";}}s:8:"settings";a:3:{s:15:"text_processing";s:1:"0";s:18:"user_register_form";b:0;s:23:"entity_translation_sync";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:12:"text_default";s:8:"settings";a:0:{}s:6:"module";s:4:"text";s:6:"weight";i:0;}}s:8:"required";i:0;s:11:"description";s:0:"";s:13:"default_value";N;}',
          'deleted' => '0',
        ],
      ],
      'field_revision_field_complex_entity' => [
        [
          'entity_type',
          'bundle',
          'deleted',
          'entity_id',
          'revision_id',
          'language',
          'delta',
          'field_complex_entity_target_id',
        ],
        [
          'entity_type' => 'complex_entity',
          'bundle' => 'complex_entity',
          'deleted' => '0',
          'entity_id' => '1',
          'revision_id' => '1',
          'language' => 'und',
          'delta' => '0',
          'field_complex_entity_target_id' => '3',
        ],
        [
          'entity_type' => 'complex_entity',
          'bundle' => 'complex_entity',
          'deleted' => '0',
          'entity_id' => '2',
          'revision_id' => '2',
          'language' => 'und',
          'delta' => '0',
          'field_complex_entity_target_id' => '1',
        ],
        [
          'entity_type' => 'complex_entity',
          'bundle' => 'complex_entity',
          'deleted' => '0',
          'entity_id' => '3',
          'revision_id' => '3',
          'language' => 'und',
          'delta' => '0',
          'field_complex_entity_target_id' => '2',
        ],
      ],
      'field_revision_field_node' => [
        [
          'entity_type' => 'complex_entity',
          'bundle' => 'complex_entity',
          'deleted' => '0',
          'entity_id' => '1',
          'revision_id' => '1',
          'language' => 'und',
          'delta' => '0',
          'field_node_target_id' => '1',
        ],
        [
          'entity_type' => 'complex_entity',
          'bundle' => 'complex_entity',
          'deleted' => '0',
          'entity_id' => '2',
          'revision_id' => '2',
          'language' => 'und',
          'delta' => '0',
          'field_node_target_id' => '2',
        ],
        [
          'entity_type' => 'complex_entity',
          'bundle' => 'complex_entity',
          'deleted' => '0',
          'entity_id' => '3',
          'revision_id' => '3',
          'language' => 'und',
          'delta' => '0',
          'field_node_target_id' => '1',
        ],
      ],
      'field_revision_field_simple_entities' => [
        [
          'entity_type',
          'bundle',
          'deleted',
          'entity_id',
          'revision_id',
          'language',
          'delta',
          'field_simple_entities_target_id',
        ],
        [
          'entity_type' => 'complex_entity',
          'bundle' => 'complex_entity',
          'deleted' => '0',
          'entity_id' => '1',
          'revision_id' => '1',
          'language' => 'und',
          'delta' => '0',
          'field_simple_entities_target_id' => '1',
        ],
        [
          'entity_type' => 'complex_entity',
          'bundle' => 'complex_entity',
          'deleted' => '0',
          'entity_id' => '1',
          'revision_id' => '1',
          'language' => 'und',
          'delta' => '1',
          'field_simple_entities_target_id' => '2',
        ],
        [
          'entity_type' => 'complex_entity',
          'bundle' => 'complex_entity',
          'deleted' => '0',
          'entity_id' => '2',
          'revision_id' => '2',
          'language' => 'und',
          'delta' => '0',
          'field_simple_entities_target_id' => '1',
        ],
      ],
      'field_revision_field_text' => [
        [
          'entity_type' => 'complex_entity',
          'bundle' => 'complex_entity',
          'deleted' => '0',
          'entity_id' => '1',
          'revision_id' => '1',
          'language' => 'en',
          'delta' => '0',
          'field_text_value' => 'Complex entity text value - English version.',
          'field_text_format' => NULL,
        ],
        [
          'entity_type' => 'complex_entity',
          'bundle' => 'complex_entity',
          'deleted' => '0',
          'entity_id' => '1',
          'revision_id' => '1',
          'language' => 'fr',
          'delta' => '0',
          'field_text_value' => 'Complex entity text value - French version.',
          'field_text_format' => NULL,
        ],
        [
          'entity_type' => 'complex_entity',
          'bundle' => 'complex_entity',
          'deleted' => '0',
          'entity_id' => '2',
          'revision_id' => '2',
          'language' => 'en',
          'delta' => '0',
          'field_text_value' => 'Complex entity 2 text value - English version.',
          'field_text_format' => NULL,
        ],
        [
          'entity_type' => 'complex_entity',
          'bundle' => 'complex_entity',
          'deleted' => '0',
          'entity_id' => '3',
          'revision_id' => '3',
          'language' => 'fr',
          'delta' => '0',
          'field_text_value' => 'Complex entity 3 text value - French version.',
          'field_text_format' => NULL,
        ],
        [
          'entity_type' => 'complex_entity',
          'bundle' => 'another_bundle',
          'deleted' => '0',
          'entity_id' => '4',
          'revision_id' => '4',
          'language' => 'en',
          'delta' => '0',
          'field_text_value' => 'Text value of another complex bundle 1.',
          'field_text_format' => NULL,
        ],
        [
          'entity_type' => 'complex_entity',
          'bundle' => 'another_bundle',
          'deleted' => '0',
          'entity_id' => '5',
          'revision_id' => '5',
          'language' => 'en',
          'delta' => '0',
          'field_text_value' => 'Text value of another complex bundle 2.',
          'field_text_format' => NULL,
        ],
        [
          'entity_type' => 'simple_entity',
          'bundle' => 'simple_entity',
          'deleted' => '0',
          'entity_id' => '1',
          'revision_id' => '1',
          'language' => 'en',
          'delta' => '0',
          'field_text_value' => 'Simple entity 1 text value.',
          'field_text_format' => NULL,
        ],
        [
          'entity_type' => 'simple_entity',
          'bundle' => 'simple_entity',
          'deleted' => '0',
          'entity_id' => '2',
          'revision_id' => '2',
          'language' => 'en',
          'delta' => '0',
          'field_text_value' => 'Simple entity 2 text value.',
          'field_text_format' => NULL,
        ],
      ],
      'variable' => [
        [
          'name' => 'entity_translation_settings_complex_entity__complex_entity',
          'value' => 'a:5:{s:16:"default_language";s:2:"en";s:22:"hide_language_selector";i:0;s:21:"exclude_language_none";i:1;s:13:"lock_language";i:0;s:27:"shared_fields_original_only";i:0;}',
        ],
      ],
    ];
    $tests[0]['expected_results'] = $tests[0]['database']['eck_simple_entity'];
    for ($i = 0; $i < 2; $i++) {
      array_merge($tests[0]['expected_results'][$i], [
        'language' => 'und',
      ]);
    }
    $tests[0]['expected_count'] = NULL;
    $tests[0]['configuration'] = [
      'bundle' => 'simple_entity',
      'entity_type' => 'simple_entity',
    ];

    // Complex entity.
    $tests[1] = $tests[0];
    $tests[1]['expected_results'] = [
      [
        'id' => '1',
        'type' => 'complex_entity',
        'title' => 'Complex entity 1',
        'uid' => '1',
        'created' => '1611395444',
        'changed' => '1611396304',
        'language' => 'en',
      ],
      [
        'id' => '2',
        'type' => 'complex_entity',
        'title' => 'Complex entity 2',
        'uid' => '1',
        'created' => '1611396265',
        'changed' => '1611396265',
        'language' => 'en',
        'description' => '',
        'entity_type' => 'complex_entity',
      ],
      [
        'id' => '3',
        'type' => 'complex_entity',
        'title' => 'Complex entity 3',
        'uid' => '1',
        'created' => '1611396297',
        'changed' => '1611396297',
        'language' => 'fr',
        'description' => '',
      ],
    ];
    $tests[1]['expected_count'] = NULL;
    $tests[1]['configuration'] = [
      'bundle' => 'complex_entity',
      'entity_type' => 'complex_entity',
    ];

    // All bundles for an entity type.
    $tests[2] = $tests[1];
    $tests[2]['expected_results'][] = [
      'id' => '4',
      'type' => 'another_bundle',
      'title' => 'Entity of another complex bundle 1',
      'uid' => '1',
      'created' => '1611397060',
      'changed' => '1611397101',
      'language' => 'en',
      'description' => '',
    ];
    $tests[2]['expected_results'][] = [
      'id' => '5',
      'type' => 'another_bundle',
      'title' => 'Entity of another complex bundle 2',
      'uid' => '1',
      'created' => '1611397089',
      'changed' => '1611397089',
      'language' => 'en',
      'description' => '',
    ];

    $tests[2]['expected_count'] = NULL;
    $tests[2]['configuration'] = [
      'entity_type' => 'complex_entity',
    ];

    // An invalid bundle.
    $tests[3] = $tests[0];
    $tests[3]['expected_results'] = [];
    $tests[3]['expected_count'] = NULL;
    $tests[3]['configuration'] = [
      'bundle' => 'invalid',
      'entity_type' => 'complex_entity',
    ];

    return $tests;
  }

}
