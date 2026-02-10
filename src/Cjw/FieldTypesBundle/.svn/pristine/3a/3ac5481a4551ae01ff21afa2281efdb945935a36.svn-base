<?php
/**
 * File containing the CjwGeoAddress SearchField class
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version 2014.11.1
 */

namespace Cjw\FieldTypesBundle\FieldType\CjwGeoAddress;

use eZ\Publish\SPI\Persistence\Content\Field;
use eZ\Publish\SPI\FieldType\Indexable;
use eZ\Publish\SPI\Search;

/**
 * Indexable definition for MapLocation field type
 */
class SearchField implements Indexable
{
    /**
     * Get index data for field for search backend
     *
     * @param \eZ\Publish\SPI\Persistence\Content\Field $field
     *
     * @return \eZ\Publish\SPI\Search\Field[]
     */
    public function getIndexData( Field $field )
    {
        return array(
            new Search\Field(
                'value_address',
                $field->value->externalData["address"],
                new Search\FieldType\StringField()
            ),
            new Search\Field(
                'value_location',
                array(
                    "latitude" => $field->value->externalData["latitude"],
                    "longitude" => $field->value->externalData["longitude"],
                    "country" => $field->value->externalData["country"],
                    "adress1" => $field->value->externalData["adress1"],
                    "adress2" => $field->value->externalData["adress2"],
                    "contact" => $field->value->externalData["contact"]
                ),
                new Search\FieldType\GeoLocationField()
            ),
        );
    }

    /**
     * Get index field types for search backend
     *
     * @return \eZ\Publish\SPI\Search\FieldType[]
     */
    public function getIndexDefinition()
    {
        return array(
            'value_address' => new Search\FieldType\StringField(),
            'value_location' => new Search\FieldType\GeoLocationField()
        );
    }
}
