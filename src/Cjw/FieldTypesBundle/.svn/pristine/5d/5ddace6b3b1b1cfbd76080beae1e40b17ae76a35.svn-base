<?php
/**
 * File containing the EzMatrix field type
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version 2014.11.1
 */

//namespace Cjw\FieldTypesBundle\FieldType\EzMatrix;
namespace Cjw\FieldTypesBundle\FieldType\EzMatrix;

use eZ\Publish\Core\FieldType\FieldType;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentType;
use eZ\Publish\SPI\FieldType\Value as SPIValue;
use eZ\Publish\Core\FieldType\Value as BaseValue;

/**
 * EzMatrix field types
 *
 * Represents keywords.
 */
class Type extends FieldType
{
    public function __construct( $fieldTypeIdentifier )
    {
        $this->fieldTypeIdentifier = $fieldTypeIdentifier;
    }

    /**
     * Returns the field type identifier for this field type
     *
     * @return string
     */
    public function getFieldTypeIdentifier()
    {
        return "ezmatrix";
    }

    /**
     * Returns the name of the given field value.
     *
     * It will be used to generate content name and url alias if current field is designated
     * to be used in the content name/urlAlias pattern.
     *
     * @param \Cjw\FieldTypesBundle\FieldType\EzMatrix\Value $value
     *
     * @return string
     */
    public function getName( SPIValue $value )
    {
        return (string)$value->text;
    }

    /**
     * Returns the fallback default value of field type when no such default
     * value is provided in the field definition in content types.
     *
     * @return \Cjw\FieldTypesBundle\FieldType\EzMatrix\Value
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
        return $value->xml === null || trim( $value->xml ) === "";
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * @param string|\Cjw\FieldTypesBundle\FieldType\EzMatrix\Value $inputValue
     *
     * @return \Cjw\FieldTypesBundle\FieldType\EzMatrix\Value The potentially converted and structurally plausible value.
     */
    protected function createValueFromInput( $inputValue )
    {
        if ( is_string( $inputValue ) )
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
     * @param \Cjw\FieldTypesBundle\FieldType\EzMatrix\Value $value
     *
     * @return void
     */
    protected function checkValueStructure( BaseValue $value )
    {
        if ( !is_string( $value->text ) )
        {
            throw new InvalidArgumentType(
                '$value->text',
                'string',
                $value->text
            );
        }
    }

    /**
     * Returns information for FieldValue->$sortKey relevant to the field type.
     *
     * @param \Cjw\FieldTypesBundle\FieldType\EzMatrix\Value $value
     *
     * @return array
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
     * @return \Cjw\FieldTypesBundle\FieldType\EzMatrix\Value $value
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
     * @param \Cjw\FieldTypesBundle\FieldType\EzMatrix\Value $value
     *
     * @return mixed
     */
    public function toHash( SPIValue $value )
    {
        if ( $this->isEmptyValue( $value ) )
        {
            return null;
        }
        return $value->text;
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
}
