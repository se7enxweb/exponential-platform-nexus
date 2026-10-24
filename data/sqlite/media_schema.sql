-- Auto-generated media_schema.sql for SQLite (v4/Ibexa 4.6)
-- Extra tables not covered by the core Ibexa schema

CREATE TABLE IF NOT EXISTS `nglayouts_layout` (
  `id` integer NOT NULL,
  `status` integer NOT NULL,
  `uuid` text(36) NOT NULL,
  `type` text(255) NOT NULL,
  `name` text(255) NOT NULL,
  `description` longtext NOT NULL,
  `created` integer NOT NULL,
  `modified` integer NOT NULL,
  `shared` integer NOT NULL,
  `main_locale` text(255) NOT NULL,
  PRIMARY KEY (`id`, `status`),
  UNIQUE (`uuid`, `status`)
);

CREATE TABLE IF NOT EXISTS `nglayouts_layout_translation` (
  `layout_id` integer NOT NULL,
  `status` integer NOT NULL,
  `locale` text(255) NOT NULL,
  PRIMARY KEY (`layout_id`, `status`, `locale`),
  FOREIGN KEY (`layout_id`, `status`)
    REFERENCES `nglayouts_layout` (`id`, `status`)
);

CREATE TABLE IF NOT EXISTS `nglayouts_block` (
  `id` integer NOT NULL,
  `status` integer NOT NULL,
  `uuid` text(36) NOT NULL,
  `layout_id` integer NOT NULL,
  `depth` integer NOT NULL,
  `path` text(255) NOT NULL,
  `parent_id` integer DEFAULT NULL,
  `placeholder` text(255) DEFAULT NULL,
  `position` integer DEFAULT NULL,
  `definition_identifier` text(255) NOT NULL,
  `view_type` text(255) NOT NULL,
  `item_view_type` text(255) NOT NULL,
  `name` text(255) NOT NULL,
  `config` longtext NOT NULL,
  `translatable` integer NOT NULL,
  `main_locale` text(255) NOT NULL,
  `always_available` integer NOT NULL,
  PRIMARY KEY (`id`, `status`),
  UNIQUE (`uuid`, `status`),
  FOREIGN KEY (`layout_id`, `status`)
    REFERENCES `nglayouts_layout` (`id`, `status`)
);

CREATE TABLE IF NOT EXISTS `nglayouts_block_translation` (
  `block_id` integer NOT NULL,
  `status` integer NOT NULL,
  `locale` text(255) NOT NULL,
  `parameters` longtext NOT NULL,
  PRIMARY KEY (`block_id`, `status`, `locale`),
  FOREIGN KEY (`block_id`, `status`)
    REFERENCES `nglayouts_block` (`id`, `status`)
);

CREATE TABLE IF NOT EXISTS `nglayouts_zone` (
  `identifier` text(255) NOT NULL,
  `layout_id` integer NOT NULL,
  `status` integer NOT NULL,
  `root_block_id` integer NOT NULL,
  `linked_layout_uuid` text(36),
  `linked_zone_identifier` text(255),
  PRIMARY KEY (`identifier`, `layout_id`, `status`),
  FOREIGN KEY (`layout_id`, `status`)
    REFERENCES `nglayouts_layout` (`id`, `status`),
  FOREIGN KEY (`root_block_id`, `status`)
    REFERENCES `nglayouts_block` (`id`, `status`)
);

CREATE TABLE IF NOT EXISTS `nglayouts_collection` (
  `id` integer NOT NULL,
  `status` integer NOT NULL,
  `uuid` text(36) NOT NULL,
  `start` integer NOT NULL,
  `length` integer DEFAULT NULL,
  `translatable` integer NOT NULL,
  `main_locale` text(255) NOT NULL,
  `always_available` integer NOT NULL,
  PRIMARY KEY (`id`, `status`),
  UNIQUE (`uuid`, `status`)
);

CREATE TABLE IF NOT EXISTS `nglayouts_collection_translation` (
  `collection_id` integer NOT NULL,
  `status` integer NOT NULL,
  `locale` text(255) NOT NULL,
  PRIMARY KEY (`collection_id`, `status`, `locale`),
  FOREIGN KEY (`collection_id`, `status`)
    REFERENCES `nglayouts_collection` (`id`, `status`)
);

CREATE TABLE IF NOT EXISTS `nglayouts_collection_item` (
  `id` integer NOT NULL,
  `status` integer NOT NULL,
  `uuid` text(36) NOT NULL,
  `collection_id` integer NOT NULL,
  `position` integer NOT NULL,
  `value` text(255),
  `value_type` text(255) NOT NULL,
  `view_type` text(255),
  `config` longtext NOT NULL,
  PRIMARY KEY (`id`, `status`),
  UNIQUE (`uuid`, `status`),
  FOREIGN KEY (`collection_id`, `status`)
    REFERENCES `nglayouts_collection` (`id`, `status`)
);

CREATE TABLE IF NOT EXISTS `nglayouts_collection_query` (
  `id` integer NOT NULL,
  `status` integer NOT NULL,
  `uuid` text(36) NOT NULL,
  `collection_id` integer NOT NULL,
  `type` text(255) NOT NULL,
  PRIMARY KEY (`id`, `status`),
  UNIQUE (`uuid`, `status`),
  FOREIGN KEY (`collection_id`, `status`)
    REFERENCES `nglayouts_collection` (`id`, `status`)
);

CREATE TABLE IF NOT EXISTS `nglayouts_collection_query_translation` (
  `query_id` integer NOT NULL,
  `status` integer NOT NULL,
  `locale` text(255) NOT NULL,
  `parameters` longtext NOT NULL,
  PRIMARY KEY (`query_id`, `status`, `locale`),
  FOREIGN KEY (`query_id`, `status`)
    REFERENCES `nglayouts_collection_query` (`id`, `status`)
);

CREATE TABLE IF NOT EXISTS `nglayouts_collection_slot` (
  `id` integer NOT NULL,
  `status` integer NOT NULL,
  `uuid` text(36) NOT NULL,
  `collection_id` integer NOT NULL,
  `position` integer NOT NULL,
  `view_type` text(255),
  PRIMARY KEY (`id`, `status`),
  UNIQUE (`uuid`, `status`),
  FOREIGN KEY (`collection_id`, `status`)
    REFERENCES `nglayouts_collection` (`id`, `status`)
);

CREATE TABLE IF NOT EXISTS `nglayouts_block_collection` (
  `block_id` integer NOT NULL,
  `block_status` integer NOT NULL,
  `identifier` text(255) NOT NULL,
  `collection_id` integer NOT NULL,
  `collection_status` integer NOT NULL,
  PRIMARY KEY (`block_id`, `block_status`, `identifier`),
  FOREIGN KEY (`block_id`, `block_status`)
    REFERENCES `nglayouts_block` (`id`, `status`),
  FOREIGN KEY (`collection_id`, `collection_status`)
    REFERENCES `nglayouts_collection` (`id`, `status`)
);

CREATE TABLE IF NOT EXISTS `nglayouts_role_policy` (
  `id` integer NOT NULL,
  `status` integer NOT NULL,
  `uuid` text(36) NOT NULL,
  `role_id` integer NOT NULL,
  `component` text(255) DEFAULT NULL,
  `permission` text(255) DEFAULT NULL,
  `limitations` longtext NOT NULL,
  PRIMARY KEY (`id`, `status`),
  UNIQUE (`uuid`, `status`),
  FOREIGN KEY (`role_id`, `status`)
    REFERENCES `nglayouts_role` (`id`, `status`)
);

CREATE TABLE IF NOT EXISTS `nglayouts_role` (
  `id` integer NOT NULL,
  `status` integer NOT NULL,
  `uuid` text(36) NOT NULL,
  `name` text(255) NOT NULL,
  `identifier` text(255) NOT NULL,
  `description` longtext NOT NULL,
  PRIMARY KEY (`id`, `status`),
  UNIQUE (`uuid`, `status`)
);

CREATE TABLE IF NOT EXISTS `nglayouts_rule_data` (
  `rule_id` integer NOT NULL,
  `enabled` integer NOT NULL,
  `priority` integer NOT NULL,
  PRIMARY KEY (`rule_id`)
);

CREATE TABLE IF NOT EXISTS `nglayouts_rule_target` (
  `id` integer NOT NULL,
  `status` integer NOT NULL,
  `uuid` text(36) NOT NULL,
  `rule_id` integer NOT NULL,
  `type` text(255) NOT NULL,
  `value` text,
  PRIMARY KEY (`id`, `status`),
  UNIQUE (`uuid`, `status`),
  FOREIGN KEY (`rule_id`, `status`)
    REFERENCES `nglayouts_rule` (`id`, `status`)
);

CREATE TABLE IF NOT EXISTS `nglayouts_rule_condition` (
  `id` integer NOT NULL,
  `status` integer NOT NULL,
  `uuid` text(36) NOT NULL,
  `type` text(255) NOT NULL,
  `value` text,
  PRIMARY KEY (`id`, `status`),
  UNIQUE (`uuid`, `status`)
);

CREATE TABLE IF NOT EXISTS `nglayouts_rule` (
  `id` integer NOT NULL,
  `status` integer NOT NULL,
  `uuid` text(36) NOT NULL,
  `rule_group_id` integer NOT NULL,
  `layout_uuid` text(36) DEFAULT NULL,
  `description` longtext NOT NULL,
  PRIMARY KEY (`id`, `status`),
  UNIQUE (`uuid`, `status`)
);

CREATE TABLE IF NOT EXISTS `nglayouts_rule_group` (
  `id` integer NOT NULL,
  `status` integer NOT NULL,
  `uuid` text(36) NOT NULL,
  `depth` integer NOT NULL,
  `path` text(255) NOT NULL,
  `parent_id` integer DEFAULT NULL,
  `name` text(255) NOT NULL,
  `description` longtext NOT NULL,
  PRIMARY KEY (`id`, `status`),
  UNIQUE (`uuid`, `status`)
);

CREATE TABLE IF NOT EXISTS `nglayouts_rule_group_data` (
  `rule_group_id` integer NOT NULL,
  `enabled` integer NOT NULL,
  `priority` integer NOT NULL,
  PRIMARY KEY (`rule_group_id`)
);

CREATE TABLE IF NOT EXISTS `nglayouts_rule_condition_rule` (
  `condition_id` integer NOT NULL,
  `condition_status` integer NOT NULL,
  `rule_id` integer NOT NULL,
  `rule_status` integer NOT NULL,
  PRIMARY KEY (`condition_id`, `condition_status`),
  FOREIGN KEY (`condition_id`, `condition_status`)
    REFERENCES `nglayouts_rule_condition` (`id`, `status`),
  FOREIGN KEY (`rule_id`, `rule_status`)
    REFERENCES `nglayouts_rule` (`id`, `status`)
);

CREATE TABLE IF NOT EXISTS `nglayouts_rule_condition_rule_group` (
  `condition_id` integer NOT NULL,
  `condition_status` integer NOT NULL,
  `rule_group_id` integer NOT NULL,
  `rule_group_status` integer NOT NULL,
  PRIMARY KEY (`condition_id`, `condition_status`),
  FOREIGN KEY (`condition_id`, `condition_status`)
    REFERENCES `nglayouts_rule_condition` (`id`, `status`),
  FOREIGN KEY (`rule_group_id`, `rule_group_status`)
    REFERENCES `nglayouts_rule_group` (`id`, `status`)
);

CREATE TABLE IF NOT EXISTS `nglayouts_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` integer DEFAULT NULL,
  PRIMARY KEY (`version`)
);

CREATE TABLE IF NOT EXISTS `eztags` (
  `id` integer NOT NULL,
  `parent_id` integer NOT NULL DEFAULT 0,
  `main_tag_id` integer NOT NULL DEFAULT 0,
  `keyword` varchar(255) NOT NULL DEFAULT '',
  `depth` integer NOT NULL DEFAULT 1,
  `path_string` varchar(255) NOT NULL DEFAULT '',
  `modified` integer NOT NULL DEFAULT 0,
  `remote_id` varchar(100) NOT NULL DEFAULT '',
  `main_language_id` bigint NOT NULL DEFAULT 0,
  `language_mask` bigint NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `eztags_attribute_link` (
  `id` integer NOT NULL,
  `keyword_id` integer NOT NULL DEFAULT 0,
  `objectattribute_id` integer NOT NULL DEFAULT 0,
  `objectattribute_version` integer NOT NULL DEFAULT 0,
  `object_id` integer NOT NULL DEFAULT 0,
  `priority` integer NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `eztags_keyword` (
  `keyword_id` integer NOT NULL DEFAULT 0,
  `language_id` bigint NOT NULL DEFAULT 0,
  `keyword` varchar(255) NOT NULL DEFAULT '',
  `locale` varchar(20) NOT NULL DEFAULT '',
  `status` integer NOT NULL DEFAULT 0,
  PRIMARY KEY (`keyword_id`, `locale`)
);

CREATE TABLE IF NOT EXISTS `novaseo_meta` (
  `objectattribute_id` integer NOT NULL,
  `meta_name` text NOT NULL,
  `meta_content` text NOT NULL,
  `objectattribute_version` integer NOT NULL,
  PRIMARY KEY (`objectattribute_id`, `objectattribute_version`, `meta_name`)
);

CREATE TABLE IF NOT EXISTS `sckenhancedselection` (
  `contentobject_attribute_id` integer NOT NULL DEFAULT 0,
  `contentobject_attribute_version` integer NOT NULL DEFAULT 0,
  `identifier` varchar(255) NOT NULL DEFAULT ''
);

-- Legacy kernel tables the Ibexa 4.6 schema does not create
-- (copied from the legacy kernel's kernel/sql/sqlite/schema.sql): shop, workflow,
-- collaboration, notification, RSS, information collection, sessions and the like, which
-- the legacy admin and the legacy kernel use.

CREATE TABLE IF NOT EXISTS `ezapprove_items` (
  `collaboration_id` integer NOT NULL DEFAULT '0'
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `workflow_process_id` integer NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `ezbasket` (
  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `order_id` integer NOT NULL DEFAULT '0'
,  `productcollection_id` integer NOT NULL DEFAULT '0'
,  `session_id` varchar(255) NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS `ezcollab_group` (
  `created` integer NOT NULL DEFAULT '0'
,  `depth` integer NOT NULL DEFAULT '0'
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `is_open` integer NOT NULL DEFAULT '1'
,  `modified` integer NOT NULL DEFAULT '0'
,  `parent_group_id` integer NOT NULL DEFAULT '0'
,  `path_string` varchar(255) NOT NULL DEFAULT ''
,  `priority` integer NOT NULL DEFAULT '0'
,  `title` varchar(255) NOT NULL DEFAULT ''
,  `user_id` integer NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `ezcollab_item` (
  `created` integer NOT NULL DEFAULT '0'
,  `creator_id` integer NOT NULL DEFAULT '0'
,  `data_float1` float NOT NULL DEFAULT '0'
,  `data_float2` float NOT NULL DEFAULT '0'
,  `data_float3` float NOT NULL DEFAULT '0'
,  `data_int1` integer NOT NULL DEFAULT '0'
,  `data_int2` integer NOT NULL DEFAULT '0'
,  `data_int3` integer NOT NULL DEFAULT '0'
,  `data_text1` longtext NOT NULL
,  `data_text2` longtext NOT NULL
,  `data_text3` longtext NOT NULL
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `modified` integer NOT NULL DEFAULT '0'
,  `status` integer NOT NULL DEFAULT '1'
,  `type_identifier` varchar(40) NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS `ezcollab_item_group_link` (
  `collaboration_id` integer NOT NULL DEFAULT '0'
,  `created` integer NOT NULL DEFAULT '0'
,  `group_id` integer NOT NULL DEFAULT '0'
,  `is_active` integer NOT NULL DEFAULT '1'
,  `is_read` integer NOT NULL DEFAULT '0'
,  `last_read` integer NOT NULL DEFAULT '0'
,  `modified` integer NOT NULL DEFAULT '0'
,  `user_id` integer NOT NULL DEFAULT '0'
,  PRIMARY KEY (`collaboration_id`,`group_id`,`user_id`)
);

CREATE TABLE IF NOT EXISTS `ezcollab_item_message_link` (
  `collaboration_id` integer NOT NULL DEFAULT '0'
,  `created` integer NOT NULL DEFAULT '0'
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `message_id` integer NOT NULL DEFAULT '0'
,  `message_type` integer NOT NULL DEFAULT '0'
,  `modified` integer NOT NULL DEFAULT '0'
,  `participant_id` integer NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `ezcollab_item_participant_link` (
  `collaboration_id` integer NOT NULL DEFAULT '0'
,  `created` integer NOT NULL DEFAULT '0'
,  `is_active` integer NOT NULL DEFAULT '1'
,  `is_read` integer NOT NULL DEFAULT '0'
,  `last_read` integer NOT NULL DEFAULT '0'
,  `modified` integer NOT NULL DEFAULT '0'
,  `participant_id` integer NOT NULL DEFAULT '0'
,  `participant_role` integer NOT NULL DEFAULT '1'
,  `participant_type` integer NOT NULL DEFAULT '1'
,  PRIMARY KEY (`collaboration_id`,`participant_id`)
);

CREATE TABLE IF NOT EXISTS `ezcollab_item_status` (
  `collaboration_id` integer NOT NULL DEFAULT '0'
,  `is_active` integer NOT NULL DEFAULT '1'
,  `is_read` integer NOT NULL DEFAULT '0'
,  `last_read` integer NOT NULL DEFAULT '0'
,  `user_id` integer NOT NULL DEFAULT '0'
,  PRIMARY KEY (`collaboration_id`,`user_id`)
);

CREATE TABLE IF NOT EXISTS `ezcollab_notification_rule` (
  `collab_identifier` varchar(255) NOT NULL DEFAULT ''
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `user_id` varchar(255) NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS `ezcollab_profile` (
  `created` integer NOT NULL DEFAULT '0'
,  `data_text1` longtext NOT NULL
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `main_group` integer NOT NULL DEFAULT '0'
,  `modified` integer NOT NULL DEFAULT '0'
,  `user_id` integer NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `ezcollab_simple_message` (
  `created` integer NOT NULL DEFAULT '0'
,  `creator_id` integer NOT NULL DEFAULT '0'
,  `data_float1` float NOT NULL DEFAULT '0'
,  `data_float2` float NOT NULL DEFAULT '0'
,  `data_float3` float NOT NULL DEFAULT '0'
,  `data_int1` integer NOT NULL DEFAULT '0'
,  `data_int2` integer NOT NULL DEFAULT '0'
,  `data_int3` integer NOT NULL DEFAULT '0'
,  `data_text1` longtext NOT NULL
,  `data_text2` longtext NOT NULL
,  `data_text3` longtext NOT NULL
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `message_type` varchar(40) NOT NULL DEFAULT ''
,  `modified` integer NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `ezcontentbrowserecent` (
  `created` integer NOT NULL DEFAULT '0'
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `name` varchar(255) NOT NULL DEFAULT ''
,  `node_id` integer NOT NULL DEFAULT '0'
,  `user_id` integer NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `ezcurrencydata` (
  `auto_rate_value` decimal(10,5) NOT NULL DEFAULT '0.00000'
,  `code` varchar(4) NOT NULL DEFAULT ''
,  `custom_rate_value` decimal(10,5) NOT NULL DEFAULT '0.00000'
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `locale` varchar(255) NOT NULL DEFAULT ''
,  `rate_factor` decimal(10,5) NOT NULL DEFAULT '1.00000'
,  `status` integer NOT NULL DEFAULT '1'
,  `symbol` varchar(255) NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS `ezdiscountrule` (
  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `name` varchar(255) NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS `ezdiscountsubrule` (
  `discount_percent` float DEFAULT NULL
,  `discountrule_id` integer NOT NULL DEFAULT '0'
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `limitation` char(1) DEFAULT NULL
,  `name` varchar(255) NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS `ezdiscountsubrule_value` (
  `discountsubrule_id` integer NOT NULL DEFAULT '0'
,  `issection` integer NOT NULL DEFAULT '0'
,  `value` integer NOT NULL DEFAULT '0'
,  PRIMARY KEY (`discountsubrule_id`,`value`,`issection`)
);

CREATE TABLE IF NOT EXISTS `ezenumobjectvalue` (
  `contentobject_attribute_id` integer NOT NULL DEFAULT '0'
,  `contentobject_attribute_version` integer NOT NULL DEFAULT '0'
,  `text` NOT NULL DEFAULT ''
,  `enumid` integer NOT NULL DEFAULT '0'
,  PRIMARY KEY (`contentobject_attribute_id`,`contentobject_attribute_version`,`text`)
);

CREATE TABLE IF NOT EXISTS `ezenumvalue` (
  `contentclass_attribute_id` integer NOT NULL DEFAULT '0'
,  `contentclass_attribute_version` integer NOT NULL DEFAULT '0'
,  `text` NOT NULL DEFAULT ''
,  `id` integer NOT NULL PRIMARY KEY
,  `placement` integer NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `ezforgot_password` (
  `hash_key` varchar(32) NOT NULL DEFAULT ''
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `time` integer NOT NULL DEFAULT '0'
,  `user_id` integer NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `ezgeneral_digest_user_settings` (
  `day` varchar(255) NOT NULL DEFAULT ''
,  `digest_type` integer NOT NULL DEFAULT '0'
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `receive_digest` integer NOT NULL DEFAULT '0'
,  `time` varchar(255) NOT NULL DEFAULT ''
,  `user_id` integer NOT NULL DEFAULT '0'
,  UNIQUE (`user_id`)
);

CREATE TABLE IF NOT EXISTS `ezinfocollection` (
  `contentobject_id` integer NOT NULL DEFAULT '0'
,  `created` integer NOT NULL DEFAULT '0'
,  `creator_id` integer NOT NULL DEFAULT '0'
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `modified` integer DEFAULT '0'
,  `user_identifier` varchar(34) DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS `ezinfocollection_attribute` (
  `contentclass_attribute_id` integer NOT NULL DEFAULT '0'
,  `contentobject_attribute_id` integer DEFAULT NULL
,  `contentobject_id` integer DEFAULT NULL
,  `data_float` float DEFAULT NULL
,  `data_int` integer DEFAULT NULL
,  `data_text` longtext
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `informationcollection_id` integer NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `ezisbn_group` (
  `description` varchar(255) NOT NULL DEFAULT ''
,  `group_number` integer NOT NULL DEFAULT '0'
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
);

CREATE TABLE IF NOT EXISTS `ezisbn_group_range` (
  `from_number` integer NOT NULL DEFAULT '0'
,  `group_from` varchar(32) NOT NULL DEFAULT ''
,  `group_length` integer NOT NULL DEFAULT '0'
,  `group_to` varchar(32) NOT NULL DEFAULT ''
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `to_number` integer NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `ezisbn_registrant_range` (
  `from_number` integer NOT NULL DEFAULT '0'
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `isbn_group_id` integer NOT NULL DEFAULT '0'
,  `registrant_from` varchar(32) NOT NULL DEFAULT ''
,  `registrant_length` integer NOT NULL DEFAULT '0'
,  `registrant_to` varchar(32) NOT NULL DEFAULT ''
,  `to_number` integer NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `ezmessage` (
  `body` longtext
,  `destination_address` varchar(50) NOT NULL DEFAULT ''
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `is_sent` integer NOT NULL DEFAULT '0'
,  `send_method` varchar(50) NOT NULL DEFAULT ''
,  `send_time` varchar(50) NOT NULL DEFAULT ''
,  `send_weekday` varchar(50) NOT NULL DEFAULT ''
,  `title` varchar(255) NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS `ezmodule_run` (
  `function_name` varchar(255) DEFAULT NULL
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `module_data` longtext
,  `module_name` varchar(255) DEFAULT NULL
,  `workflow_process_id` integer DEFAULT NULL
,  UNIQUE (`workflow_process_id`)
);

CREATE TABLE IF NOT EXISTS `ezmultipricedata` (
  `contentobject_attr_id` integer NOT NULL DEFAULT '0'
,  `contentobject_attr_version` integer NOT NULL DEFAULT '0'
,  `currency_code` varchar(4) NOT NULL DEFAULT ''
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `type` integer NOT NULL DEFAULT '0'
,  `value` decimal(15,2) NOT NULL DEFAULT '0.00'
);

CREATE TABLE IF NOT EXISTS `eznotificationcollection` (
  `data_subject` longtext NOT NULL
,  `data_text` longtext NOT NULL
,  `event_id` integer NOT NULL DEFAULT '0'
,  `handler` varchar(255) NOT NULL DEFAULT ''
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `transport` varchar(255) NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS `eznotificationcollection_item` (
  `address` varchar(255) NOT NULL DEFAULT ''
,  `collection_id` integer NOT NULL DEFAULT '0'
,  `event_id` integer NOT NULL DEFAULT '0'
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `send_date` integer NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `eznotificationevent` (
  `data_int1` integer NOT NULL DEFAULT '0'
,  `data_int2` integer NOT NULL DEFAULT '0'
,  `data_int3` integer NOT NULL DEFAULT '0'
,  `data_int4` integer NOT NULL DEFAULT '0'
,  `data_text1` longtext NOT NULL
,  `data_text2` longtext NOT NULL
,  `data_text3` longtext NOT NULL
,  `data_text4` longtext NOT NULL
,  `event_type_string` varchar(255) NOT NULL DEFAULT ''
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `status` integer NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `ezoperation_memento` (
  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `main` integer NOT NULL DEFAULT '0'
,  `main_key` varchar(32) NOT NULL DEFAULT ''
,  `memento_data` longtext NOT NULL
,  `memento_key` varchar(32) NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS `ezorder` (
  `account_identifier` varchar(100) NOT NULL DEFAULT 'default'
,  `created` integer NOT NULL DEFAULT '0'
,  `data_text_1` longtext
,  `data_text_2` longtext
,  `email` varchar(150) DEFAULT ''
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `ignore_vat` integer NOT NULL DEFAULT '0'
,  `is_archived` integer NOT NULL DEFAULT '0'
,  `is_temporary` integer NOT NULL DEFAULT '1'
,  `order_nr` integer NOT NULL DEFAULT '0'
,  `productcollection_id` integer NOT NULL DEFAULT '0'
,  `status_id` integer DEFAULT '0'
,  `status_modified` integer DEFAULT '0'
,  `status_modifier_id` integer DEFAULT '0'
,  `user_id` integer NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `ezorder_item` (
  `description` varchar(255) DEFAULT NULL
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `is_vat_inc` integer NOT NULL DEFAULT '0'
,  `order_id` integer NOT NULL DEFAULT '0'
,  `price` float DEFAULT NULL
,  `type` varchar(30) DEFAULT NULL
,  `vat_value` float NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `ezorder_nr_incr` (
  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
);

CREATE TABLE IF NOT EXISTS `ezorder_status` (
  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `is_active` integer NOT NULL DEFAULT '1'
,  `name` varchar(255) NOT NULL DEFAULT ''
,  `status_id` integer NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `ezorder_status_history` (
  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `modified` integer NOT NULL DEFAULT '0'
,  `modifier_id` integer NOT NULL DEFAULT '0'
,  `order_id` integer NOT NULL DEFAULT '0'
,  `status_id` integer NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `ezpaymentobject` (
  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `order_id` integer NOT NULL DEFAULT '0'
,  `payment_string` varchar(255) NOT NULL DEFAULT ''
,  `status` integer NOT NULL DEFAULT '0'
,  `workflowprocess_id` integer NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `ezpdf_export` (
  `created` integer DEFAULT NULL
,  `creator_id` integer DEFAULT NULL
,  `export_classes` varchar(255) DEFAULT NULL
,  `export_structure` varchar(255) DEFAULT NULL
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `intro_text` longtext
,  `modified` integer DEFAULT NULL
,  `modifier_id` integer DEFAULT NULL
,  `pdf_filename` varchar(255) DEFAULT NULL
,  `show_frontpage` integer DEFAULT NULL
,  `site_access` varchar(255) DEFAULT NULL
,  `source_node_id` integer DEFAULT NULL
,  `status` integer DEFAULT NULL
,  `sub_text` longtext
,  `title` varchar(255) DEFAULT NULL
,  `version` integer NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `ezpending_actions` (
  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `action` varchar(64) NOT NULL DEFAULT ''
,  `created` integer DEFAULT NULL
,  `param` longtext
);

CREATE TABLE IF NOT EXISTS `ezprest_authcode` (
  `client_id` varchar(200) NOT NULL DEFAULT ''
,  `expirytime` integer NOT NULL DEFAULT '0'
,  `id` varchar(200) NOT NULL DEFAULT ''
,  `scope` varchar(200) DEFAULT NULL
,  `user_id` integer NOT NULL DEFAULT '0'
,  PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `ezprest_authorized_clients` (
  `created` integer DEFAULT NULL
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `rest_client_id` integer DEFAULT NULL
,  `user_id` integer DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS `ezprest_clients` (
  `client_id` varchar(200) DEFAULT NULL
,  `client_secret` varchar(200) DEFAULT NULL
,  `created` integer NOT NULL DEFAULT '0'
,  `description` longtext
,  `endpoint_uri` varchar(200) DEFAULT NULL
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `name` varchar(100) DEFAULT NULL
,  `owner_id` integer NOT NULL DEFAULT '0'
,  `updated` integer NOT NULL DEFAULT '0'
,  `version` integer NOT NULL DEFAULT '0'
,  UNIQUE (`client_id`,`version`)
);

CREATE TABLE IF NOT EXISTS `ezprest_token` (
  `client_id` varchar(200) NOT NULL DEFAULT ''
,  `expirytime` integer NOT NULL DEFAULT '0'
,  `id` varchar(200) NOT NULL DEFAULT ''
,  `refresh_token` varchar(200) NOT NULL DEFAULT ''
,  `scope` varchar(200) DEFAULT NULL
,  `user_id` integer NOT NULL DEFAULT '0'
,  PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `ezproductcategory` (
  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `name` varchar(255) NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS `ezproductcollection` (
  `created` integer DEFAULT NULL
,  `currency_code` varchar(4) NOT NULL DEFAULT ''
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
);

CREATE TABLE IF NOT EXISTS `ezproductcollection_item` (
  `contentobject_id` integer NOT NULL DEFAULT '0'
,  `discount` float DEFAULT NULL
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `is_vat_inc` integer DEFAULT NULL
,  `item_count` integer NOT NULL DEFAULT '0'
,  `name` varchar(255) NOT NULL DEFAULT ''
,  `price` float DEFAULT '0'
,  `productcollection_id` integer NOT NULL DEFAULT '0'
,  `vat_value` float DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS `ezproductcollection_item_opt` (
  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `item_id` integer NOT NULL DEFAULT '0'
,  `name` varchar(255) NOT NULL DEFAULT ''
,  `object_attribute_id` integer DEFAULT NULL
,  `option_item_id` integer NOT NULL DEFAULT '0'
,  `price` float NOT NULL DEFAULT '0'
,  `value` varchar(255) NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS `ezpublishingqueueprocesses` (
  `created` integer DEFAULT NULL
,  `ezcontentobject_version_id` integer NOT NULL DEFAULT '0'
,  `finished` integer DEFAULT NULL
,  `pid` integer DEFAULT NULL
,  `started` integer DEFAULT NULL
,  `status` integer DEFAULT NULL
,  PRIMARY KEY (`ezcontentobject_version_id`)
);

CREATE TABLE IF NOT EXISTS `ezrss_export` (
  `access_url` varchar(255) DEFAULT NULL
,  `active` integer DEFAULT NULL
,  `created` integer DEFAULT NULL
,  `creator_id` integer DEFAULT NULL
,  `description` longtext
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `image_id` integer DEFAULT NULL
,  `main_node_only` integer NOT NULL DEFAULT '1'
,  `modified` integer DEFAULT NULL
,  `modifier_id` integer DEFAULT NULL
,  `node_id` integer DEFAULT NULL
,  `number_of_objects` integer NOT NULL DEFAULT '0'
,  `rss_version` varchar(255) DEFAULT NULL
,  `site_access` varchar(255) DEFAULT NULL
,  `status` integer NOT NULL DEFAULT '0'
,  `title` varchar(255) DEFAULT NULL
,  `url` varchar(255) DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS `ezrss_export_item` (
  `category` varchar(255) DEFAULT NULL
,  `class_id` integer DEFAULT NULL
,  `description` varchar(255) DEFAULT NULL
,  `enclosure` varchar(255) DEFAULT NULL
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `rssexport_id` integer DEFAULT NULL
,  `source_node_id` integer DEFAULT NULL
,  `status` integer NOT NULL DEFAULT '0'
,  `subnodes` integer NOT NULL DEFAULT '0'
,  `title` varchar(255) DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS `ezrss_import` (
  `active` integer DEFAULT NULL
,  `class_description` varchar(255) DEFAULT NULL
,  `class_id` integer DEFAULT NULL
,  `class_title` varchar(255) DEFAULT NULL
,  `class_url` varchar(255) DEFAULT NULL
,  `created` integer DEFAULT NULL
,  `creator_id` integer DEFAULT NULL
,  `destination_node_id` integer DEFAULT NULL
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `import_description` longtext NOT NULL
,  `modified` integer DEFAULT NULL
,  `modifier_id` integer DEFAULT NULL
,  `name` varchar(255) DEFAULT NULL
,  `object_owner_id` integer DEFAULT NULL
,  `status` integer NOT NULL DEFAULT '0'
,  `url` longtext
);

CREATE TABLE IF NOT EXISTS `ezscheduled_script` (
  `command` varchar(255) NOT NULL DEFAULT ''
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `last_report_timestamp` integer NOT NULL DEFAULT '0'
,  `name` varchar(50) NOT NULL DEFAULT ''
,  `process_id` integer NOT NULL DEFAULT '0'
,  `progress` integer DEFAULT '0'
,  `user_id` integer NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `ezsearch_search_phrase` (
  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `phrase` varchar(250) DEFAULT NULL
,  `phrase_count` integer DEFAULT '0'
,  `result_count` integer DEFAULT '0'
,  UNIQUE (`phrase`)
);

CREATE TABLE IF NOT EXISTS `ezsession` (
  `data` longtext NOT NULL
,  `expiration_time` integer NOT NULL DEFAULT '0'
,  `session_key` varchar(32) NOT NULL DEFAULT ''
,  `user_hash` varchar(32) NOT NULL DEFAULT ''
,  `user_id` integer NOT NULL DEFAULT '0'
,  PRIMARY KEY (`session_key`)
);

CREATE TABLE IF NOT EXISTS `ezstarrating` (
  `contentobject_id` integer NOT NULL
,  `contentobject_attribute_id` integer NOT NULL
,  `rating_average` float NOT NULL
,  `rating_count` integer NOT NULL
,  PRIMARY KEY (`contentobject_id`,`contentobject_attribute_id`)
);

CREATE TABLE IF NOT EXISTS `ezstarrating_data` (
  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `created_at` integer NOT NULL
,  `user_id` integer NOT NULL
,  `session_key` varchar(32) NOT NULL
,  `rating` float NOT NULL
,  `contentobject_id` integer NOT NULL
,  `contentobject_attribute_id` integer NOT NULL
);

CREATE TABLE IF NOT EXISTS `ezsubtree_notification_rule` (
  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `node_id` integer NOT NULL DEFAULT '0'
,  `use_digest` integer DEFAULT '0'
,  `user_id` integer NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `eztipafriend_counter` (
  `count` integer NOT NULL DEFAULT '0'
,  `node_id` integer NOT NULL DEFAULT '0'
,  `requested` integer NOT NULL DEFAULT '0'
,  PRIMARY KEY (`node_id`,`requested`)
);

CREATE TABLE IF NOT EXISTS `eztipafriend_request` (
  `created` integer NOT NULL DEFAULT '0'
,  `email_receiver` varchar(100) NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS `eztrigger` (
  `connect_type` char(1) NOT NULL DEFAULT ''
,  `function_name` varchar(200) NOT NULL DEFAULT ''
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `module_name` varchar(200) NOT NULL DEFAULT ''
,  `name` varchar(255) DEFAULT NULL
,  `workflow_id` integer DEFAULT NULL
,  UNIQUE (`module_name`,`function_name`,`connect_type`)
);

CREATE TABLE IF NOT EXISTS `ezuser_discountrule` (
  `contentobject_id` integer DEFAULT NULL
,  `discountrule_id` integer DEFAULT NULL
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `name` varchar(255) NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS `ezuservisit` (
  `current_visit_timestamp` integer NOT NULL DEFAULT '0'
,  `failed_login_attempts` integer NOT NULL DEFAULT '0'
,  `last_visit_timestamp` integer NOT NULL DEFAULT '0'
,  `login_count` integer NOT NULL DEFAULT '0'
,  `user_id` integer NOT NULL DEFAULT '0'
,  PRIMARY KEY (`user_id`)
);

CREATE TABLE IF NOT EXISTS `ezvatrule` (
  `country_code` varchar(255) NOT NULL DEFAULT ''
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `vat_type` integer NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `ezvatrule_product_category` (
  `product_category_id` integer NOT NULL DEFAULT '0'
,  `vatrule_id` integer NOT NULL DEFAULT '0'
,  PRIMARY KEY (`vatrule_id`,`product_category_id`)
);

CREATE TABLE IF NOT EXISTS `ezvattype` (
  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `name` varchar(255) NOT NULL DEFAULT ''
,  `percentage` float DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS `ezview_counter` (
  `count` integer NOT NULL DEFAULT '0'
,  `node_id` integer NOT NULL DEFAULT '0'
,  PRIMARY KEY (`node_id`)
);

CREATE TABLE IF NOT EXISTS `ezwaituntildatevalue` (
  `contentclass_attribute_id` integer NOT NULL DEFAULT '0'
,  `contentclass_id` integer NOT NULL DEFAULT '0'
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `workflow_event_id` integer NOT NULL DEFAULT '0'
,  `workflow_event_version` integer NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `ezwishlist` (
  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `productcollection_id` integer NOT NULL DEFAULT '0'
,  `user_id` integer NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `ezworkflow` (
  `created` integer NOT NULL DEFAULT '0'
,  `creator_id` integer NOT NULL DEFAULT '0'
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `is_enabled` integer NOT NULL DEFAULT '0'
,  `modified` integer NOT NULL DEFAULT '0'
,  `modifier_id` integer NOT NULL DEFAULT '0'
,  `name` varchar(255) NOT NULL DEFAULT ''
,  `version` integer NOT NULL DEFAULT '0'
,  `workflow_type_string` varchar(50) NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS `ezworkflow_assign` (
  `access_type` integer NOT NULL DEFAULT '0'
,  `as_tree` integer NOT NULL DEFAULT '0'
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `node_id` integer NOT NULL DEFAULT '0'
,  `workflow_id` integer NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `ezworkflow_event` (
  `data_int1` integer DEFAULT NULL
,  `data_int2` integer DEFAULT NULL
,  `data_int3` integer DEFAULT NULL
,  `data_int4` integer DEFAULT NULL
,  `data_text1` varchar(255) DEFAULT NULL
,  `data_text2` varchar(255) DEFAULT NULL
,  `data_text3` varchar(255) DEFAULT NULL
,  `data_text4` varchar(255) DEFAULT NULL
,  `data_text5` longtext
,  `description` varchar(50) NOT NULL DEFAULT ''
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `placement` integer NOT NULL DEFAULT '0'
,  `version` integer NOT NULL DEFAULT '0'
,  `workflow_id` integer NOT NULL DEFAULT '0'
,  `workflow_type_string` varchar(50) NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS `ezworkflow_group` (
  `created` integer NOT NULL DEFAULT '0'
,  `creator_id` integer NOT NULL DEFAULT '0'
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `modified` integer NOT NULL DEFAULT '0'
,  `modifier_id` integer NOT NULL DEFAULT '0'
,  `name` varchar(255) NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS `ezworkflow_group_link` (
  `group_id` integer NOT NULL DEFAULT '0'
,  `group_name` varchar(255) DEFAULT NULL
,  `workflow_id` integer NOT NULL DEFAULT '0'
,  `workflow_version` integer NOT NULL DEFAULT '0'
,  PRIMARY KEY (`workflow_id`,`group_id`,`workflow_version`)
);

CREATE TABLE IF NOT EXISTS `ezworkflow_process` (
  `activation_date` integer DEFAULT NULL
,  `content_id` integer NOT NULL DEFAULT '0'
,  `content_version` integer NOT NULL DEFAULT '0'
,  `created` integer NOT NULL DEFAULT '0'
,  `event_id` integer NOT NULL DEFAULT '0'
,  `event_position` integer NOT NULL DEFAULT '0'
,  `event_state` integer DEFAULT NULL
,  `event_status` integer NOT NULL DEFAULT '0'
,  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `last_event_id` integer NOT NULL DEFAULT '0'
,  `last_event_position` integer NOT NULL DEFAULT '0'
,  `last_event_status` integer NOT NULL DEFAULT '0'
,  `memento_key` varchar(32) DEFAULT NULL
,  `modified` integer NOT NULL DEFAULT '0'
,  `node_id` integer NOT NULL DEFAULT '0'
,  `parameters` longtext
,  `process_key` varchar(32) NOT NULL DEFAULT ''
,  `session_key` varchar(32) NOT NULL DEFAULT '0'
,  `status` integer DEFAULT NULL
,  `user_id` integer NOT NULL DEFAULT '0'
,  `workflow_id` integer NOT NULL DEFAULT '0'
);

CREATE INDEX IF NOT EXISTS "idx_eztipafriend_request_eztipafriend_request_created" ON "eztipafriend_request" (`created`);

CREATE INDEX IF NOT EXISTS "idx_eztipafriend_request_eztipafriend_request_email_rec" ON "eztipafriend_request" (`email_receiver`);

CREATE INDEX IF NOT EXISTS "idx_ezwaituntildatevalue_ezwaituntildateevalue_wf_ev_id_wf_ver" ON "ezwaituntildatevalue" (`workflow_event_id`,`workflow_event_version`);

CREATE INDEX IF NOT EXISTS "idx_ezorder_ezorder_is_archived" ON "ezorder" (`is_archived`);

CREATE INDEX IF NOT EXISTS "idx_ezorder_ezorder_is_tmp" ON "ezorder" (`is_temporary`);

CREATE INDEX IF NOT EXISTS "idx_ezbasket_ezbasket_session_id" ON "ezbasket" (`session_id`);

CREATE INDEX IF NOT EXISTS "idx_ezorder_status_ezorder_status_active" ON "ezorder_status" (`is_active`);

CREATE INDEX IF NOT EXISTS "idx_ezorder_status_ezorder_status_name" ON "ezorder_status" (`name`);

CREATE INDEX IF NOT EXISTS "idx_ezorder_status_ezorder_status_sid" ON "ezorder_status" (`status_id`);

CREATE INDEX IF NOT EXISTS "idx_eztrigger_eztrigger_fetch" ON "eztrigger" (`name`,`module_name`,`function_name`);

CREATE INDEX IF NOT EXISTS "idx_ezuservisit_ezuservisit_co_visit_count" ON "ezuservisit" (`current_visit_timestamp`,`login_count`);

CREATE INDEX IF NOT EXISTS "idx_ezcollab_group_ezcollab_group_depth" ON "ezcollab_group" (`depth`);

CREATE INDEX IF NOT EXISTS "idx_ezcollab_group_ezcollab_group_path" ON "ezcollab_group" (`path_string`);

CREATE INDEX IF NOT EXISTS "idx_ezpending_actions_ezpending_actions_action" ON "ezpending_actions" (`action`);

CREATE INDEX IF NOT EXISTS "idx_ezpending_actions_ezpending_actions_created" ON "ezpending_actions" (`created`);

CREATE INDEX IF NOT EXISTS "idx_ezforgot_password_ezforgot_password_user" ON "ezforgot_password" (`user_id`);

CREATE INDEX IF NOT EXISTS "idx_ezinfocollection_ezinfocollection_co_id_created" ON "ezinfocollection" (`contentobject_id`,`created`);

CREATE INDEX IF NOT EXISTS "idx_ezorder_item_ezorder_item_order_id" ON "ezorder_item" (`order_id`);

CREATE INDEX IF NOT EXISTS "idx_ezorder_item_ezorder_item_type" ON "ezorder_item" (`type`);

CREATE INDEX IF NOT EXISTS "idx_ezcontentbrowserecent_ezcontentbrowserecent_user" ON "ezcontentbrowserecent" (`user_id`);

CREATE INDEX IF NOT EXISTS "idx_ezproductcollection_item_opt_ezproductcollection_item_opt_item_id" ON "ezproductcollection_item_opt" (`item_id`);

CREATE INDEX IF NOT EXISTS "idx_ezproductcollection_item_ezproductcollection_item_contentobject_id" ON "ezproductcollection_item" (`contentobject_id`);

CREATE INDEX IF NOT EXISTS "idx_ezproductcollection_item_ezproductcollection_item_productcollection_id" ON "ezproductcollection_item" (`productcollection_id`);

CREATE INDEX IF NOT EXISTS "idx_ezinfocollection_attribute_ezinfocollection_attr_cca_id" ON "ezinfocollection_attribute" (`contentclass_attribute_id`);

CREATE INDEX IF NOT EXISTS "idx_ezinfocollection_attribute_ezinfocollection_attr_co_id" ON "ezinfocollection_attribute" (`contentobject_id`);

CREATE INDEX IF NOT EXISTS "idx_ezinfocollection_attribute_ezinfocollection_attr_coa_id" ON "ezinfocollection_attribute" (`contentobject_attribute_id`);

CREATE INDEX IF NOT EXISTS "idx_ezinfocollection_attribute_ezinfocollection_attr_ic_id" ON "ezinfocollection_attribute" (`informationcollection_id`);

CREATE INDEX IF NOT EXISTS "idx_ezprest_authorized_clients_client_user" ON "ezprest_authorized_clients" (`rest_client_id`,`user_id`);

CREATE INDEX IF NOT EXISTS "idx_ezmultipricedata_ezmultipricedata_coa_id" ON "ezmultipricedata" (`contentobject_attr_id`);

CREATE INDEX IF NOT EXISTS "idx_ezmultipricedata_ezmultipricedata_coa_version" ON "ezmultipricedata" (`contentobject_attr_version`);

CREATE INDEX IF NOT EXISTS "idx_ezmultipricedata_ezmultipricedata_currency_code" ON "ezmultipricedata" (`currency_code`);

CREATE INDEX IF NOT EXISTS "idx_ezcurrencydata_ezcurrencydata_code" ON "ezcurrencydata" (`code`);

CREATE INDEX IF NOT EXISTS "idx_ezworkflow_process_ezworkflow_process_process_key" ON "ezworkflow_process" (`process_key`);

CREATE INDEX IF NOT EXISTS "idx_ezprest_authcode_authcode_client_id" ON "ezprest_authcode" (`client_id`);

CREATE INDEX IF NOT EXISTS "idx_ezstarrating_data_user_id_session_key" ON "ezstarrating_data" (`user_id`,`session_key`);

CREATE INDEX IF NOT EXISTS "idx_ezstarrating_data_contentobject_id_contentobject_attribute_id" ON "ezstarrating_data" (`contentobject_id`,`contentobject_attribute_id`);

CREATE INDEX IF NOT EXISTS "idx_ezscheduled_script_ezscheduled_script_timestamp" ON "ezscheduled_script" (`last_report_timestamp`);

CREATE INDEX IF NOT EXISTS "idx_ezoperation_memento_ezoperation_memento_memento_key_main" ON "ezoperation_memento" (`memento_key`,`main`);

CREATE INDEX IF NOT EXISTS "idx_ezenumvalue_ezenumvalue_co_cl_attr_id_co_class_att_ver" ON "ezenumvalue" (`contentclass_attribute_id`,`contentclass_attribute_version`);

CREATE INDEX IF NOT EXISTS "idx_ezrss_export_item_ezrss_export_rsseid" ON "ezrss_export_item" (`rssexport_id`);

CREATE INDEX IF NOT EXISTS "idx_ezworkflow_event_wid_version_placement" ON "ezworkflow_event" (`workflow_id`,`version`,`placement`);

CREATE INDEX IF NOT EXISTS "idx_ezprest_token_token_client_id" ON "ezprest_token" (`client_id`);

CREATE INDEX IF NOT EXISTS "idx_ezorder_status_history_ezorder_status_history_mod" ON "ezorder_status_history" (`modified`);

CREATE INDEX IF NOT EXISTS "idx_ezorder_status_history_ezorder_status_history_oid" ON "ezorder_status_history" (`order_id`);

CREATE INDEX IF NOT EXISTS "idx_ezorder_status_history_ezorder_status_history_sid" ON "ezorder_status_history" (`status_id`);

CREATE INDEX IF NOT EXISTS "idx_ezsubtree_notification_rule_ezsubtree_notification_rule_user_id" ON "ezsubtree_notification_rule" (`user_id`);

CREATE INDEX IF NOT EXISTS "idx_ezsearch_search_phrase_ezsearch_search_phrase_count" ON "ezsearch_search_phrase" (`phrase_count`);

CREATE INDEX IF NOT EXISTS "idx_ezsession_expiration_time" ON "ezsession" (`expiration_time`);

CREATE INDEX IF NOT EXISTS "idx_ezsession_ezsession_user_id" ON "ezsession" (`user_id`);

