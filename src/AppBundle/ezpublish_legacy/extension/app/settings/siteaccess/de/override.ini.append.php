<?php /* #?ini charset="utf-8"?
###################### info ##########################
# ez doku zu override.ini
# http://ez.no/doc/ez_publish/technical_manual/4_0/reference/template_override_conditions
#
# Hilfe Definition ini Blöcke
# [Source(ohne .tpl)#blockname (z.B. class_identifer)]
#    [node/view/full#cjw_folder]
#    Source=node/view/full.tpl
#    MatchFile=node/view/full/cjw_folder.tpl
#    Subdir=templates
#    Match[class_identifier]=cjw_folder
###################### info ###########################

### node/view/full.tpl ###

[node/view/full#cjw_veranst_db]
Source=node/view/full.tpl
MatchFile=node/view/full/cjw_veranst_db.tpl
Subdir=templates
Match[class_identifier]=cjw_veranst_db

# back to Line-View -> als Rückfall, fall das Objekt einmal als Full-View
#                      aufegrufen wird (z.B. bei Relation)
[node/view/full#cjw_link]
Source=node/view/full.tpl
MatchFile=node/view/full/events.tpl
Subdir=templates
Match[class_identifier]=cjw_link

[node/view/full#cjw_google_map]
Source=node/view/full.tpl
MatchFile=node/view/full/cjw_google_map.tpl
Subdir=templates
Match[class_identifier]=cjw_google_map

[node/view/full#cjw_folder_gallery]
Source=node/view/full.tpl
MatchFile=node/view/full/cjw_folder_gallery.tpl
Subdir=templates
Match[class_identifier]=cjw_folder_gallery

[node/view/full#cjw_overview_prices]
Source=node/view/full.tpl
MatchFile=node/view/full/cjw_overview_prices.tpl
Subdir=templates
Match[class_identifier]=cjw_overview_prices

[node/view/full#cjw_overview_supplys]
Source=node/view/full.tpl
MatchFile=node/view/full/cjw_overview_supplys.tpl
Subdir=templates
Match[class_identifier]=cjw_overview_supplys

[node/view/full#cjw_object_accommodation]
Source=node/view/full.tpl
MatchFile=node/view/full/cjw_object_accommodation.tpl
Subdir=templates
Match[class_identifier]=cjw_object_accommodation

[node/view/full#cjw_object_accommodation_group]
Source=node/view/full.tpl
MatchFile=node/view/full/cjw_object_accommodation_group.tpl
Subdir=templates
Match[class_identifier]=cjw_object_accommodation_group

[node/view/full#cjw_folder]
Source=node/view/full.tpl
MatchFile=node/view/full/cjw_folder.tpl
Subdir=templates
Match[class_identifier]=cjw_folder

[node/view/full#cjw_folder_objects]
Source=node/view/full.tpl
MatchFile=node/view/full/cjw_folder.tpl
Subdir=templates
Match[class_identifier]=cjw_folder_objects

[node/view/full#cjw_article_home]
Source=node/view/full.tpl
MatchFile=node/view/full/cjw_article_home.tpl
Subdir=templates
Match[class_identifier]=cjw_article_home

[node/view/full#cjw_article]
Source=node/view/full.tpl
MatchFile=node/view/full/cjw_article.tpl
Subdir=templates
Match[class_identifier]=cjw_article

[node/view/full#cjw_supply_request]
Source=node/view/full.tpl
MatchFile=node/view/full/cjw_supply_request.tpl
Subdir=templates
Match[class_identifier]=cjw_supply_request

[node/view/full#cjw_booking_request]
Source=node/view/full.tpl
MatchFile=node/view/full/cjw_booking_request.tpl
Subdir=templates
Match[class_identifier]=cjw_booking_request

[node/view/full#cjw_call_me_back_form]
Source=node/view/full.tpl
MatchFile=node/view/full/cjw_call_me_back_form.tpl
Subdir=templates
Match[class_identifier]=cjw_call_me_back_form

[node/view/full#cjw_feedback_form]
Source=node/view/full.tpl
MatchFile=node/view/full/cjw_feedback_form.tpl
Subdir=templates
Match[class_identifier]=cjw_feedback_form

[node/view/full#cjw_file]
Source=node/view/full.tpl
MatchFile=node/view/full/cjw_file.tpl
Subdir=templates
Match[class_identifier]=cjw_file

### node/view/line.tpl ###

[node/view/line#cjw_object_accommodation]
Source=node/view/line.tpl
MatchFile=node/view/line/cjw_object_accommodation.tpl
Subdir=templates
Match[class_identifier]=cjw_object_accommodation

[node/view/line#cjw_teaser_start]
Source=node/view/line.tpl
MatchFile=node/view/line/cjw_teaser_start.tpl
Subdir=templates
Match[class_identifier]=cjw_teaser_start

### node/view/block.tpl ###

[node/view/block#cjw_object_accommodation_group]
Source=node/view/block.tpl
MatchFile=node/view/block/cjw_object_accommodation_and_his_group.tpl
Subdir=templates
Match[class_identifier]=cjw_object_accommodation_group

[node/view/block#cjw_object_accommodation]
Source=node/view/block.tpl
MatchFile=node/view/block/cjw_object_accommodation_and_his_group.tpl
Subdir=templates
Match[class_identifier]=cjw_object_accommodation


### content/view/embed.tpl ###

[content/view/embed#cjw_image]
Source=content/view/embed.tpl
MatchFile=content/view/embed/cjw_image.tpl
Subdir=templates
Match[class_identifier]=cjw_image

### Security ###
# nachfolgende Regeln auskommentieren um für contentclassen,
# die keine overrideregel haben ein leeres template auszugeben

#[empty_full]
#Source=node/view/full.tpl
#MatchFile=empty.tpl
#Subdir=templates

#[empty_line]
#Source=node/view/line.tpl
#MatchFile=empty.tpl
#Subdir=templates

#[empty_edit]
#Source=node/view/edit.tpl
#MatchFile=empty.tpl
#Subdir=templates

###############################################################################
#
# Miscellaneous overrides
#
###############################################################################

[content/datatype/collect/ezselection#cjw_feedback_form-salutation]
Source=content/datatype/collect/ezselection.tpl
MatchFile=content/datatype/collect/ezselection/ezselection_as_radio.tpl
Subdir=templates
Match[attribute_identifier]=salutation

[content/datatype/collect/ezselection#cjw_feedback_form-day]
Source=content/datatype/collect/ezselection.tpl
MatchFile=content/datatype/collect/ezselection/ezselection_as_radio.tpl
Subdir=templates
Match[attribute_identifier]=day

[content/datatype/collect/ezselection#cjw_booking_request-appartements]
Source=content/datatype/collect/ezselection.tpl
MatchFile=content/datatype/collect/ezselection/ezselection_appartements.tpl
Subdir=templates
Match[attribute_identifier]=appartements

[content/collectedinfomail#cjw_booking_request]
Source=content/collectedinfomail/form.tpl
MatchFile=content/collectedinfomail/cjw_booking_request.tpl
Subdir=templates
Match[class_identifier]=cjw_booking_request

*/ ?>
