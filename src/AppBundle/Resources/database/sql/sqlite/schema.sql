-- SQLite schema for exponential-cjw installer (all tables)
-- Auto-generated from schema/schema.sql by mysql_to_sqlite_cjw.py
-- Do not edit manually.

PRAGMA foreign_keys = OFF;

-- cjwnl_blacklist_item
CREATE TABLE IF NOT EXISTS "cjwnl_blacklist_item" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "email_hash" TEXT DEFAULT NULL,
  "email" TEXT DEFAULT NULL,
  "newsletter_user_id" INTEGER NOT NULL,
  "created" INTEGER DEFAULT NULL,
  "creator_contentobject_id" INTEGER DEFAULT NULL,
  "note" TEXT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS "cjwnewsletter_user_id" ON "cjwnl_blacklist_item" ("newsletter_user_id");

-- cjwnl_edition
CREATE TABLE IF NOT EXISTS "cjwnl_edition" (
  "contentobject_attribute_id" INTEGER NOT NULL,
  "contentobject_attribute_version" INTEGER NOT NULL,
  "contentobject_id" INTEGER NOT NULL,
  "contentclass_id" INTEGER NOT NULL,
  PRIMARY KEY ("contentobject_attribute_id","contentobject_attribute_version")
);
CREATE INDEX IF NOT EXISTS "contentobject_id" ON "cjwnl_edition" ("contentobject_id");
CREATE INDEX IF NOT EXISTS "contentobject_attribute_id" ON "cjwnl_edition" ("contentobject_attribute_id");
CREATE INDEX IF NOT EXISTS "contentobject_attribute_version" ON "cjwnl_edition" ("contentobject_attribute_version");

-- cjwnl_edition_send
CREATE TABLE IF NOT EXISTS "cjwnl_edition_send" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "list_contentobject_id" INTEGER NOT NULL,
  "list_contentobject_version" INTEGER NOT NULL DEFAULT 0,
  "list_is_virtual" INTEGER NOT NULL DEFAULT 0,
  "edition_contentobject_id" INTEGER NOT NULL,
  "edition_contentobject_version" INTEGER NOT NULL,
  "created" INTEGER NOT NULL,
  "status" INTEGER NOT NULL DEFAULT 0,
  "siteaccess" TEXT NOT NULL,
  "output_format_array_string" TEXT NOT NULL,
  "creator_id" INTEGER NOT NULL,
  "mailqueue_created" INTEGER NOT NULL,
  "mailqueue_process_scheduled" INTEGER DEFAULT NULL,
  "mailqueue_process_started" INTEGER NOT NULL,
  "mailqueue_process_finished" INTEGER NOT NULL,
  "mailqueue_process_aborted" INTEGER NOT NULL,
  "output_xml" TEXT NOT NULL,
  "hash" TEXT NOT NULL,
  "email_sender" TEXT NOT NULL,
  "email_reply_to" TEXT NOT NULL,
  "email_return_path" TEXT NOT NULL,
  "email_sender_name" TEXT NOT NULL,
  "personalize_content" INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS "edition_contentobject_id" ON "cjwnl_edition_send" ("edition_contentobject_id");
CREATE INDEX IF NOT EXISTS "edition_contentobject_version" ON "cjwnl_edition_send" ("edition_contentobject_version");
CREATE INDEX IF NOT EXISTS "list_contentobject_id" ON "cjwnl_edition_send" ("list_contentobject_id");

-- cjwnl_edition_send_item
CREATE TABLE IF NOT EXISTS "cjwnl_edition_send_item" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "edition_send_id" INTEGER NOT NULL,
  "newsletter_user_id" INTEGER NOT NULL,
  "output_format_id" INTEGER NOT NULL DEFAULT 0,
  "subscription_id" INTEGER NOT NULL,
  "created" INTEGER NOT NULL,
  "processed" INTEGER NOT NULL,
  "status" INTEGER NOT NULL DEFAULT 0,
  "hash" TEXT NOT NULL,
  "bounced" INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS "edition_send_id" ON "cjwnl_edition_send_item" ("edition_send_id");
CREATE INDEX IF NOT EXISTS "newsletter_user_id" ON "cjwnl_edition_send_item" ("newsletter_user_id");
CREATE INDEX IF NOT EXISTS "subscription_id" ON "cjwnl_edition_send_item" ("subscription_id");

-- cjwnl_import
CREATE TABLE IF NOT EXISTS "cjwnl_import" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "type" TEXT NOT NULL,
  "list_contentobject_id" INTEGER DEFAULT NULL,
  "created" INTEGER DEFAULT NULL,
  "creator_contentobject_id" TEXT DEFAULT NULL,
  "note" TEXT DEFAULT NULL,
  "data_text" TEXT NOT NULL,
  "remote_id" TEXT NOT NULL,
  "data_xml" TEXT NOT NULL,
  "imported" INTEGER NOT NULL,
  "imported_user_count" INTEGER NOT NULL,
  "imported_subscription_count" INTEGER NOT NULL
);

-- cjwnl_list
CREATE TABLE IF NOT EXISTS "cjwnl_list" (
  "contentobject_attribute_id" INTEGER NOT NULL,
  "contentobject_attribute_version" INTEGER NOT NULL,
  "contentobject_id" INTEGER NOT NULL,
  "contentclass_id" INTEGER NOT NULL,
  "main_siteaccess" TEXT NOT NULL,
  "siteaccess_array_string" TEXT NOT NULL,
  "output_format_array_string" TEXT NOT NULL,
  "email_sender_name" TEXT NOT NULL,
  "email_sender" TEXT NOT NULL,
  "email_reply_to" TEXT NOT NULL,
  "email_return_path" TEXT NOT NULL,
  "email_receiver_test" TEXT NOT NULL,
  "auto_approve_registered_user" INTEGER NOT NULL DEFAULT 0,
  "skin_name" TEXT NOT NULL DEFAULT 'default',
  "personalize_content" INTEGER NOT NULL DEFAULT 0,
  "user_data_fields" TEXT NOT NULL,
  "is_virtual" INTEGER NOT NULL DEFAULT 0,
  "virtual_filter" TEXT NOT NULL,
  PRIMARY KEY ("contentobject_attribute_id","contentobject_attribute_version")
);
CREATE INDEX IF NOT EXISTS "contentobject_id" ON "cjwnl_list" ("contentobject_id");
CREATE INDEX IF NOT EXISTS "contentobject_attribute_id" ON "cjwnl_list" ("contentobject_attribute_id");
CREATE INDEX IF NOT EXISTS "contentobject_attribute_version" ON "cjwnl_list" ("contentobject_attribute_version");

-- cjwnl_mailbox
CREATE TABLE IF NOT EXISTS "cjwnl_mailbox" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "email" TEXT DEFAULT NULL,
  "server" TEXT DEFAULT NULL,
  "port" INTEGER DEFAULT NULL,
  "user_name" TEXT DEFAULT NULL,
  "password" TEXT DEFAULT NULL,
  "type" TEXT DEFAULT 'imap',
  "delete_mails_from_server" INTEGER NOT NULL DEFAULT 0,
  "is_ssl" INTEGER NOT NULL DEFAULT 0,
  "is_activated" INTEGER DEFAULT 1,
  "last_server_connect" INTEGER DEFAULT NULL
);

-- cjwnl_mailbox_item
CREATE TABLE IF NOT EXISTS "cjwnl_mailbox_item" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "mailbox_id" INTEGER DEFAULT NULL,
  "message_id" INTEGER DEFAULT NULL,
  "message_identifier" TEXT DEFAULT NULL,
  "message_size" INTEGER NOT NULL DEFAULT 0,
  "created" INTEGER DEFAULT NULL,
  "processed" INTEGER DEFAULT NULL,
  "bounce_code" TEXT DEFAULT NULL,
  "email_from" TEXT DEFAULT NULL,
  "email_to" TEXT DEFAULT NULL,
  "email_subject" TEXT DEFAULT NULL,
  "email_send_date" INTEGER DEFAULT NULL,
  "edition_send_id" INTEGER DEFAULT NULL,
  "edition_send_item_id" INTEGER NOT NULL,
  "newsletter_user_id" INTEGER DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS "edition_send_id" ON "cjwnl_mailbox_item" ("edition_send_id");
CREATE INDEX IF NOT EXISTS "mailbox_id" ON "cjwnl_mailbox_item" ("mailbox_id");
CREATE INDEX IF NOT EXISTS "newsletter_user_id" ON "cjwnl_mailbox_item" ("newsletter_user_id");

-- cjwnl_subscription
CREATE TABLE IF NOT EXISTS "cjwnl_subscription" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "list_contentobject_id" INTEGER NOT NULL,
  "newsletter_user_id" INTEGER DEFAULT NULL,
  "hash" TEXT NOT NULL,
  "status" INTEGER NOT NULL DEFAULT 0,
  "output_format_array_string" TEXT NOT NULL,
  "creator_contentobject_id" INTEGER NOT NULL,
  "created" INTEGER NOT NULL,
  "modifier_contentobject_id" INTEGER NOT NULL,
  "modified" INTEGER NOT NULL,
  "confirmed" INTEGER NOT NULL,
  "approved" INTEGER NOT NULL,
  "removed" INTEGER NOT NULL,
  "remote_id" TEXT NOT NULL,
  "import_id" INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS "list_contentobject_id" ON "cjwnl_subscription" ("list_contentobject_id");
CREATE INDEX IF NOT EXISTS "newsletter_user_id" ON "cjwnl_subscription" ("newsletter_user_id");
CREATE INDEX IF NOT EXISTS "import_id" ON "cjwnl_subscription" ("import_id");

-- cjwnl_user
CREATE TABLE IF NOT EXISTS "cjwnl_user" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "email" TEXT DEFAULT NULL,
  "salutation" INTEGER DEFAULT NULL,
  "first_name" TEXT DEFAULT NULL,
  "last_name" TEXT DEFAULT NULL,
  "organisation" TEXT DEFAULT NULL,
  "birthday" TEXT DEFAULT NULL,
  "data_xml" TEXT DEFAULT NULL,
  "hash" TEXT DEFAULT NULL,
  "ez_user_id" INTEGER DEFAULT NULL,
  "status" INTEGER NOT NULL DEFAULT 0,
  "creator_contentobject_id" INTEGER NOT NULL,
  "created" INTEGER NOT NULL,
  "modified" INTEGER NOT NULL,
  "modifier_contentobject_id" INTEGER NOT NULL,
  "confirmed" INTEGER NOT NULL,
  "removed" INTEGER NOT NULL,
  "bounced" INTEGER NOT NULL,
  "blacklisted" INTEGER NOT NULL,
  "note" TEXT DEFAULT NULL,
  "external_user_id" INTEGER DEFAULT NULL,
  "remote_id" TEXT DEFAULT NULL,
  "import_id" INTEGER DEFAULT NULL,
  "bounce_count" INTEGER DEFAULT 0,
  "data_text" TEXT DEFAULT NULL,
  "custom_data_text_1" TEXT NOT NULL,
  "custom_data_text_2" TEXT NOT NULL,
  "custom_data_text_3" TEXT NOT NULL,
  "custom_data_text_4" TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS "ez_user_id" ON "cjwnl_user" ("ez_user_id");
CREATE INDEX IF NOT EXISTS "import_id" ON "cjwnl_user" ("import_id");

-- ezapprove_items
CREATE TABLE IF NOT EXISTS "ezapprove_items" (
  "collaboration_id" INTEGER NOT NULL DEFAULT 0,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "workflow_process_id" INTEGER NOT NULL DEFAULT 0
);

-- ezbasket
CREATE TABLE IF NOT EXISTS "ezbasket" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "order_id" INTEGER NOT NULL DEFAULT 0,
  "productcollection_id" INTEGER NOT NULL DEFAULT 0,
  "session_id" TEXT NOT NULL DEFAULT ''
);
CREATE INDEX IF NOT EXISTS "ezbasket_session_id" ON "ezbasket" ("session_id");

-- ezbinaryfile
CREATE TABLE IF NOT EXISTS "ezbinaryfile" (
  "contentobject_attribute_id" INTEGER NOT NULL DEFAULT 0,
  "download_count" INTEGER NOT NULL DEFAULT 0,
  "filename" TEXT NOT NULL DEFAULT '',
  "mime_type" TEXT NOT NULL DEFAULT '',
  "original_filename" TEXT NOT NULL DEFAULT '',
  "version" INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY ("contentobject_attribute_id","version")
);

-- ezcobj_state
CREATE TABLE IF NOT EXISTS "ezcobj_state" (
  "default_language_id" INTEGER NOT NULL DEFAULT 0,
  "group_id" INTEGER NOT NULL DEFAULT 0,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "identifier" TEXT NOT NULL DEFAULT '',
  "language_mask" INTEGER NOT NULL DEFAULT 0,
  "priority" INTEGER NOT NULL DEFAULT 0
);
CREATE UNIQUE INDEX IF NOT EXISTS "ezcobj_state_identifier" ON "ezcobj_state" ("group_id","identifier");
CREATE INDEX IF NOT EXISTS "ezcobj_state_lmask" ON "ezcobj_state" ("language_mask");
CREATE INDEX IF NOT EXISTS "ezcobj_state_priority" ON "ezcobj_state" ("priority");

-- ezcobj_state_group
CREATE TABLE IF NOT EXISTS "ezcobj_state_group" (
  "default_language_id" INTEGER NOT NULL DEFAULT 0,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "identifier" TEXT NOT NULL DEFAULT '',
  "language_mask" INTEGER NOT NULL DEFAULT 0
);
CREATE UNIQUE INDEX IF NOT EXISTS "ezcobj_state_group_identifier" ON "ezcobj_state_group" ("identifier");
CREATE INDEX IF NOT EXISTS "ezcobj_state_group_lmask" ON "ezcobj_state_group" ("language_mask");

-- ezcobj_state_group_language
CREATE TABLE IF NOT EXISTS "ezcobj_state_group_language" (
  "contentobject_state_group_id" INTEGER NOT NULL DEFAULT 0,
  "description" TEXT NOT NULL,
  "language_id" INTEGER NOT NULL DEFAULT 0,
  "name" TEXT NOT NULL DEFAULT '',
  "real_language_id" INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY ("contentobject_state_group_id","real_language_id")
);

-- ezcobj_state_language
CREATE TABLE IF NOT EXISTS "ezcobj_state_language" (
  "contentobject_state_id" INTEGER NOT NULL DEFAULT 0,
  "description" TEXT NOT NULL,
  "language_id" INTEGER NOT NULL DEFAULT 0,
  "name" TEXT NOT NULL DEFAULT '',
  PRIMARY KEY ("contentobject_state_id","language_id")
);

-- ezcobj_state_link
CREATE TABLE IF NOT EXISTS "ezcobj_state_link" (
  "contentobject_id" INTEGER NOT NULL DEFAULT 0,
  "contentobject_state_id" INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY ("contentobject_id","contentobject_state_id")
);

-- ezcollab_group
CREATE TABLE IF NOT EXISTS "ezcollab_group" (
  "created" INTEGER NOT NULL DEFAULT 0,
  "depth" INTEGER NOT NULL DEFAULT 0,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "is_open" INTEGER NOT NULL DEFAULT 1,
  "modified" INTEGER NOT NULL DEFAULT 0,
  "parent_group_id" INTEGER NOT NULL DEFAULT 0,
  "path_string" TEXT NOT NULL DEFAULT '',
  "priority" INTEGER NOT NULL DEFAULT 0,
  "title" TEXT NOT NULL DEFAULT '',
  "user_id" INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS "ezcollab_group_depth" ON "ezcollab_group" ("depth");
CREATE INDEX IF NOT EXISTS "ezcollab_group_path" ON "ezcollab_group" ("path_string");

-- ezcollab_item
CREATE TABLE IF NOT EXISTS "ezcollab_item" (
  "created" INTEGER NOT NULL DEFAULT 0,
  "creator_id" INTEGER NOT NULL DEFAULT 0,
  "data_float1" REAL NOT NULL DEFAULT 0,
  "data_float2" REAL NOT NULL DEFAULT 0,
  "data_float3" REAL NOT NULL DEFAULT 0,
  "data_int1" INTEGER NOT NULL DEFAULT 0,
  "data_int2" INTEGER NOT NULL DEFAULT 0,
  "data_int3" INTEGER NOT NULL DEFAULT 0,
  "data_text1" TEXT NOT NULL,
  "data_text2" TEXT NOT NULL,
  "data_text3" TEXT NOT NULL,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "modified" INTEGER NOT NULL DEFAULT 0,
  "status" INTEGER NOT NULL DEFAULT 1,
  "type_identifier" TEXT NOT NULL DEFAULT ''
);

-- ezcollab_item_group_link
CREATE TABLE IF NOT EXISTS "ezcollab_item_group_link" (
  "collaboration_id" INTEGER NOT NULL DEFAULT 0,
  "created" INTEGER NOT NULL DEFAULT 0,
  "group_id" INTEGER NOT NULL DEFAULT 0,
  "is_active" INTEGER NOT NULL DEFAULT 1,
  "is_read" INTEGER NOT NULL DEFAULT 0,
  "last_read" INTEGER NOT NULL DEFAULT 0,
  "modified" INTEGER NOT NULL DEFAULT 0,
  "user_id" INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY ("collaboration_id","group_id","user_id")
);

-- ezcollab_item_message_link
CREATE TABLE IF NOT EXISTS "ezcollab_item_message_link" (
  "collaboration_id" INTEGER NOT NULL DEFAULT 0,
  "created" INTEGER NOT NULL DEFAULT 0,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "message_id" INTEGER NOT NULL DEFAULT 0,
  "message_type" INTEGER NOT NULL DEFAULT 0,
  "modified" INTEGER NOT NULL DEFAULT 0,
  "participant_id" INTEGER NOT NULL DEFAULT 0
);

-- ezcollab_item_participant_link
CREATE TABLE IF NOT EXISTS "ezcollab_item_participant_link" (
  "collaboration_id" INTEGER NOT NULL DEFAULT 0,
  "created" INTEGER NOT NULL DEFAULT 0,
  "is_active" INTEGER NOT NULL DEFAULT 1,
  "is_read" INTEGER NOT NULL DEFAULT 0,
  "last_read" INTEGER NOT NULL DEFAULT 0,
  "modified" INTEGER NOT NULL DEFAULT 0,
  "participant_id" INTEGER NOT NULL DEFAULT 0,
  "participant_role" INTEGER NOT NULL DEFAULT 1,
  "participant_type" INTEGER NOT NULL DEFAULT 1,
  PRIMARY KEY ("collaboration_id","participant_id")
);

-- ezcollab_item_status
CREATE TABLE IF NOT EXISTS "ezcollab_item_status" (
  "collaboration_id" INTEGER NOT NULL DEFAULT 0,
  "is_active" INTEGER NOT NULL DEFAULT 1,
  "is_read" INTEGER NOT NULL DEFAULT 0,
  "last_read" INTEGER NOT NULL DEFAULT 0,
  "user_id" INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY ("collaboration_id","user_id")
);

-- ezcollab_notification_rule
CREATE TABLE IF NOT EXISTS "ezcollab_notification_rule" (
  "collab_identifier" TEXT NOT NULL DEFAULT '',
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "user_id" TEXT NOT NULL DEFAULT ''
);

-- ezcollab_profile
CREATE TABLE IF NOT EXISTS "ezcollab_profile" (
  "created" INTEGER NOT NULL DEFAULT 0,
  "data_text1" TEXT NOT NULL,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "main_group" INTEGER NOT NULL DEFAULT 0,
  "modified" INTEGER NOT NULL DEFAULT 0,
  "user_id" INTEGER NOT NULL DEFAULT 0
);

-- ezcollab_simple_message
CREATE TABLE IF NOT EXISTS "ezcollab_simple_message" (
  "created" INTEGER NOT NULL DEFAULT 0,
  "creator_id" INTEGER NOT NULL DEFAULT 0,
  "data_float1" REAL NOT NULL DEFAULT 0,
  "data_float2" REAL NOT NULL DEFAULT 0,
  "data_float3" REAL NOT NULL DEFAULT 0,
  "data_int1" INTEGER NOT NULL DEFAULT 0,
  "data_int2" INTEGER NOT NULL DEFAULT 0,
  "data_int3" INTEGER NOT NULL DEFAULT 0,
  "data_text1" TEXT NOT NULL,
  "data_text2" TEXT NOT NULL,
  "data_text3" TEXT NOT NULL,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "message_type" TEXT NOT NULL DEFAULT '',
  "modified" INTEGER NOT NULL DEFAULT 0
);

-- ezcontent_language
CREATE TABLE IF NOT EXISTS "ezcontent_language" (
  "disabled" INTEGER NOT NULL DEFAULT 0,
  "id" INTEGER NOT NULL DEFAULT 0,
  "locale" TEXT NOT NULL DEFAULT '',
  "name" TEXT NOT NULL DEFAULT '',
  PRIMARY KEY ("id")
);
CREATE INDEX IF NOT EXISTS "ezcontent_language_name" ON "ezcontent_language" ("name");

-- ezcontentbrowsebookmark
CREATE TABLE IF NOT EXISTS "ezcontentbrowsebookmark" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "name" TEXT NOT NULL DEFAULT '',
  "node_id" INTEGER NOT NULL DEFAULT 0,
  "user_id" INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS "ezcontentbrowsebookmark_user" ON "ezcontentbrowsebookmark" ("user_id");
CREATE INDEX IF NOT EXISTS "ezcontentbrowsebookmark_location" ON "ezcontentbrowsebookmark" ("node_id");
CREATE INDEX IF NOT EXISTS "ezcontentbrowsebookmark_user_location" ON "ezcontentbrowsebookmark" ("user_id","node_id");

-- ezcontentbrowserecent
CREATE TABLE IF NOT EXISTS "ezcontentbrowserecent" (
  "created" INTEGER NOT NULL DEFAULT 0,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "name" TEXT NOT NULL DEFAULT '',
  "node_id" INTEGER NOT NULL DEFAULT 0,
  "user_id" INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS "ezcontentbrowserecent_user" ON "ezcontentbrowserecent" ("user_id");

-- ezcontentclass
CREATE TABLE IF NOT EXISTS "ezcontentclass" (
  "always_available" INTEGER NOT NULL DEFAULT 0,
  "contentobject_name" TEXT DEFAULT NULL,
  "created" INTEGER NOT NULL DEFAULT 0,
  "creator_id" INTEGER NOT NULL DEFAULT 0,
  "id" INTEGER NOT NULL,
  "identifier" TEXT NOT NULL DEFAULT '',
  "initial_language_id" INTEGER NOT NULL DEFAULT 0,
  "is_container" INTEGER NOT NULL DEFAULT 0,
  "language_mask" INTEGER NOT NULL DEFAULT 0,
  "modified" INTEGER NOT NULL DEFAULT 0,
  "modifier_id" INTEGER NOT NULL DEFAULT 0,
  "remote_id" TEXT NOT NULL DEFAULT '',
  "serialized_description_list" TEXT DEFAULT NULL,
  "serialized_name_list" TEXT DEFAULT NULL,
  "sort_field" INTEGER NOT NULL DEFAULT 1,
  "sort_order" INTEGER NOT NULL DEFAULT 1,
  "url_alias_name" TEXT DEFAULT NULL,
  "version" INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY ("id","version")
);
CREATE INDEX IF NOT EXISTS "ezcontentclass_version" ON "ezcontentclass" ("version");
CREATE INDEX IF NOT EXISTS "ezcontentclass_identifier" ON "ezcontentclass" ("identifier","version");

-- ezcontentclass_attribute
CREATE TABLE IF NOT EXISTS "ezcontentclass_attribute" (
  "can_translate" INTEGER DEFAULT 1,
  "category" TEXT NOT NULL DEFAULT '',
  "contentclass_id" INTEGER NOT NULL DEFAULT 0,
  "data_float1" REAL DEFAULT NULL,
  "data_float2" REAL DEFAULT NULL,
  "data_float3" REAL DEFAULT NULL,
  "data_float4" REAL DEFAULT NULL,
  "data_int1" INTEGER DEFAULT NULL,
  "data_int2" INTEGER DEFAULT NULL,
  "data_int3" INTEGER DEFAULT NULL,
  "data_int4" INTEGER DEFAULT NULL,
  "data_text1" TEXT DEFAULT NULL,
  "data_text2" TEXT DEFAULT NULL,
  "data_text3" TEXT DEFAULT NULL,
  "data_text4" TEXT DEFAULT NULL,
  "data_text5" TEXT DEFAULT NULL,
  "data_type_string" TEXT NOT NULL DEFAULT '',
  "id" INTEGER NOT NULL,
  "identifier" TEXT NOT NULL DEFAULT '',
  "is_information_collector" INTEGER NOT NULL DEFAULT 0,
  "is_required" INTEGER NOT NULL DEFAULT 0,
  "is_searchable" INTEGER NOT NULL DEFAULT 0,
  "placement" INTEGER NOT NULL DEFAULT 0,
  "serialized_data_text" TEXT DEFAULT NULL,
  "serialized_description_list" TEXT DEFAULT NULL,
  "serialized_name_list" TEXT NOT NULL,
  "version" INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY ("id","version")
);
CREATE INDEX IF NOT EXISTS "ezcontentclass_attr_ccid" ON "ezcontentclass_attribute" ("contentclass_id");

-- ezcontentclass_attribute_ml
CREATE TABLE IF NOT EXISTS "ezcontentclass_attribute_ml" (
  "contentclass_attribute_id" INTEGER NOT NULL,
  "version" INTEGER NOT NULL,
  "language_id" INTEGER NOT NULL,
  "name" TEXT NOT NULL,
  "description" TEXT DEFAULT NULL,
  "data_text" TEXT DEFAULT NULL,
  "data_json" TEXT DEFAULT NULL,
  PRIMARY KEY ("contentclass_attribute_id","version","language_id")
);
CREATE INDEX IF NOT EXISTS "ezcontentclass_attribute_ml_lang_fk" ON "ezcontentclass_attribute_ml" ("language_id");

-- ezcontentclass_classgroup
CREATE TABLE IF NOT EXISTS "ezcontentclass_classgroup" (
  "contentclass_id" INTEGER NOT NULL DEFAULT 0,
  "contentclass_version" INTEGER NOT NULL DEFAULT 0,
  "group_id" INTEGER NOT NULL DEFAULT 0,
  "group_name" TEXT DEFAULT NULL,
  PRIMARY KEY ("contentclass_id","contentclass_version","group_id")
);

-- ezcontentclass_name
CREATE TABLE IF NOT EXISTS "ezcontentclass_name" (
  "contentclass_id" INTEGER NOT NULL DEFAULT 0,
  "contentclass_version" INTEGER NOT NULL DEFAULT 0,
  "language_id" INTEGER NOT NULL DEFAULT 0,
  "language_locale" TEXT NOT NULL DEFAULT '',
  "name" TEXT NOT NULL DEFAULT '',
  PRIMARY KEY ("contentclass_id","contentclass_version","language_id")
);

-- ezcontentclassgroup
CREATE TABLE IF NOT EXISTS "ezcontentclassgroup" (
  "created" INTEGER NOT NULL DEFAULT 0,
  "creator_id" INTEGER NOT NULL DEFAULT 0,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "modified" INTEGER NOT NULL DEFAULT 0,
  "modifier_id" INTEGER NOT NULL DEFAULT 0,
  "name" TEXT DEFAULT NULL
);

-- ezcontentobject
CREATE TABLE IF NOT EXISTS "ezcontentobject" (
  "contentclass_id" INTEGER NOT NULL DEFAULT 0,
  "current_version" INTEGER DEFAULT NULL,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "initial_language_id" INTEGER NOT NULL DEFAULT 0,
  "language_mask" INTEGER NOT NULL DEFAULT 0,
  "modified" INTEGER NOT NULL DEFAULT 0,
  "name" TEXT DEFAULT NULL,
  "owner_id" INTEGER NOT NULL DEFAULT 0,
  "published" INTEGER NOT NULL DEFAULT 0,
  "remote_id" TEXT DEFAULT NULL,
  "section_id" INTEGER NOT NULL DEFAULT 0,
  "status" INTEGER DEFAULT 0,
  "is_hidden" INTEGER NOT NULL DEFAULT 0
);
CREATE UNIQUE INDEX IF NOT EXISTS "ezcontentobject_remote_id" ON "ezcontentobject" ("remote_id");
CREATE INDEX IF NOT EXISTS "ezcontentobject_classid" ON "ezcontentobject" ("contentclass_id");
CREATE INDEX IF NOT EXISTS "ezcontentobject_currentversion" ON "ezcontentobject" ("current_version");
CREATE INDEX IF NOT EXISTS "ezcontentobject_lmask" ON "ezcontentobject" ("language_mask");
CREATE INDEX IF NOT EXISTS "ezcontentobject_owner" ON "ezcontentobject" ("owner_id");
CREATE INDEX IF NOT EXISTS "ezcontentobject_pub" ON "ezcontentobject" ("published");
CREATE INDEX IF NOT EXISTS "ezcontentobject_status" ON "ezcontentobject" ("status");
CREATE INDEX IF NOT EXISTS "ezcontentobject_section" ON "ezcontentobject" ("section_id");

-- ezcontentobject_attribute
CREATE TABLE IF NOT EXISTS "ezcontentobject_attribute" (
  "attribute_original_id" INTEGER DEFAULT 0,
  "contentclassattribute_id" INTEGER NOT NULL DEFAULT 0,
  "contentobject_id" INTEGER NOT NULL DEFAULT 0,
  "data_float" REAL DEFAULT NULL,
  "data_int" INTEGER DEFAULT NULL,
  "data_text" TEXT DEFAULT NULL,
  "data_type_string" TEXT DEFAULT '',
  "id" INTEGER NOT NULL,
  "language_code" TEXT NOT NULL DEFAULT '',
  "language_id" INTEGER NOT NULL DEFAULT 0,
  "sort_key_int" INTEGER NOT NULL DEFAULT 0,
  "sort_key_string" TEXT NOT NULL DEFAULT '',
  "version" INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY ("id","version")
);
CREATE INDEX IF NOT EXISTS "ezcontentobject_attribute_co_id_ver_lang_code" ON "ezcontentobject_attribute" ("contentobject_id","version","language_code");
CREATE INDEX IF NOT EXISTS "ezcontentobject_attribute_language_code" ON "ezcontentobject_attribute" ("language_code");
CREATE INDEX IF NOT EXISTS "ezcontentobject_classattr_id" ON "ezcontentobject_attribute" ("contentclassattribute_id");
CREATE INDEX IF NOT EXISTS "sort_key_int" ON "ezcontentobject_attribute" ("sort_key_int");
CREATE INDEX IF NOT EXISTS "sort_key_string" ON "ezcontentobject_attribute" ("sort_key_string");

-- ezcontentobject_link
CREATE TABLE IF NOT EXISTS "ezcontentobject_link" (
  "contentclassattribute_id" INTEGER NOT NULL DEFAULT 0,
  "from_contentobject_id" INTEGER NOT NULL DEFAULT 0,
  "from_contentobject_version" INTEGER NOT NULL DEFAULT 0,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "relation_type" INTEGER NOT NULL DEFAULT 1,
  "to_contentobject_id" INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS "ezco_link_from" ON "ezcontentobject_link" ("from_contentobject_id","from_contentobject_version","contentclassattribute_id");
CREATE INDEX IF NOT EXISTS "ezco_link_to_co_id" ON "ezcontentobject_link" ("to_contentobject_id");

-- ezcontentobject_name
CREATE TABLE IF NOT EXISTS "ezcontentobject_name" (
  "content_translation" TEXT NOT NULL DEFAULT '',
  "content_version" INTEGER NOT NULL DEFAULT 0,
  "contentobject_id" INTEGER NOT NULL DEFAULT 0,
  "language_id" INTEGER NOT NULL DEFAULT 0,
  "name" TEXT DEFAULT NULL,
  "real_translation" TEXT DEFAULT NULL,
  PRIMARY KEY ("contentobject_id","content_version","content_translation")
);
CREATE INDEX IF NOT EXISTS "ezcontentobject_name_cov_id" ON "ezcontentobject_name" ("content_version");
CREATE INDEX IF NOT EXISTS "ezcontentobject_name_lang_id" ON "ezcontentobject_name" ("language_id");
CREATE INDEX IF NOT EXISTS "ezcontentobject_name_name" ON "ezcontentobject_name" ("name");

-- ezcontentobject_trash
CREATE TABLE IF NOT EXISTS "ezcontentobject_trash" (
  "contentobject_id" INTEGER DEFAULT NULL,
  "contentobject_version" INTEGER DEFAULT NULL,
  "depth" INTEGER NOT NULL DEFAULT 0,
  "is_hidden" INTEGER NOT NULL DEFAULT 0,
  "is_invisible" INTEGER NOT NULL DEFAULT 0,
  "main_node_id" INTEGER DEFAULT NULL,
  "modified_subnode" INTEGER DEFAULT 0,
  "node_id" INTEGER NOT NULL DEFAULT 0,
  "parent_node_id" INTEGER NOT NULL DEFAULT 0,
  "path_identification_string" TEXT DEFAULT NULL,
  "path_string" TEXT NOT NULL DEFAULT '',
  "priority" INTEGER NOT NULL DEFAULT 0,
  "remote_id" TEXT NOT NULL DEFAULT '',
  "sort_field" INTEGER DEFAULT 1,
  "sort_order" INTEGER DEFAULT 1,
  "trashed" INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY ("node_id")
);
CREATE INDEX IF NOT EXISTS "ezcobj_trash_co_id" ON "ezcontentobject_trash" ("contentobject_id");
CREATE INDEX IF NOT EXISTS "ezcobj_trash_depth" ON "ezcontentobject_trash" ("depth");
CREATE INDEX IF NOT EXISTS "ezcobj_trash_modified_subnode" ON "ezcontentobject_trash" ("modified_subnode");
CREATE INDEX IF NOT EXISTS "ezcobj_trash_p_node_id" ON "ezcontentobject_trash" ("parent_node_id");
CREATE INDEX IF NOT EXISTS "ezcobj_trash_path" ON "ezcontentobject_trash" ("path_string");
CREATE INDEX IF NOT EXISTS "ezcobj_trash_path_ident" ON "ezcontentobject_trash" ("path_identification_string");

-- ezcontentobject_tree
CREATE TABLE IF NOT EXISTS "ezcontentobject_tree" (
  "contentobject_id" INTEGER DEFAULT NULL,
  "contentobject_is_published" INTEGER DEFAULT NULL,
  "contentobject_version" INTEGER DEFAULT NULL,
  "depth" INTEGER NOT NULL DEFAULT 0,
  "is_hidden" INTEGER NOT NULL DEFAULT 0,
  "is_invisible" INTEGER NOT NULL DEFAULT 0,
  "main_node_id" INTEGER DEFAULT NULL,
  "modified_subnode" INTEGER DEFAULT 0,
  "node_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "parent_node_id" INTEGER NOT NULL DEFAULT 0,
  "path_identification_string" TEXT DEFAULT NULL,
  "path_string" TEXT NOT NULL DEFAULT '',
  "priority" INTEGER NOT NULL DEFAULT 0,
  "remote_id" TEXT NOT NULL DEFAULT '',
  "sort_field" INTEGER DEFAULT 1,
  "sort_order" INTEGER DEFAULT 1
);
CREATE UNIQUE INDEX IF NOT EXISTS "ezcontentobject_tree_contentobject_id_path_string" ON "ezcontentobject_tree" ("path_string","contentobject_id");
CREATE INDEX IF NOT EXISTS "ezcontentobject_tree_remote_id" ON "ezcontentobject_tree" ("remote_id");
CREATE INDEX IF NOT EXISTS "ezcontentobject_tree_co_id" ON "ezcontentobject_tree" ("contentobject_id");
CREATE INDEX IF NOT EXISTS "ezcontentobject_tree_depth" ON "ezcontentobject_tree" ("depth");
CREATE INDEX IF NOT EXISTS "ezcontentobject_tree_p_node_id" ON "ezcontentobject_tree" ("parent_node_id");
CREATE INDEX IF NOT EXISTS "ezcontentobject_tree_path" ON "ezcontentobject_tree" ("path_string");
CREATE INDEX IF NOT EXISTS "ezcontentobject_tree_path_ident" ON "ezcontentobject_tree" ("path_identification_string");
CREATE INDEX IF NOT EXISTS "modified_subnode" ON "ezcontentobject_tree" ("modified_subnode");

-- ezcontentobject_version
CREATE TABLE IF NOT EXISTS "ezcontentobject_version" (
  "contentobject_id" INTEGER DEFAULT NULL,
  "created" INTEGER NOT NULL DEFAULT 0,
  "creator_id" INTEGER NOT NULL DEFAULT 0,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "initial_language_id" INTEGER NOT NULL DEFAULT 0,
  "language_mask" INTEGER NOT NULL DEFAULT 0,
  "modified" INTEGER NOT NULL DEFAULT 0,
  "status" INTEGER NOT NULL DEFAULT 0,
  "user_id" INTEGER NOT NULL DEFAULT 0,
  "version" INTEGER NOT NULL DEFAULT 0,
  "workflow_event_pos" INTEGER DEFAULT 0
);
CREATE INDEX IF NOT EXISTS "ezcobj_version_creator_id" ON "ezcontentobject_version" ("creator_id");
CREATE INDEX IF NOT EXISTS "ezcobj_version_status" ON "ezcontentobject_version" ("status");
CREATE INDEX IF NOT EXISTS "idx_object_version_objver" ON "ezcontentobject_version" ("contentobject_id","version");
CREATE INDEX IF NOT EXISTS "ezcontobj_version_obj_status" ON "ezcontentobject_version" ("contentobject_id","status");

-- ezcurrencydata
CREATE TABLE IF NOT EXISTS "ezcurrencydata" (
  "auto_rate_value" REAL NOT NULL DEFAULT 0.00000,
  "code" TEXT NOT NULL DEFAULT '',
  "custom_rate_value" REAL NOT NULL DEFAULT 0.00000,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "locale" TEXT NOT NULL DEFAULT '',
  "rate_factor" REAL NOT NULL DEFAULT 1.00000,
  "status" INTEGER NOT NULL DEFAULT 1,
  "symbol" TEXT NOT NULL DEFAULT ''
);
CREATE INDEX IF NOT EXISTS "ezcurrencydata_code" ON "ezcurrencydata" ("code");

-- ezdiscountrule
CREATE TABLE IF NOT EXISTS "ezdiscountrule" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "name" TEXT NOT NULL DEFAULT ''
);

-- ezdiscountsubrule
CREATE TABLE IF NOT EXISTS "ezdiscountsubrule" (
  "discount_percent" REAL DEFAULT NULL,
  "discountrule_id" INTEGER NOT NULL DEFAULT 0,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "limitation" TEXT DEFAULT NULL,
  "name" TEXT NOT NULL DEFAULT ''
);

-- ezdiscountsubrule_value
CREATE TABLE IF NOT EXISTS "ezdiscountsubrule_value" (
  "discountsubrule_id" INTEGER NOT NULL DEFAULT 0,
  "issection" INTEGER NOT NULL DEFAULT 0,
  "value" INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY ("discountsubrule_id","value","issection")
);

-- ezenumobjectvalue
CREATE TABLE IF NOT EXISTS "ezenumobjectvalue" (
  "contentobject_attribute_id" INTEGER NOT NULL DEFAULT 0,
  "contentobject_attribute_version" INTEGER NOT NULL DEFAULT 0,
  "enumelement" TEXT NOT NULL DEFAULT '',
  "enumid" INTEGER NOT NULL DEFAULT 0,
  "enumvalue" TEXT NOT NULL DEFAULT '',
  PRIMARY KEY ("contentobject_attribute_id","contentobject_attribute_version","enumid")
);

-- ezenumvalue
CREATE TABLE IF NOT EXISTS "ezenumvalue" (
  "contentclass_attribute_id" INTEGER NOT NULL DEFAULT 0,
  "contentclass_attribute_version" INTEGER NOT NULL DEFAULT 0,
  "enumelement" TEXT NOT NULL DEFAULT '',
  "enumvalue" TEXT NOT NULL DEFAULT '',
  "id" INTEGER NOT NULL,
  "placement" INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY ("id","contentclass_attribute_id","contentclass_attribute_version")
);
CREATE INDEX IF NOT EXISTS "ezenumvalue_co_cl_attr_id_co_class_att_ver" ON "ezenumvalue" ("contentclass_attribute_id","contentclass_attribute_version");

-- ezforgot_password
CREATE TABLE IF NOT EXISTS "ezforgot_password" (
  "hash_key" TEXT NOT NULL DEFAULT '',
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "time" INTEGER NOT NULL DEFAULT 0,
  "user_id" INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS "ezforgot_password_user" ON "ezforgot_password" ("user_id");

-- ezgeneral_digest_user_settings
CREATE TABLE IF NOT EXISTS "ezgeneral_digest_user_settings" (
  "day" TEXT NOT NULL DEFAULT '',
  "digest_type" INTEGER NOT NULL DEFAULT 0,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "receive_digest" INTEGER NOT NULL DEFAULT 0,
  "time" TEXT NOT NULL DEFAULT '',
  "user_id" INTEGER NOT NULL DEFAULT 0
);
CREATE UNIQUE INDEX IF NOT EXISTS "ezgeneral_digest_user_id" ON "ezgeneral_digest_user_settings" ("user_id");

-- ezgmaplocation
CREATE TABLE IF NOT EXISTS "ezgmaplocation" (
  "contentobject_attribute_id" INTEGER NOT NULL DEFAULT 0,
  "contentobject_version" INTEGER NOT NULL DEFAULT 0,
  "latitude" REAL NOT NULL DEFAULT 0,
  "longitude" REAL NOT NULL DEFAULT 0,
  "address" TEXT DEFAULT NULL,
  PRIMARY KEY ("contentobject_attribute_id","contentobject_version")
);
CREATE INDEX IF NOT EXISTS "latitude_longitude_key" ON "ezgmaplocation" ("latitude","longitude");

-- ezimagefile
CREATE TABLE IF NOT EXISTS "ezimagefile" (
  "contentobject_attribute_id" INTEGER NOT NULL DEFAULT 0,
  "filepath" TEXT NOT NULL,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL
);
CREATE INDEX IF NOT EXISTS "ezimagefile_coid" ON "ezimagefile" ("contentobject_attribute_id");
CREATE INDEX IF NOT EXISTS "ezimagefile_file" ON "ezimagefile" ("filepath");

-- ezinfocollection
CREATE TABLE IF NOT EXISTS "ezinfocollection" (
  "contentobject_id" INTEGER NOT NULL DEFAULT 0,
  "created" INTEGER NOT NULL DEFAULT 0,
  "creator_id" INTEGER NOT NULL DEFAULT 0,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "modified" INTEGER DEFAULT 0,
  "user_identifier" TEXT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS "ezinfocollection_co_id_created" ON "ezinfocollection" ("contentobject_id","created");

-- ezinfocollection_attribute
CREATE TABLE IF NOT EXISTS "ezinfocollection_attribute" (
  "contentclass_attribute_id" INTEGER NOT NULL DEFAULT 0,
  "contentobject_attribute_id" INTEGER DEFAULT NULL,
  "contentobject_id" INTEGER DEFAULT NULL,
  "data_float" REAL DEFAULT NULL,
  "data_int" INTEGER DEFAULT NULL,
  "data_text" TEXT DEFAULT NULL,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "informationcollection_id" INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS "ezinfocollection_attr_cca_id" ON "ezinfocollection_attribute" ("contentclass_attribute_id");
CREATE INDEX IF NOT EXISTS "ezinfocollection_attr_co_id" ON "ezinfocollection_attribute" ("contentobject_id");
CREATE INDEX IF NOT EXISTS "ezinfocollection_attr_coa_id" ON "ezinfocollection_attribute" ("contentobject_attribute_id");
CREATE INDEX IF NOT EXISTS "ezinfocollection_attr_ic_id" ON "ezinfocollection_attribute" ("informationcollection_id");

-- ezisbn_group
CREATE TABLE IF NOT EXISTS "ezisbn_group" (
  "description" TEXT NOT NULL DEFAULT '',
  "group_number" INTEGER NOT NULL DEFAULT 0,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL
);

-- ezisbn_group_range
CREATE TABLE IF NOT EXISTS "ezisbn_group_range" (
  "from_number" INTEGER NOT NULL DEFAULT 0,
  "group_from" TEXT NOT NULL DEFAULT '',
  "group_length" INTEGER NOT NULL DEFAULT 0,
  "group_to" TEXT NOT NULL DEFAULT '',
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "to_number" INTEGER NOT NULL DEFAULT 0
);

-- ezisbn_registrant_range
CREATE TABLE IF NOT EXISTS "ezisbn_registrant_range" (
  "from_number" INTEGER NOT NULL DEFAULT 0,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "isbn_group_id" INTEGER NOT NULL DEFAULT 0,
  "registrant_from" TEXT NOT NULL DEFAULT '',
  "registrant_length" INTEGER NOT NULL DEFAULT 0,
  "registrant_to" TEXT NOT NULL DEFAULT '',
  "to_number" INTEGER NOT NULL DEFAULT 0
);

-- ezkeyword
CREATE TABLE IF NOT EXISTS "ezkeyword" (
  "class_id" INTEGER NOT NULL DEFAULT 0,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "keyword" TEXT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS "ezkeyword_keyword" ON "ezkeyword" ("keyword");

-- ezkeyword_attribute_link
CREATE TABLE IF NOT EXISTS "ezkeyword_attribute_link" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "keyword_id" INTEGER NOT NULL DEFAULT 0,
  "objectattribute_id" INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS "ezkeyword_attr_link_kid_oaid" ON "ezkeyword_attribute_link" ("keyword_id","objectattribute_id");
CREATE INDEX IF NOT EXISTS "ezkeyword_attr_link_oaid" ON "ezkeyword_attribute_link" ("objectattribute_id");

-- ezmedia
CREATE TABLE IF NOT EXISTS "ezmedia" (
  "contentobject_attribute_id" INTEGER NOT NULL DEFAULT 0,
  "controls" TEXT DEFAULT NULL,
  "filename" TEXT NOT NULL DEFAULT '',
  "has_controller" INTEGER DEFAULT 0,
  "height" INTEGER DEFAULT NULL,
  "is_autoplay" INTEGER DEFAULT 0,
  "is_loop" INTEGER DEFAULT 0,
  "mime_type" TEXT NOT NULL DEFAULT '',
  "original_filename" TEXT NOT NULL DEFAULT '',
  "pluginspage" TEXT DEFAULT NULL,
  "quality" TEXT DEFAULT NULL,
  "version" INTEGER NOT NULL DEFAULT 0,
  "width" INTEGER DEFAULT NULL,
  PRIMARY KEY ("contentobject_attribute_id","version")
);

-- ezmessage
CREATE TABLE IF NOT EXISTS "ezmessage" (
  "body" TEXT DEFAULT NULL,
  "destination_address" TEXT NOT NULL DEFAULT '',
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "is_sent" INTEGER NOT NULL DEFAULT 0,
  "send_method" TEXT NOT NULL DEFAULT '',
  "send_time" TEXT NOT NULL DEFAULT '',
  "send_weekday" TEXT NOT NULL DEFAULT '',
  "title" TEXT NOT NULL DEFAULT ''
);

-- ezmodule_run
CREATE TABLE IF NOT EXISTS "ezmodule_run" (
  "function_name" TEXT DEFAULT NULL,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "module_data" TEXT DEFAULT NULL,
  "module_name" TEXT DEFAULT NULL,
  "workflow_process_id" INTEGER DEFAULT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS "ezmodule_run_workflow_process_id_s" ON "ezmodule_run" ("workflow_process_id");

-- ezmultipricedata
CREATE TABLE IF NOT EXISTS "ezmultipricedata" (
  "contentobject_attr_id" INTEGER NOT NULL DEFAULT 0,
  "contentobject_attr_version" INTEGER NOT NULL DEFAULT 0,
  "currency_code" TEXT NOT NULL DEFAULT '',
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "type" INTEGER NOT NULL DEFAULT 0,
  "value" REAL NOT NULL DEFAULT 0.00
);
CREATE INDEX IF NOT EXISTS "ezmultipricedata_coa_id" ON "ezmultipricedata" ("contentobject_attr_id");
CREATE INDEX IF NOT EXISTS "ezmultipricedata_coa_version" ON "ezmultipricedata" ("contentobject_attr_version");
CREATE INDEX IF NOT EXISTS "ezmultipricedata_currency_code" ON "ezmultipricedata" ("currency_code");

-- eznode_assignment
CREATE TABLE IF NOT EXISTS "eznode_assignment" (
  "contentobject_id" INTEGER DEFAULT NULL,
  "contentobject_version" INTEGER DEFAULT NULL,
  "from_node_id" INTEGER DEFAULT 0,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "is_main" INTEGER NOT NULL DEFAULT 0,
  "op_code" INTEGER NOT NULL DEFAULT 0,
  "parent_node" INTEGER DEFAULT NULL,
  "parent_remote_id" TEXT NOT NULL DEFAULT '',
  "remote_id" TEXT NOT NULL DEFAULT '0',
  "sort_field" INTEGER DEFAULT 1,
  "sort_order" INTEGER DEFAULT 1,
  "priority" INTEGER NOT NULL DEFAULT 0,
  "is_hidden" INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS "eznode_assignment_co_version" ON "eznode_assignment" ("contentobject_version");
CREATE INDEX IF NOT EXISTS "eznode_assignment_coid_cov" ON "eznode_assignment" ("contentobject_id","contentobject_version");
CREATE INDEX IF NOT EXISTS "eznode_assignment_is_main" ON "eznode_assignment" ("is_main");
CREATE INDEX IF NOT EXISTS "eznode_assignment_parent_node" ON "eznode_assignment" ("parent_node");

-- eznotification
CREATE TABLE IF NOT EXISTS "eznotification" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "owner_id" INTEGER NOT NULL DEFAULT 0,
  "is_pending" INTEGER NOT NULL DEFAULT 1,
  "type" TEXT NOT NULL DEFAULT '',
  "created" INTEGER NOT NULL DEFAULT 0,
  "data" TEXT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS "eznotification_owner" ON "eznotification" ("owner_id");
CREATE INDEX IF NOT EXISTS "eznotification_owner_is_pending" ON "eznotification" ("owner_id","is_pending");

-- eznotificationcollection
CREATE TABLE IF NOT EXISTS "eznotificationcollection" (
  "data_subject" TEXT NOT NULL,
  "data_text" TEXT NOT NULL,
  "event_id" INTEGER NOT NULL DEFAULT 0,
  "handler" TEXT NOT NULL DEFAULT '',
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "transport" TEXT NOT NULL DEFAULT ''
);

-- eznotificationcollection_item
CREATE TABLE IF NOT EXISTS "eznotificationcollection_item" (
  "address" TEXT NOT NULL DEFAULT '',
  "collection_id" INTEGER NOT NULL DEFAULT 0,
  "event_id" INTEGER NOT NULL DEFAULT 0,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "send_date" INTEGER NOT NULL DEFAULT 0
);

-- eznotificationevent
CREATE TABLE IF NOT EXISTS "eznotificationevent" (
  "data_int1" INTEGER NOT NULL DEFAULT 0,
  "data_int2" INTEGER NOT NULL DEFAULT 0,
  "data_int3" INTEGER NOT NULL DEFAULT 0,
  "data_int4" INTEGER NOT NULL DEFAULT 0,
  "data_text1" TEXT NOT NULL,
  "data_text2" TEXT NOT NULL,
  "data_text3" TEXT NOT NULL,
  "data_text4" TEXT NOT NULL,
  "event_type_string" TEXT NOT NULL DEFAULT '',
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "status" INTEGER NOT NULL DEFAULT 0
);

-- ezoperation_memento
CREATE TABLE IF NOT EXISTS "ezoperation_memento" (
  "id" INTEGER NOT NULL,
  "main" INTEGER NOT NULL DEFAULT 0,
  "main_key" TEXT NOT NULL DEFAULT '',
  "memento_data" TEXT NOT NULL,
  "memento_key" TEXT NOT NULL DEFAULT '',
  PRIMARY KEY ("id","memento_key")
);
CREATE INDEX IF NOT EXISTS "ezoperation_memento_memento_key_main" ON "ezoperation_memento" ("memento_key","main");

-- ezorder
CREATE TABLE IF NOT EXISTS "ezorder" (
  "account_identifier" TEXT NOT NULL DEFAULT 'default',
  "created" INTEGER NOT NULL DEFAULT 0,
  "data_text_1" TEXT DEFAULT NULL,
  "data_text_2" TEXT DEFAULT NULL,
  "email" TEXT DEFAULT '',
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "ignore_vat" INTEGER NOT NULL DEFAULT 0,
  "is_archived" INTEGER NOT NULL DEFAULT 0,
  "is_temporary" INTEGER NOT NULL DEFAULT 1,
  "order_nr" INTEGER NOT NULL DEFAULT 0,
  "productcollection_id" INTEGER NOT NULL DEFAULT 0,
  "status_id" INTEGER DEFAULT 0,
  "status_modified" INTEGER DEFAULT 0,
  "status_modifier_id" INTEGER DEFAULT 0,
  "user_id" INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS "ezorder_is_archived" ON "ezorder" ("is_archived");
CREATE INDEX IF NOT EXISTS "ezorder_is_tmp" ON "ezorder" ("is_temporary");

-- ezorder_item
CREATE TABLE IF NOT EXISTS "ezorder_item" (
  "description" TEXT DEFAULT NULL,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "is_vat_inc" INTEGER NOT NULL DEFAULT 0,
  "order_id" INTEGER NOT NULL DEFAULT 0,
  "price" REAL DEFAULT NULL,
  "type" TEXT DEFAULT NULL,
  "vat_value" REAL NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS "ezorder_item_order_id" ON "ezorder_item" ("order_id");
CREATE INDEX IF NOT EXISTS "ezorder_item_type" ON "ezorder_item" ("type");

-- ezorder_nr_incr
CREATE TABLE IF NOT EXISTS "ezorder_nr_incr" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL
);

-- ezorder_status
CREATE TABLE IF NOT EXISTS "ezorder_status" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "is_active" INTEGER NOT NULL DEFAULT 1,
  "name" TEXT NOT NULL DEFAULT '',
  "status_id" INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS "ezorder_status_active" ON "ezorder_status" ("is_active");
CREATE INDEX IF NOT EXISTS "ezorder_status_name" ON "ezorder_status" ("name");
CREATE INDEX IF NOT EXISTS "ezorder_status_sid" ON "ezorder_status" ("status_id");

-- ezorder_status_history
CREATE TABLE IF NOT EXISTS "ezorder_status_history" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "modified" INTEGER NOT NULL DEFAULT 0,
  "modifier_id" INTEGER NOT NULL DEFAULT 0,
  "order_id" INTEGER NOT NULL DEFAULT 0,
  "status_id" INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS "ezorder_status_history_mod" ON "ezorder_status_history" ("modified");
CREATE INDEX IF NOT EXISTS "ezorder_status_history_oid" ON "ezorder_status_history" ("order_id");
CREATE INDEX IF NOT EXISTS "ezorder_status_history_sid" ON "ezorder_status_history" ("status_id");

-- ezpackage
CREATE TABLE IF NOT EXISTS "ezpackage" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "install_date" INTEGER NOT NULL DEFAULT 0,
  "name" TEXT NOT NULL DEFAULT '',
  "version" TEXT NOT NULL DEFAULT '0'
);

-- ezpaymentobject
CREATE TABLE IF NOT EXISTS "ezpaymentobject" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "order_id" INTEGER NOT NULL DEFAULT 0,
  "payment_string" TEXT NOT NULL DEFAULT '',
  "status" INTEGER NOT NULL DEFAULT 0,
  "workflowprocess_id" INTEGER NOT NULL DEFAULT 0
);

-- ezpdf_export
CREATE TABLE IF NOT EXISTS "ezpdf_export" (
  "created" INTEGER DEFAULT NULL,
  "creator_id" INTEGER DEFAULT NULL,
  "export_classes" TEXT DEFAULT NULL,
  "export_structure" TEXT DEFAULT NULL,
  "id" INTEGER NOT NULL,
  "intro_text" TEXT DEFAULT NULL,
  "modified" INTEGER DEFAULT NULL,
  "modifier_id" INTEGER DEFAULT NULL,
  "pdf_filename" TEXT DEFAULT NULL,
  "show_frontpage" INTEGER DEFAULT NULL,
  "site_access" TEXT DEFAULT NULL,
  "source_node_id" INTEGER DEFAULT NULL,
  "status" INTEGER DEFAULT NULL,
  "sub_text" TEXT DEFAULT NULL,
  "title" TEXT DEFAULT NULL,
  "version" INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY ("id","version")
);

-- ezpending_actions
CREATE TABLE IF NOT EXISTS "ezpending_actions" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "action" TEXT NOT NULL DEFAULT '',
  "created" INTEGER DEFAULT NULL,
  "param" TEXT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS "ezpending_actions_action" ON "ezpending_actions" ("action");
CREATE INDEX IF NOT EXISTS "ezpending_actions_created" ON "ezpending_actions" ("created");

-- ezpolicy
CREATE TABLE IF NOT EXISTS "ezpolicy" (
  "function_name" TEXT DEFAULT NULL,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "module_name" TEXT DEFAULT NULL,
  "original_id" INTEGER NOT NULL DEFAULT 0,
  "role_id" INTEGER DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS "ezpolicy_original_id" ON "ezpolicy" ("original_id");
CREATE INDEX IF NOT EXISTS "ezpolicy_role_id" ON "ezpolicy" ("role_id");

-- ezpolicy_limitation
CREATE TABLE IF NOT EXISTS "ezpolicy_limitation" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "identifier" TEXT NOT NULL DEFAULT '',
  "policy_id" INTEGER DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS "policy_id" ON "ezpolicy_limitation" ("policy_id");

-- ezpolicy_limitation_value
CREATE TABLE IF NOT EXISTS "ezpolicy_limitation_value" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "limitation_id" INTEGER DEFAULT NULL,
  "value" TEXT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS "ezpolicy_limitation_value_val" ON "ezpolicy_limitation_value" ("value");
CREATE INDEX IF NOT EXISTS "ezpolicy_limit_value_limit_id" ON "ezpolicy_limitation_value" ("limitation_id");

-- ezpreferences
CREATE TABLE IF NOT EXISTS "ezpreferences" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "name" TEXT DEFAULT NULL,
  "user_id" INTEGER NOT NULL DEFAULT 0,
  "value" TEXT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS "ezpreferences_name" ON "ezpreferences" ("name");
CREATE INDEX IF NOT EXISTS "ezpreferences_user_id_idx" ON "ezpreferences" ("user_id","name");

-- ezprest_authcode
CREATE TABLE IF NOT EXISTS "ezprest_authcode" (
  "client_id" TEXT NOT NULL DEFAULT '',
  "expirytime" INTEGER NOT NULL DEFAULT 0,
  "id" TEXT NOT NULL DEFAULT '',
  "scope" TEXT DEFAULT NULL,
  "user_id" INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY ("id")
);
CREATE INDEX IF NOT EXISTS "authcode_client_id" ON "ezprest_authcode" ("client_id");

-- ezprest_authorized_clients
CREATE TABLE IF NOT EXISTS "ezprest_authorized_clients" (
  "created" INTEGER DEFAULT NULL,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "rest_client_id" INTEGER DEFAULT NULL,
  "user_id" INTEGER DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS "client_user" ON "ezprest_authorized_clients" ("rest_client_id","user_id");

-- ezprest_clients
CREATE TABLE IF NOT EXISTS "ezprest_clients" (
  "client_id" TEXT DEFAULT NULL,
  "client_secret" TEXT DEFAULT NULL,
  "created" INTEGER NOT NULL DEFAULT 0,
  "description" TEXT DEFAULT NULL,
  "endpoint_uri" TEXT DEFAULT NULL,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "name" TEXT DEFAULT NULL,
  "owner_id" INTEGER NOT NULL DEFAULT 0,
  "updated" INTEGER NOT NULL DEFAULT 0,
  "version" INTEGER NOT NULL DEFAULT 0
);
CREATE UNIQUE INDEX IF NOT EXISTS "client_id_unique" ON "ezprest_clients" ("client_id","version");

-- ezprest_token
CREATE TABLE IF NOT EXISTS "ezprest_token" (
  "client_id" TEXT NOT NULL DEFAULT '',
  "expirytime" INTEGER NOT NULL DEFAULT 0,
  "id" TEXT NOT NULL DEFAULT '',
  "refresh_token" TEXT NOT NULL DEFAULT '',
  "scope" TEXT DEFAULT NULL,
  "user_id" INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY ("id")
);
CREATE INDEX IF NOT EXISTS "token_client_id" ON "ezprest_token" ("client_id");

-- ezproductcategory
CREATE TABLE IF NOT EXISTS "ezproductcategory" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "name" TEXT NOT NULL DEFAULT ''
);

-- ezproductcollection
CREATE TABLE IF NOT EXISTS "ezproductcollection" (
  "created" INTEGER DEFAULT NULL,
  "currency_code" TEXT NOT NULL DEFAULT '',
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL
);

-- ezproductcollection_item
CREATE TABLE IF NOT EXISTS "ezproductcollection_item" (
  "contentobject_id" INTEGER NOT NULL DEFAULT 0,
  "discount" REAL DEFAULT NULL,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "is_vat_inc" INTEGER DEFAULT NULL,
  "item_count" INTEGER NOT NULL DEFAULT 0,
  "name" TEXT NOT NULL DEFAULT '',
  "price" REAL DEFAULT 0,
  "productcollection_id" INTEGER NOT NULL DEFAULT 0,
  "vat_value" REAL DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS "ezproductcollection_item_contentobject_id" ON "ezproductcollection_item" ("contentobject_id");
CREATE INDEX IF NOT EXISTS "ezproductcollection_item_productcollection_id" ON "ezproductcollection_item" ("productcollection_id");

-- ezproductcollection_item_opt
CREATE TABLE IF NOT EXISTS "ezproductcollection_item_opt" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "item_id" INTEGER NOT NULL DEFAULT 0,
  "name" TEXT NOT NULL DEFAULT '',
  "object_attribute_id" INTEGER DEFAULT NULL,
  "option_item_id" INTEGER NOT NULL DEFAULT 0,
  "price" REAL NOT NULL DEFAULT 0,
  "value" TEXT NOT NULL DEFAULT ''
);
CREATE INDEX IF NOT EXISTS "ezproductcollection_item_opt_item_id" ON "ezproductcollection_item_opt" ("item_id");

-- ezpublishingqueueprocesses
CREATE TABLE IF NOT EXISTS "ezpublishingqueueprocesses" (
  "created" INTEGER DEFAULT NULL,
  "ezcontentobject_version_id" INTEGER NOT NULL DEFAULT 0,
  "finished" INTEGER DEFAULT NULL,
  "pid" INTEGER DEFAULT NULL,
  "started" INTEGER DEFAULT NULL,
  "status" INTEGER DEFAULT NULL,
  PRIMARY KEY ("ezcontentobject_version_id")
);

-- ezrole
CREATE TABLE IF NOT EXISTS "ezrole" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "is_new" INTEGER NOT NULL DEFAULT 0,
  "name" TEXT NOT NULL DEFAULT '',
  "value" TEXT DEFAULT NULL,
  "version" INTEGER DEFAULT 0
);

-- ezrss_export
CREATE TABLE IF NOT EXISTS "ezrss_export" (
  "access_url" TEXT DEFAULT NULL,
  "active" INTEGER DEFAULT NULL,
  "created" INTEGER DEFAULT NULL,
  "creator_id" INTEGER DEFAULT NULL,
  "description" TEXT DEFAULT NULL,
  "id" INTEGER NOT NULL,
  "image_id" INTEGER DEFAULT NULL,
  "main_node_only" INTEGER NOT NULL DEFAULT 1,
  "modified" INTEGER DEFAULT NULL,
  "modifier_id" INTEGER DEFAULT NULL,
  "node_id" INTEGER DEFAULT NULL,
  "number_of_objects" INTEGER NOT NULL DEFAULT 0,
  "rss_version" TEXT DEFAULT NULL,
  "site_access" TEXT DEFAULT NULL,
  "status" INTEGER NOT NULL DEFAULT 0,
  "title" TEXT DEFAULT NULL,
  "url" TEXT DEFAULT NULL,
  PRIMARY KEY ("id","status")
);

-- ezrss_export_item
CREATE TABLE IF NOT EXISTS "ezrss_export_item" (
  "category" TEXT DEFAULT NULL,
  "class_id" INTEGER DEFAULT NULL,
  "description" TEXT DEFAULT NULL,
  "enclosure" TEXT DEFAULT NULL,
  "id" INTEGER NOT NULL,
  "rssexport_id" INTEGER DEFAULT NULL,
  "source_node_id" INTEGER DEFAULT NULL,
  "status" INTEGER NOT NULL DEFAULT 0,
  "subnodes" INTEGER NOT NULL DEFAULT 0,
  "title" TEXT DEFAULT NULL,
  PRIMARY KEY ("id","status")
);
CREATE INDEX IF NOT EXISTS "ezrss_export_rsseid" ON "ezrss_export_item" ("rssexport_id");

-- ezrss_import
CREATE TABLE IF NOT EXISTS "ezrss_import" (
  "active" INTEGER DEFAULT NULL,
  "class_description" TEXT DEFAULT NULL,
  "class_id" INTEGER DEFAULT NULL,
  "class_title" TEXT DEFAULT NULL,
  "class_url" TEXT DEFAULT NULL,
  "created" INTEGER DEFAULT NULL,
  "creator_id" INTEGER DEFAULT NULL,
  "destination_node_id" INTEGER DEFAULT NULL,
  "id" INTEGER NOT NULL,
  "import_description" TEXT NOT NULL,
  "modified" INTEGER DEFAULT NULL,
  "modifier_id" INTEGER DEFAULT NULL,
  "name" TEXT DEFAULT NULL,
  "object_owner_id" INTEGER DEFAULT NULL,
  "status" INTEGER NOT NULL DEFAULT 0,
  "url" TEXT DEFAULT NULL,
  PRIMARY KEY ("id","status")
);

-- ezscheduled_script
CREATE TABLE IF NOT EXISTS "ezscheduled_script" (
  "command" TEXT NOT NULL DEFAULT '',
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "last_report_timestamp" INTEGER NOT NULL DEFAULT 0,
  "name" TEXT NOT NULL DEFAULT '',
  "process_id" INTEGER NOT NULL DEFAULT 0,
  "progress" INTEGER DEFAULT 0,
  "user_id" INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS "ezscheduled_script_timestamp" ON "ezscheduled_script" ("last_report_timestamp");

-- ezsearch_object_word_link
CREATE TABLE IF NOT EXISTS "ezsearch_object_word_link" (
  "contentclass_attribute_id" INTEGER NOT NULL DEFAULT 0,
  "contentclass_id" INTEGER NOT NULL DEFAULT 0,
  "contentobject_id" INTEGER NOT NULL DEFAULT 0,
  "frequency" REAL NOT NULL DEFAULT 0,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "identifier" TEXT NOT NULL DEFAULT '',
  "integer_value" INTEGER NOT NULL DEFAULT 0,
  "next_word_id" INTEGER NOT NULL DEFAULT 0,
  "placement" INTEGER NOT NULL DEFAULT 0,
  "prev_word_id" INTEGER NOT NULL DEFAULT 0,
  "published" INTEGER NOT NULL DEFAULT 0,
  "section_id" INTEGER NOT NULL DEFAULT 0,
  "word_id" INTEGER NOT NULL DEFAULT 0,
  "language_mask" INTEGER NOT NULL DEFAULT 1
);
CREATE INDEX IF NOT EXISTS "ezsearch_object_word_link_frequency" ON "ezsearch_object_word_link" ("frequency");
CREATE INDEX IF NOT EXISTS "ezsearch_object_word_link_identifier" ON "ezsearch_object_word_link" ("identifier");
CREATE INDEX IF NOT EXISTS "ezsearch_object_word_link_integer_value" ON "ezsearch_object_word_link" ("integer_value");
CREATE INDEX IF NOT EXISTS "ezsearch_object_word_link_object" ON "ezsearch_object_word_link" ("contentobject_id");
CREATE INDEX IF NOT EXISTS "ezsearch_object_word_link_word" ON "ezsearch_object_word_link" ("word_id");

-- ezsearch_search_phrase
CREATE TABLE IF NOT EXISTS "ezsearch_search_phrase" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "phrase" TEXT DEFAULT NULL,
  "phrase_count" INTEGER DEFAULT 0,
  "result_count" INTEGER DEFAULT 0
);
CREATE UNIQUE INDEX IF NOT EXISTS "ezsearch_search_phrase_phrase" ON "ezsearch_search_phrase" ("phrase");
CREATE INDEX IF NOT EXISTS "ezsearch_search_phrase_count" ON "ezsearch_search_phrase" ("phrase_count");

-- ezsearch_word
CREATE TABLE IF NOT EXISTS "ezsearch_word" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "object_count" INTEGER NOT NULL DEFAULT 0,
  "word" TEXT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS "ezsearch_word_obj_count" ON "ezsearch_word" ("object_count");
CREATE INDEX IF NOT EXISTS "ezsearch_word_word_i" ON "ezsearch_word" ("word");

-- ezsection
CREATE TABLE IF NOT EXISTS "ezsection" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "identifier" TEXT DEFAULT NULL,
  "locale" TEXT DEFAULT NULL,
  "name" TEXT DEFAULT NULL,
  "navigation_part_identifier" TEXT DEFAULT 'ezcontentnavigationpart'
);

-- ezsession
CREATE TABLE IF NOT EXISTS "ezsession" (
  "data" TEXT NOT NULL,
  "expiration_time" INTEGER NOT NULL DEFAULT 0,
  "session_key" TEXT NOT NULL DEFAULT '',
  "user_hash" TEXT NOT NULL DEFAULT '',
  "user_id" INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY ("session_key")
);
CREATE INDEX IF NOT EXISTS "expiration_time" ON "ezsession" ("expiration_time");
CREATE INDEX IF NOT EXISTS "ezsession_user_id" ON "ezsession" ("user_id");

-- ezsite_data
CREATE TABLE IF NOT EXISTS "ezsite_data" (
  "name" TEXT NOT NULL DEFAULT '',
  "value" TEXT NOT NULL,
  PRIMARY KEY ("name")
);

-- ezstarrating
CREATE TABLE IF NOT EXISTS "ezstarrating" (
  "contentobject_id" INTEGER NOT NULL,
  "contentobject_attribute_id" INTEGER NOT NULL,
  "rating_average" REAL NOT NULL,
  "rating_count" INTEGER NOT NULL,
  PRIMARY KEY ("contentobject_id","contentobject_attribute_id")
);

-- ezstarrating_data
CREATE TABLE IF NOT EXISTS "ezstarrating_data" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "created_at" INTEGER NOT NULL,
  "user_id" INTEGER NOT NULL,
  "session_key" TEXT NOT NULL,
  "rating" REAL NOT NULL,
  "contentobject_id" INTEGER NOT NULL,
  "contentobject_attribute_id" INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS "user_id_session_key" ON "ezstarrating_data" ("user_id","session_key");
CREATE INDEX IF NOT EXISTS "contentobject_id_contentobject_attribute_id" ON "ezstarrating_data" ("contentobject_id","contentobject_attribute_id");

-- ezsubtree_notification_rule
CREATE TABLE IF NOT EXISTS "ezsubtree_notification_rule" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "node_id" INTEGER NOT NULL DEFAULT 0,
  "use_digest" INTEGER DEFAULT 0,
  "user_id" INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS "ezsubtree_notification_rule_user_id" ON "ezsubtree_notification_rule" ("user_id");

-- eztags
CREATE TABLE IF NOT EXISTS "eztags" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "parent_id" INTEGER NOT NULL DEFAULT 0,
  "main_tag_id" INTEGER NOT NULL DEFAULT 0,
  "keyword" TEXT NOT NULL DEFAULT '',
  "depth" INTEGER NOT NULL DEFAULT 1,
  "path_string" TEXT NOT NULL DEFAULT '',
  "modified" INTEGER NOT NULL DEFAULT 0,
  "remote_id" TEXT NOT NULL DEFAULT '',
  "main_language_id" INTEGER NOT NULL DEFAULT 0,
  "language_mask" INTEGER NOT NULL DEFAULT 0
);
CREATE UNIQUE INDEX IF NOT EXISTS "idx_eztags_remote_id" ON "eztags" ("remote_id");
CREATE INDEX IF NOT EXISTS "idx_eztags_keyword" ON "eztags" ("keyword");
CREATE INDEX IF NOT EXISTS "idx_eztags_keyword_id" ON "eztags" ("keyword","id");

-- eztags_attribute_link
CREATE TABLE IF NOT EXISTS "eztags_attribute_link" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "keyword_id" INTEGER NOT NULL DEFAULT 0,
  "objectattribute_id" INTEGER NOT NULL DEFAULT 0,
  "objectattribute_version" INTEGER NOT NULL DEFAULT 0,
  "object_id" INTEGER NOT NULL DEFAULT 0,
  "priority" INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS "idx_eztags_attr_link_keyword_id" ON "eztags_attribute_link" ("keyword_id");
CREATE INDEX IF NOT EXISTS "idx_eztags_attr_link_kid_oaid_oav" ON "eztags_attribute_link" ("keyword_id","objectattribute_id","objectattribute_version");
CREATE INDEX IF NOT EXISTS "idx_eztags_attr_link_kid_oid" ON "eztags_attribute_link" ("keyword_id","object_id");
CREATE INDEX IF NOT EXISTS "idx_eztags_attr_link_oaid_oav" ON "eztags_attribute_link" ("objectattribute_id","objectattribute_version");

-- eztags_keyword
CREATE TABLE IF NOT EXISTS "eztags_keyword" (
  "keyword_id" INTEGER NOT NULL DEFAULT 0,
  "language_id" INTEGER NOT NULL DEFAULT 0,
  "keyword" TEXT NOT NULL DEFAULT '',
  "locale" TEXT NOT NULL DEFAULT '',
  "status" INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY ("keyword_id","locale")
);

-- eztipafriend_counter
CREATE TABLE IF NOT EXISTS "eztipafriend_counter" (
  "count" INTEGER NOT NULL DEFAULT 0,
  "node_id" INTEGER NOT NULL DEFAULT 0,
  "requested" INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY ("node_id","requested")
);

-- eztipafriend_request
CREATE TABLE IF NOT EXISTS "eztipafriend_request" (
  "created" INTEGER NOT NULL DEFAULT 0,
  "email_receiver" TEXT NOT NULL DEFAULT ''
);
CREATE INDEX IF NOT EXISTS "eztipafriend_request_created" ON "eztipafriend_request" ("created");
CREATE INDEX IF NOT EXISTS "eztipafriend_request_email_rec" ON "eztipafriend_request" ("email_receiver");

-- eztrigger
CREATE TABLE IF NOT EXISTS "eztrigger" (
  "connect_type" TEXT NOT NULL DEFAULT '',
  "function_name" TEXT NOT NULL DEFAULT '',
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "module_name" TEXT NOT NULL DEFAULT '',
  "name" TEXT DEFAULT NULL,
  "workflow_id" INTEGER DEFAULT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS "eztrigger_def_id" ON "eztrigger" ("module_name","function_name","connect_type");
CREATE INDEX IF NOT EXISTS "eztrigger_fetch" ON "eztrigger" ("name","module_name","function_name");

-- ezurl
CREATE TABLE IF NOT EXISTS "ezurl" (
  "created" INTEGER NOT NULL DEFAULT 0,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "is_valid" INTEGER NOT NULL DEFAULT 1,
  "last_checked" INTEGER NOT NULL DEFAULT 0,
  "modified" INTEGER NOT NULL DEFAULT 0,
  "original_url_md5" TEXT NOT NULL DEFAULT '',
  "url" TEXT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS "ezurl_url" ON "ezurl" ("url");

-- ezurl_object_link
CREATE TABLE IF NOT EXISTS "ezurl_object_link" (
  "contentobject_attribute_id" INTEGER NOT NULL DEFAULT 0,
  "contentobject_attribute_version" INTEGER NOT NULL DEFAULT 0,
  "url_id" INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS "ezurl_ol_coa_id" ON "ezurl_object_link" ("contentobject_attribute_id");
CREATE INDEX IF NOT EXISTS "ezurl_ol_coa_version" ON "ezurl_object_link" ("contentobject_attribute_version");
CREATE INDEX IF NOT EXISTS "ezurl_ol_url_id" ON "ezurl_object_link" ("url_id");

-- ezurlalias
CREATE TABLE IF NOT EXISTS "ezurlalias" (
  "destination_url" TEXT NOT NULL,
  "forward_to_id" INTEGER NOT NULL DEFAULT 0,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "is_imported" INTEGER NOT NULL DEFAULT 0,
  "is_internal" INTEGER NOT NULL DEFAULT 1,
  "is_wildcard" INTEGER NOT NULL DEFAULT 0,
  "source_md5" TEXT DEFAULT NULL,
  "source_url" TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS "ezurlalias_desturl" ON "ezurlalias" ("destination_url");
CREATE INDEX IF NOT EXISTS "ezurlalias_forward_to_id" ON "ezurlalias" ("forward_to_id");
CREATE INDEX IF NOT EXISTS "ezurlalias_imp_wcard_fwd" ON "ezurlalias" ("is_imported","is_wildcard","forward_to_id");
CREATE INDEX IF NOT EXISTS "ezurlalias_source_md5" ON "ezurlalias" ("source_md5");
CREATE INDEX IF NOT EXISTS "ezurlalias_source_url" ON "ezurlalias" ("source_url");
CREATE INDEX IF NOT EXISTS "ezurlalias_wcard_fwd" ON "ezurlalias" ("is_wildcard","forward_to_id");

-- ezurlalias_ml
CREATE TABLE IF NOT EXISTS "ezurlalias_ml" (
  "action" TEXT NOT NULL,
  "action_type" TEXT NOT NULL DEFAULT '',
  "alias_redirects" INTEGER NOT NULL DEFAULT 1,
  "id" INTEGER NOT NULL DEFAULT 0,
  "is_alias" INTEGER NOT NULL DEFAULT 0,
  "is_original" INTEGER NOT NULL DEFAULT 0,
  "lang_mask" INTEGER NOT NULL DEFAULT 0,
  "link" INTEGER NOT NULL DEFAULT 0,
  "parent" INTEGER NOT NULL DEFAULT 0,
  "text" TEXT NOT NULL,
  "text_md5" TEXT NOT NULL DEFAULT '',
  PRIMARY KEY ("parent","text_md5")
);
CREATE INDEX IF NOT EXISTS "ezurlalias_ml_act_org" ON "ezurlalias_ml" ("action","is_original");
CREATE INDEX IF NOT EXISTS "ezurlalias_ml_actt_org_al" ON "ezurlalias_ml" ("action_type","is_original","is_alias");
CREATE INDEX IF NOT EXISTS "ezurlalias_ml_id" ON "ezurlalias_ml" ("id");
CREATE INDEX IF NOT EXISTS "ezurlalias_ml_par_act_id_lnk" ON "ezurlalias_ml" ("action","id","link","parent");
CREATE INDEX IF NOT EXISTS "ezurlalias_ml_par_lnk_txt" ON "ezurlalias_ml" ("parent","text","link");
CREATE INDEX IF NOT EXISTS "ezurlalias_ml_text" ON "ezurlalias_ml" ("text","id","link");
CREATE INDEX IF NOT EXISTS "ezurlalias_ml_text_lang" ON "ezurlalias_ml" ("text","lang_mask","parent");

-- ezurlalias_ml_incr
CREATE TABLE IF NOT EXISTS "ezurlalias_ml_incr" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL
);

-- ezurlwildcard
CREATE TABLE IF NOT EXISTS "ezurlwildcard" (
  "destination_url" TEXT NOT NULL,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "source_url" TEXT NOT NULL,
  "type" INTEGER NOT NULL DEFAULT 0
);

-- ezuser
CREATE TABLE IF NOT EXISTS "ezuser" (
  "contentobject_id" INTEGER NOT NULL DEFAULT 0,
  "email" TEXT NOT NULL DEFAULT '',
  "login" TEXT NOT NULL DEFAULT '',
  "password_hash" TEXT DEFAULT NULL,
  "password_hash_type" INTEGER NOT NULL DEFAULT 1,
  "password_updated_at" INTEGER DEFAULT NULL,
  PRIMARY KEY ("contentobject_id")
);
CREATE UNIQUE INDEX IF NOT EXISTS "ezuser_login" ON "ezuser" ("login");

-- ezuser_accountkey
CREATE TABLE IF NOT EXISTS "ezuser_accountkey" (
  "hash_key" TEXT NOT NULL DEFAULT '',
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "time" INTEGER NOT NULL DEFAULT 0,
  "user_id" INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS "hash_key" ON "ezuser_accountkey" ("hash_key");

-- ezuser_discountrule
CREATE TABLE IF NOT EXISTS "ezuser_discountrule" (
  "contentobject_id" INTEGER DEFAULT NULL,
  "discountrule_id" INTEGER DEFAULT NULL,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "name" TEXT NOT NULL DEFAULT ''
);

-- ezuser_role
CREATE TABLE IF NOT EXISTS "ezuser_role" (
  "contentobject_id" INTEGER DEFAULT NULL,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "limit_identifier" TEXT DEFAULT '',
  "limit_value" TEXT DEFAULT '',
  "role_id" INTEGER DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS "ezuser_role_contentobject_id" ON "ezuser_role" ("contentobject_id");
CREATE INDEX IF NOT EXISTS "ezuser_role_role_id" ON "ezuser_role" ("role_id");

-- ezuser_setting
CREATE TABLE IF NOT EXISTS "ezuser_setting" (
  "is_enabled" INTEGER NOT NULL DEFAULT 0,
  "max_login" INTEGER DEFAULT NULL,
  "user_id" INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY ("user_id")
);

-- ezuservisit
CREATE TABLE IF NOT EXISTS "ezuservisit" (
  "current_visit_timestamp" INTEGER NOT NULL DEFAULT 0,
  "failed_login_attempts" INTEGER NOT NULL DEFAULT 0,
  "last_visit_timestamp" INTEGER NOT NULL DEFAULT 0,
  "login_count" INTEGER NOT NULL DEFAULT 0,
  "user_id" INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY ("user_id")
);
CREATE INDEX IF NOT EXISTS "ezuservisit_co_visit_count" ON "ezuservisit" ("current_visit_timestamp","login_count");

-- ezvatrule
CREATE TABLE IF NOT EXISTS "ezvatrule" (
  "country_code" TEXT NOT NULL DEFAULT '',
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "vat_type" INTEGER NOT NULL DEFAULT 0
);

-- ezvatrule_product_category
CREATE TABLE IF NOT EXISTS "ezvatrule_product_category" (
  "product_category_id" INTEGER NOT NULL DEFAULT 0,
  "vatrule_id" INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY ("vatrule_id","product_category_id")
);

-- ezvattype
CREATE TABLE IF NOT EXISTS "ezvattype" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "name" TEXT NOT NULL DEFAULT '',
  "percentage" REAL DEFAULT NULL
);

-- ezview_counter
CREATE TABLE IF NOT EXISTS "ezview_counter" (
  "count" INTEGER NOT NULL DEFAULT 0,
  "node_id" INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY ("node_id")
);

-- ezwaituntildatevalue
CREATE TABLE IF NOT EXISTS "ezwaituntildatevalue" (
  "contentclass_attribute_id" INTEGER NOT NULL DEFAULT 0,
  "contentclass_id" INTEGER NOT NULL DEFAULT 0,
  "id" INTEGER NOT NULL,
  "workflow_event_id" INTEGER NOT NULL DEFAULT 0,
  "workflow_event_version" INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY ("id","workflow_event_id","workflow_event_version")
);
CREATE INDEX IF NOT EXISTS "ezwaituntildateevalue_wf_ev_id_wf_ver" ON "ezwaituntildatevalue" ("workflow_event_id","workflow_event_version");

-- ezwishlist
CREATE TABLE IF NOT EXISTS "ezwishlist" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "productcollection_id" INTEGER NOT NULL DEFAULT 0,
  "user_id" INTEGER NOT NULL DEFAULT 0
);

-- ezworkflow
CREATE TABLE IF NOT EXISTS "ezworkflow" (
  "created" INTEGER NOT NULL DEFAULT 0,
  "creator_id" INTEGER NOT NULL DEFAULT 0,
  "id" INTEGER NOT NULL,
  "is_enabled" INTEGER NOT NULL DEFAULT 0,
  "modified" INTEGER NOT NULL DEFAULT 0,
  "modifier_id" INTEGER NOT NULL DEFAULT 0,
  "name" TEXT NOT NULL DEFAULT '',
  "version" INTEGER NOT NULL DEFAULT 0,
  "workflow_type_string" TEXT NOT NULL DEFAULT '',
  PRIMARY KEY ("id","version")
);

-- ezworkflow_assign
CREATE TABLE IF NOT EXISTS "ezworkflow_assign" (
  "access_type" INTEGER NOT NULL DEFAULT 0,
  "as_tree" INTEGER NOT NULL DEFAULT 0,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "node_id" INTEGER NOT NULL DEFAULT 0,
  "workflow_id" INTEGER NOT NULL DEFAULT 0
);

-- ezworkflow_event
CREATE TABLE IF NOT EXISTS "ezworkflow_event" (
  "data_int1" INTEGER DEFAULT NULL,
  "data_int2" INTEGER DEFAULT NULL,
  "data_int3" INTEGER DEFAULT NULL,
  "data_int4" INTEGER DEFAULT NULL,
  "data_text1" TEXT DEFAULT NULL,
  "data_text2" TEXT DEFAULT NULL,
  "data_text3" TEXT DEFAULT NULL,
  "data_text4" TEXT DEFAULT NULL,
  "data_text5" TEXT DEFAULT NULL,
  "description" TEXT NOT NULL DEFAULT '',
  "id" INTEGER NOT NULL,
  "placement" INTEGER NOT NULL DEFAULT 0,
  "version" INTEGER NOT NULL DEFAULT 0,
  "workflow_id" INTEGER NOT NULL DEFAULT 0,
  "workflow_type_string" TEXT NOT NULL DEFAULT '',
  PRIMARY KEY ("id","version")
);
CREATE INDEX IF NOT EXISTS "wid_version_placement" ON "ezworkflow_event" ("workflow_id","version","placement");

-- ezworkflow_group
CREATE TABLE IF NOT EXISTS "ezworkflow_group" (
  "created" INTEGER NOT NULL DEFAULT 0,
  "creator_id" INTEGER NOT NULL DEFAULT 0,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "modified" INTEGER NOT NULL DEFAULT 0,
  "modifier_id" INTEGER NOT NULL DEFAULT 0,
  "name" TEXT NOT NULL DEFAULT ''
);

-- ezworkflow_group_link
CREATE TABLE IF NOT EXISTS "ezworkflow_group_link" (
  "group_id" INTEGER NOT NULL DEFAULT 0,
  "group_name" TEXT DEFAULT NULL,
  "workflow_id" INTEGER NOT NULL DEFAULT 0,
  "workflow_version" INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY ("workflow_id","group_id","workflow_version")
);

-- ezworkflow_process
CREATE TABLE IF NOT EXISTS "ezworkflow_process" (
  "activation_date" INTEGER DEFAULT NULL,
  "content_id" INTEGER NOT NULL DEFAULT 0,
  "content_version" INTEGER NOT NULL DEFAULT 0,
  "created" INTEGER NOT NULL DEFAULT 0,
  "event_id" INTEGER NOT NULL DEFAULT 0,
  "event_position" INTEGER NOT NULL DEFAULT 0,
  "event_state" INTEGER DEFAULT NULL,
  "event_status" INTEGER NOT NULL DEFAULT 0,
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "last_event_id" INTEGER NOT NULL DEFAULT 0,
  "last_event_position" INTEGER NOT NULL DEFAULT 0,
  "last_event_status" INTEGER NOT NULL DEFAULT 0,
  "memento_key" TEXT DEFAULT NULL,
  "modified" INTEGER NOT NULL DEFAULT 0,
  "node_id" INTEGER NOT NULL DEFAULT 0,
  "parameters" TEXT DEFAULT NULL,
  "process_key" TEXT NOT NULL DEFAULT '',
  "session_key" TEXT NOT NULL DEFAULT '0',
  "status" INTEGER DEFAULT NULL,
  "user_id" INTEGER NOT NULL DEFAULT 0,
  "workflow_id" INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS "ezworkflow_process_process_key" ON "ezworkflow_process" ("process_key");

-- nglayouts_block
CREATE TABLE IF NOT EXISTS "nglayouts_block" (
  "id" INTEGER NOT NULL,
  "status" INTEGER NOT NULL,
  "uuid" TEXT NOT NULL,
  "layout_id" INTEGER NOT NULL,
  "depth" INTEGER NOT NULL,
  "path" TEXT NOT NULL,
  "parent_id" INTEGER DEFAULT NULL,
  "placeholder" TEXT DEFAULT NULL,
  "position" INTEGER DEFAULT NULL,
  "definition_identifier" TEXT NOT NULL,
  "view_type" TEXT NOT NULL,
  "item_view_type" TEXT NOT NULL,
  "name" TEXT NOT NULL,
  "config" TEXT NOT NULL,
  "translatable" INTEGER NOT NULL,
  "main_locale" TEXT NOT NULL,
  "always_available" INTEGER NOT NULL,
  PRIMARY KEY ("id","status")
);
CREATE UNIQUE INDEX IF NOT EXISTS "idx_ngl_block_uuid" ON "nglayouts_block" ("uuid","status");
CREATE INDEX IF NOT EXISTS "idx_ngl_layout" ON "nglayouts_block" ("layout_id","status");
CREATE INDEX IF NOT EXISTS "idx_ngl_parent_block" ON "nglayouts_block" ("parent_id","placeholder","status");

-- nglayouts_block_collection
CREATE TABLE IF NOT EXISTS "nglayouts_block_collection" (
  "block_id" INTEGER NOT NULL,
  "block_status" INTEGER NOT NULL,
  "identifier" TEXT NOT NULL,
  "collection_id" INTEGER NOT NULL,
  "collection_status" INTEGER NOT NULL,
  PRIMARY KEY ("block_id","block_status","identifier")
);
CREATE INDEX IF NOT EXISTS "idx_ngl_block" ON "nglayouts_block_collection" ("block_id","block_status");
CREATE INDEX IF NOT EXISTS "idx_ngl_collection" ON "nglayouts_block_collection" ("collection_id","collection_status");

-- nglayouts_block_translation
CREATE TABLE IF NOT EXISTS "nglayouts_block_translation" (
  "block_id" INTEGER NOT NULL,
  "status" INTEGER NOT NULL,
  "locale" TEXT NOT NULL,
  "parameters" TEXT NOT NULL,
  PRIMARY KEY ("block_id","status","locale")
);

-- nglayouts_collection
CREATE TABLE IF NOT EXISTS "nglayouts_collection" (
  "id" INTEGER NOT NULL,
  "status" INTEGER NOT NULL,
  "uuid" TEXT NOT NULL,
  "start" INTEGER NOT NULL,
  "length" INTEGER DEFAULT NULL,
  "translatable" INTEGER NOT NULL,
  "main_locale" TEXT NOT NULL,
  "always_available" INTEGER NOT NULL,
  PRIMARY KEY ("id","status")
);
CREATE UNIQUE INDEX IF NOT EXISTS "idx_ngl_collection_uuid" ON "nglayouts_collection" ("uuid","status");

-- nglayouts_collection_item
CREATE TABLE IF NOT EXISTS "nglayouts_collection_item" (
  "id" INTEGER NOT NULL,
  "status" INTEGER NOT NULL,
  "uuid" TEXT NOT NULL,
  "collection_id" INTEGER NOT NULL,
  "position" INTEGER NOT NULL,
  "value" TEXT DEFAULT NULL,
  "value_type" TEXT NOT NULL,
  "view_type" TEXT DEFAULT NULL,
  "config" TEXT NOT NULL,
  PRIMARY KEY ("id","status")
);
CREATE UNIQUE INDEX IF NOT EXISTS "idx_ngl_collection_item_uuid" ON "nglayouts_collection_item" ("uuid","status");
CREATE INDEX IF NOT EXISTS "idx_ngl_collection" ON "nglayouts_collection_item" ("collection_id","status");

-- nglayouts_collection_query
CREATE TABLE IF NOT EXISTS "nglayouts_collection_query" (
  "id" INTEGER NOT NULL,
  "status" INTEGER NOT NULL,
  "uuid" TEXT NOT NULL,
  "collection_id" INTEGER NOT NULL,
  "type" TEXT NOT NULL,
  PRIMARY KEY ("id","status")
);
CREATE UNIQUE INDEX IF NOT EXISTS "idx_ngl_collection_query_uuid" ON "nglayouts_collection_query" ("uuid","status");
CREATE INDEX IF NOT EXISTS "idx_ngl_collection" ON "nglayouts_collection_query" ("collection_id","status");

-- nglayouts_collection_query_translation
CREATE TABLE IF NOT EXISTS "nglayouts_collection_query_translation" (
  "query_id" INTEGER NOT NULL,
  "status" INTEGER NOT NULL,
  "locale" TEXT NOT NULL,
  "parameters" TEXT NOT NULL,
  PRIMARY KEY ("query_id","status","locale")
);

-- nglayouts_collection_slot
CREATE TABLE IF NOT EXISTS "nglayouts_collection_slot" (
  "id" INTEGER NOT NULL,
  "status" INTEGER NOT NULL,
  "uuid" TEXT NOT NULL,
  "collection_id" INTEGER NOT NULL,
  "position" INTEGER NOT NULL,
  "view_type" TEXT DEFAULT NULL,
  PRIMARY KEY ("id","status")
);
CREATE UNIQUE INDEX IF NOT EXISTS "idx_ngl_collection_slot_uuid" ON "nglayouts_collection_slot" ("uuid","status");
CREATE INDEX IF NOT EXISTS "idx_ngl_collection" ON "nglayouts_collection_slot" ("collection_id","status");
CREATE INDEX IF NOT EXISTS "idx_ngl_position" ON "nglayouts_collection_slot" ("collection_id","position");

-- nglayouts_collection_translation
CREATE TABLE IF NOT EXISTS "nglayouts_collection_translation" (
  "collection_id" INTEGER NOT NULL,
  "status" INTEGER NOT NULL,
  "locale" TEXT NOT NULL,
  PRIMARY KEY ("collection_id","status","locale")
);

-- nglayouts_layout
CREATE TABLE IF NOT EXISTS "nglayouts_layout" (
  "id" INTEGER NOT NULL,
  "status" INTEGER NOT NULL,
  "uuid" TEXT NOT NULL,
  "type" TEXT NOT NULL,
  "name" TEXT NOT NULL,
  "description" TEXT NOT NULL,
  "created" INTEGER NOT NULL,
  "modified" INTEGER NOT NULL,
  "shared" INTEGER NOT NULL,
  "main_locale" TEXT NOT NULL,
  PRIMARY KEY ("id","status")
);
CREATE UNIQUE INDEX IF NOT EXISTS "idx_ngl_layout_uuid" ON "nglayouts_layout" ("uuid","status");
CREATE INDEX IF NOT EXISTS "idx_ngl_layout_name" ON "nglayouts_layout" ("name");
CREATE INDEX IF NOT EXISTS "idx_ngl_layout_type" ON "nglayouts_layout" ("type");
CREATE INDEX IF NOT EXISTS "idx_ngl_layout_shared" ON "nglayouts_layout" ("shared");

-- nglayouts_layout_translation
CREATE TABLE IF NOT EXISTS "nglayouts_layout_translation" (
  "layout_id" INTEGER NOT NULL,
  "status" INTEGER NOT NULL,
  "locale" TEXT NOT NULL,
  PRIMARY KEY ("layout_id","status","locale")
);

-- nglayouts_migration_versions
CREATE TABLE IF NOT EXISTS "nglayouts_migration_versions" (
  "version" TEXT NOT NULL,
  "executed_at" TEXT DEFAULT NULL,
  "execution_time" INTEGER DEFAULT NULL,
  PRIMARY KEY ("version")
);

-- nglayouts_role
CREATE TABLE IF NOT EXISTS "nglayouts_role" (
  "id" INTEGER NOT NULL,
  "status" INTEGER NOT NULL,
  "uuid" TEXT NOT NULL,
  "name" TEXT NOT NULL,
  "identifier" TEXT NOT NULL,
  "description" TEXT NOT NULL,
  PRIMARY KEY ("id","status")
);
CREATE UNIQUE INDEX IF NOT EXISTS "idx_ngl_role_uuid" ON "nglayouts_role" ("uuid","status");
CREATE INDEX IF NOT EXISTS "idx_ngl_role_identifier" ON "nglayouts_role" ("identifier");

-- nglayouts_role_policy
CREATE TABLE IF NOT EXISTS "nglayouts_role_policy" (
  "id" INTEGER NOT NULL,
  "status" INTEGER NOT NULL,
  "uuid" TEXT NOT NULL,
  "role_id" INTEGER NOT NULL,
  "component" TEXT DEFAULT NULL,
  "permission" TEXT DEFAULT NULL,
  "limitations" TEXT NOT NULL,
  PRIMARY KEY ("id","status")
);
CREATE UNIQUE INDEX IF NOT EXISTS "idx_ngl_role_policy_uuid" ON "nglayouts_role_policy" ("uuid","status");
CREATE INDEX IF NOT EXISTS "idx_ngl_role" ON "nglayouts_role_policy" ("role_id","status");
CREATE INDEX IF NOT EXISTS "idx_ngl_policy_component" ON "nglayouts_role_policy" ("component");
CREATE INDEX IF NOT EXISTS "idx_ngl_policy_component_permission" ON "nglayouts_role_policy" ("component","permission");

-- nglayouts_rule
CREATE TABLE IF NOT EXISTS "nglayouts_rule" (
  "id" INTEGER NOT NULL,
  "status" INTEGER NOT NULL,
  "uuid" TEXT NOT NULL,
  "rule_group_id" INTEGER NOT NULL,
  "layout_uuid" TEXT DEFAULT NULL,
  "description" TEXT NOT NULL,
  PRIMARY KEY ("id","status")
);
CREATE UNIQUE INDEX IF NOT EXISTS "idx_ngl_rule_uuid" ON "nglayouts_rule" ("uuid","status");
CREATE INDEX IF NOT EXISTS "idx_ngl_related_layout" ON "nglayouts_rule" ("layout_uuid");

-- nglayouts_rule_condition
CREATE TABLE IF NOT EXISTS "nglayouts_rule_condition" (
  "id" INTEGER NOT NULL,
  "status" INTEGER NOT NULL,
  "uuid" TEXT NOT NULL,
  "type" TEXT NOT NULL,
  "value" TEXT DEFAULT NULL,
  PRIMARY KEY ("id","status")
);
CREATE UNIQUE INDEX IF NOT EXISTS "idx_ngl_rule_condition_uuid" ON "nglayouts_rule_condition" ("uuid","status");

-- nglayouts_rule_condition_rule
CREATE TABLE IF NOT EXISTS "nglayouts_rule_condition_rule" (
  "condition_id" INTEGER NOT NULL,
  "condition_status" INTEGER NOT NULL,
  "rule_id" INTEGER NOT NULL,
  "rule_status" INTEGER NOT NULL,
  PRIMARY KEY ("condition_id","condition_status")
);
CREATE INDEX IF NOT EXISTS "idx_ngl_rule" ON "nglayouts_rule_condition_rule" ("rule_id","rule_status");

-- nglayouts_rule_condition_rule_group
CREATE TABLE IF NOT EXISTS "nglayouts_rule_condition_rule_group" (
  "condition_id" INTEGER NOT NULL,
  "condition_status" INTEGER NOT NULL,
  "rule_group_id" INTEGER NOT NULL,
  "rule_group_status" INTEGER NOT NULL,
  PRIMARY KEY ("condition_id","condition_status")
);
CREATE INDEX IF NOT EXISTS "idx_ngl_rule_group" ON "nglayouts_rule_condition_rule_group" ("rule_group_id","rule_group_status");

-- nglayouts_rule_data
CREATE TABLE IF NOT EXISTS "nglayouts_rule_data" (
  "rule_id" INTEGER NOT NULL,
  "enabled" INTEGER NOT NULL,
  "priority" INTEGER NOT NULL,
  PRIMARY KEY ("rule_id")
);

-- nglayouts_rule_group
CREATE TABLE IF NOT EXISTS "nglayouts_rule_group" (
  "id" INTEGER NOT NULL,
  "status" INTEGER NOT NULL,
  "uuid" TEXT NOT NULL,
  "depth" INTEGER NOT NULL,
  "path" TEXT NOT NULL,
  "parent_id" INTEGER DEFAULT NULL,
  "name" TEXT NOT NULL,
  "description" TEXT NOT NULL,
  PRIMARY KEY ("id","status")
);
CREATE UNIQUE INDEX IF NOT EXISTS "idx_ngl_rule_group_uuid" ON "nglayouts_rule_group" ("uuid","status");
CREATE INDEX IF NOT EXISTS "idx_ngl_parent_rule_group" ON "nglayouts_rule_group" ("parent_id");

-- nglayouts_rule_group_data
CREATE TABLE IF NOT EXISTS "nglayouts_rule_group_data" (
  "rule_group_id" INTEGER NOT NULL,
  "enabled" INTEGER NOT NULL,
  "priority" INTEGER NOT NULL,
  PRIMARY KEY ("rule_group_id")
);

-- nglayouts_rule_target
CREATE TABLE IF NOT EXISTS "nglayouts_rule_target" (
  "id" INTEGER NOT NULL,
  "status" INTEGER NOT NULL,
  "uuid" TEXT NOT NULL,
  "rule_id" INTEGER NOT NULL,
  "type" TEXT NOT NULL,
  "value" TEXT DEFAULT NULL,
  PRIMARY KEY ("id","status")
);
CREATE UNIQUE INDEX IF NOT EXISTS "idx_ngl_rule_target_uuid" ON "nglayouts_rule_target" ("uuid","status");
CREATE INDEX IF NOT EXISTS "idx_ngl_rule" ON "nglayouts_rule_target" ("rule_id","status");
CREATE INDEX IF NOT EXISTS "idx_ngl_target_type" ON "nglayouts_rule_target" ("type");

-- nglayouts_zone
CREATE TABLE IF NOT EXISTS "nglayouts_zone" (
  "identifier" TEXT NOT NULL,
  "layout_id" INTEGER NOT NULL,
  "status" INTEGER NOT NULL,
  "root_block_id" INTEGER NOT NULL,
  "linked_layout_uuid" TEXT DEFAULT NULL,
  "linked_zone_identifier" TEXT DEFAULT NULL,
  PRIMARY KEY ("identifier","layout_id","status")
);
CREATE INDEX IF NOT EXISTS "idx_ngl_layout" ON "nglayouts_zone" ("layout_id","status");
CREATE INDEX IF NOT EXISTS "idx_ngl_root_block" ON "nglayouts_zone" ("root_block_id","status");
CREATE INDEX IF NOT EXISTS "idx_ngl_linked_zone" ON "nglayouts_zone" ("linked_layout_uuid","linked_zone_identifier");

-- nguser_setting
CREATE TABLE IF NOT EXISTS "nguser_setting" (
  "user_id" INTEGER NOT NULL,
  "is_activated" INTEGER NOT NULL,
  PRIMARY KEY ("user_id")
);

-- sckenhancedselection
CREATE TABLE IF NOT EXISTS "sckenhancedselection" (
  "contentobject_attribute_id" INTEGER NOT NULL DEFAULT 0,
  "contentobject_attribute_version" INTEGER NOT NULL DEFAULT 0,
  "identifier" TEXT NOT NULL DEFAULT ''
);
CREATE INDEX IF NOT EXISTS "sckenhancedselection_coaid_coav" ON "sckenhancedselection" ("contentobject_attribute_id","contentobject_attribute_version");
CREATE INDEX IF NOT EXISTS "sckenhancedselection_coaid_coav_iden" ON "sckenhancedselection" ("contentobject_attribute_id","contentobject_attribute_version","identifier");

-- lexik_translation_file
CREATE TABLE IF NOT EXISTS "lexik_translation_file" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "domain" TEXT NOT NULL,
  "locale" TEXT NOT NULL,
  "extention" TEXT NOT NULL,
  "path" TEXT NOT NULL,
  "hash" TEXT NOT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS "hash_idx" ON "lexik_translation_file" ("hash");

-- lexik_trans_unit
CREATE TABLE IF NOT EXISTS "lexik_trans_unit" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "key_name" TEXT NOT NULL,
  "domain" TEXT NOT NULL,
  "created_at" TEXT DEFAULT NULL,
  "updated_at" TEXT DEFAULT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS "key_domain_idx" ON "lexik_trans_unit" ("key_name","domain");

-- lexik_trans_unit_translations
CREATE TABLE IF NOT EXISTS "lexik_trans_unit_translations" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "file_id" INTEGER DEFAULT NULL,
  "trans_unit_id" INTEGER DEFAULT NULL,
  "locale" TEXT NOT NULL,
  "content" TEXT NOT NULL,
  "created_at" TEXT DEFAULT NULL,
  "updated_at" TEXT DEFAULT NULL,
  "modified_manually" INTEGER NOT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS "trans_unit_locale_idx" ON "lexik_trans_unit_translations" ("trans_unit_id","locale");
CREATE INDEX IF NOT EXISTS "IDX_B0AA394493CB796C" ON "lexik_trans_unit_translations" ("file_id");
CREATE INDEX IF NOT EXISTS "IDX_B0AA3944C3C583C9" ON "lexik_trans_unit_translations" ("trans_unit_id");
