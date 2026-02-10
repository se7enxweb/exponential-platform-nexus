<?php
/**
 * File containing the MapLocation field type
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version 2014.11.1
 */

//namespace Cjw\FieldTypesBundle\FieldType\CjwGeoAddress;
namespace Cjw\FieldTypesBundle\FieldType\CjwGeoAddress;

use eZ\Publish\Core\FieldType\FieldType;
use eZ\Publish\SPI\Persistence\Content\FieldValue;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentType;
use eZ\Publish\SPI\FieldType\Value as SPIValue;
use eZ\Publish\Core\FieldType\Value as BaseValue;

/**
 * MapLocation field types
 *
 * Represents keywords.
 */
class Type extends FieldType
{
    /**
     * Returns the field type identifier for this field type
     *
     * @return string
     */
    public function getFieldTypeIdentifier()
    {
        return "cjwgeoaddress";
    }

    /**
     * Returns the name of the given field value.
     *
     * It will be used to generate content name and url alias if current field is designated
     * to be used in the content name/urlAlias pattern.
     *
     * @param \Cjw\FieldTypesBundle\FieldType\CjwGeoAddress\Value $value
     *
     * @return string
     */
    public function getName( SPIValue $value )
    {
        return (string)$value->address;
    }

    /**
     * Returns the fallback default value of field type when no such default
     * value is provided in the field definition in content types.
     *
     * @return \Cjw\FieldTypesBundle\FieldType\CjwGeoAddress\Value
     */
    public function getEmptyValue()
    {
        return new Value;
    }

    /**
     * Returns if the given $value is considered empty by the field type
     *
     * @param mixed $value
     *
     * @return boolean
     */
    public function isEmptyValue( SPIValue $value )
    {
        return $value->latitude === null && $value->longitude === null && $value->altitude === null && $value->country === null && $value->contact === null;
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * @param array|\Cjw\FieldTypesBundle\FieldType\CjwGeoAddress\Value $inputValue
     *
     * @return \Cjw\FieldTypesBundle\FieldType\CjwGeoAddress\Value The potentially converted and structurally plausible value.
     */
    protected function createValueFromInput( $inputValue )
    {
        if ( is_array( $inputValue ) )
        {
            $inputValue = new Value( $inputValue );
        }

        return $inputValue;
    }

    /**
     * Throws an exception if value structure is not of expected format.
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException If the value does not match the expected structure.
     *
     * @param \Cjw\FieldTypesBundle\FieldType\CjwGeoAddress\Value $value
     *
     * @return void
     */
    protected function checkValueStructure( BaseValue $value )
    {
        if ( !is_float( $value->latitude ) && !is_int( $value->latitude ) )
        {
            throw new InvalidArgumentType(
                '$value->latitude',
                'float',
                $value->latitude
            );
        }
        if ( !is_float( $value->longitude ) && !is_int( $value->longitude ) )
        {
            throw new InvalidArgumentType(
                '$value->longitude',
                'float',
                $value->longitude
            );
        }

        if ( !is_float( $value->altitude ) && !is_int( $value->altitude ) )
        {
            throw new InvalidArgumentType(
                '$value->altitude',
                'float',
                $value->altitude
            );
        }

        if ( !is_string( $value->address ) )
        {
            throw new InvalidArgumentType(
                '$value->address',
                'string',
                $value->address
            );
        }

        if ( !is_string( $value->country ) )
        {
            throw new InvalidArgumentType(
                '$value->country',
                'string',
                $value->country
            );
        }

        if ( !is_string( $value->contact ) )
        {
            throw new InvalidArgumentType(
                '$value->contact',
                'string',
                $value->contact
            );
        }

        if ( !is_string( $value->address1 ) )
        {
            throw new InvalidArgumentType(
                '$value->address1',
                'string',
                $value->address1
            );
        }

        if ( !is_string( $value->address2 ) )
        {
            throw new InvalidArgumentType(
                '$value->address2',
                'string',
                $value->address2
            );
        }

        if ( !is_string( $value->zip ) )
        {
            throw new InvalidArgumentType(
                '$value->zip',
                'string',
                $value->zip
            );
        }

        if ( !is_string( $value->town ) )
        {
            throw new InvalidArgumentType(
                '$value->town',
                'string',
                $value->town
            );
        }

        if ( !is_string( $value->address2 ) )
        {
            throw new InvalidArgumentType(
                '$value->province',
                'string',
                $value->province
            );
        }

        if ( !is_string( $value->subregion ) )
        {
            throw new InvalidArgumentType(
                '$value->subregion',
                'string',
                $value->subregion
            );
        }

        if ( !is_string( $value->properties ) )
        {
            throw new InvalidArgumentType(
                '$value->properties',
                'string',
                $value->properties
            );
        }
    }

    /**
     * Returns information for FieldValue->$sortKey relevant to the field type.
     *
     * @param \Cjw\FieldTypesBundle\FieldType\CjwGeoAddress\Value $value
     *
     * @return string
     */
    protected function getSortInfo( BaseValue $value )
    {
        return $this->transformationProcessor->transformByGroup( (string)$value, "lowercase" );
    }

    /**
     * Converts an $hash to the Value defined by the field type
     *
     * @param mixed $hash
     *
     * @return \Cjw\FieldTypesBundle\FieldType\CjwGeoAddress\Value $value
     */
    public function fromHash( $hash )
    {
        if ( $hash === null )
        {
            return $this->getEmptyValue();
        }
        return new Value( $hash );
    }

    /**
     * Converts a $Value to a hash
     *
     * @param \Cjw\FieldTypesBundle\FieldType\CjwGeoAddress\Value $value
     *
     * @return mixed
     */
    public function toHash( SPIValue $value )
    {
        if ( $this->isEmptyValue( $value ) )
        {
            return null;
        }
        return array(
            'latitude' => $value->latitude,
            'longitude' => $value->longitude,
            'altitude' => $value->altitude,
            'address' => $value->address,
            'country' => $value->country,
            'contact' => $value->contact
        );
    }

    /**
     * Returns whether the field type is searchable
     *
     * @return boolean
     */
    public function isSearchable()
    {
        return true;
    }

    /**
     * Converts a $value to a persistence value
     *
     * @param \Cjw\FieldTypesBundle\FieldType\CjwGeoAddress\Value $value
     *
     * @return \eZ\Publish\SPI\Persistence\Content\FieldValue
     */
    public function toPersistenceValue( SPIValue $value )
    {
        return new FieldValue(
            array(
                "data" => null,
                "externalData" => $this->toHash( $value ),
                "sortKey" => $this->getSortInfo( $value ),
            )
        );
    }

    /**
     * Converts a persistence $fieldValue to a Value
     *
     * @param \eZ\Publish\SPI\Persistence\Content\FieldValue $fieldValue
     *
     * @return \Cjw\FieldTypesBundle\FieldType\CjwGeoAddress\Value
     */
    public function fromPersistenceValue( FieldValue $fieldValue )
    {
        if ( $fieldValue->externalData === null )
        {
            return $this->getEmptyValue();
        }
        return $this->fromHash( $fieldValue->externalData );
    }
}
