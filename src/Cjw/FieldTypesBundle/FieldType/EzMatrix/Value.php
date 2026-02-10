<?php
/**
 * File containing the EzMatrix Value class
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version 2014.11.1
 */

namespace Cjw\FieldTypesBundle\FieldType\EzMatrix;

use eZ\Publish\Core\FieldType\Value as BaseValue;

// Additional EzMatrix classes (copied over from Legacy)
use Cjw\FieldTypesBundle\FieldType\EzMatrix\Classes\EzMatrix;
use Cjw\FieldTypesBundle\FieldType\EzMatrix\Classes\EzMatrixDefinition;

/**
 * Value for EzMatrix field type
 */
class Value extends BaseValue
{
    /**
     * XML text content
     *
     * @var string
     */
    public $xml;

    /**
     * XML Matrix data
     *
     * @var array
     */
    public $matrix = array();

    /**
     * XML Matrix definition
     *
     * @var array
     */
    public $matrixDefinition = array();

    /**
     * Matrix object used for parsing the content XML data.
     *
     * @var EzMatrix
     */
    public $matrixObject = null;
    /**
     * Matrix definition object used for parsing the class XML data.
     *
     * @var EzMatrixDefinition
     */
    public $matrixDefinitionObject = null;

    /**
     * Construct a new Value object and initialize it's $xml string and try to
     * parse it to $matrix array.
     *
     * @param string $xmlString
     */
    public function __construct( $xmlString = '' )
    {
        $this->xml = $xmlString;

        // Create private object properties
        $this->matrixObject = new EzMatrix( '' );
        $this->matrixDefinitionObject = new EzMatrixDefinition();

        // If there is XML text set, we're trying to parse and decode it …
        if ( $this->xml )
        {
            $this->matrixObject->decodeXML( $this->xml );
            $this->matrix = $this->matrixObject->Matrix;

            $this->matrixDefinitionObject->decodeClassAttribute( $this->xml );
            $this->matrixDefinition = $this->matrixDefinitionObject->attributes();
        }
    }

    /**
     * @see \eZ\Publish\Core\FieldType\Value
     */
    public function __toString()
    {
        return (string)$this->xml;
    }
}
