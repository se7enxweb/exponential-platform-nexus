<?php
/**
 * File containing the LegacyConverter class.
 */
namespace Cjw\FieldTypesBundle\FieldType\CjwGeoAddress;

use eZ\Publish\Core\Persistence\Legacy\Content\FieldValue\Converter;
use eZ\Publish\Core\Persistence\Legacy\Content\StorageFieldDefinition;
use eZ\Publish\Core\Persistence\Legacy\Content\StorageFieldValue;
use eZ\Publish\SPI\Persistence\Content\FieldValue;
use eZ\Publish\SPI\Persistence\Content\Type\FieldDefinition;

/**
 * The Converter will be placed along with the Type and Value definitions
 * (the Kernel stores them inside the Legacy Storage Engine structure):
 * eZ/Publish/FieldType/Tweet/LegacyConverter.php .
 * A Legacy Converter must implement the
 * eZ\Publish\Core\Persistence\Legacy\Content\FieldValue\Converter interface.
 *
 * https://doc.mirror.ezpublishlegacy.com/display/DEVELOPER/Implement%2Bthe%2BLegacy%2BStorage%2BEngine%2BConverter.html
 *
 * Class CjwGeoAddressConverter
 * @package Cjw\FieldTypesBundle\FieldType\CjwGeoAddress
 */
class CjwGeoAddressConverter implements Converter
{
    public static function create()
    {
        return new self;
    }

    public function toStorageValue( FieldValue $value, StorageFieldValue $storageFieldValue )
    {
        $storageFieldValue->dataText = $value->data;
    }

    public function toFieldValue( StorageFieldValue $value, FieldValue $fieldValue )
    {
        $fieldValue->data = $value->dataText;
    }

    public function toStorageFieldDefinition( FieldDefinition $fieldDef, StorageFieldDefinition $storageDef )
    {

    }

    public function toFieldDefinition( StorageFieldDefinition $storageDef, FieldDefinition $fieldDef )
    {

    }

    public function getIndexColumn()
    {
        return false;
    }
}