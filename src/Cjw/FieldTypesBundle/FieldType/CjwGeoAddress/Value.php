<?php
/**
 * File containing the MapLocation Value class
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version 2014.11.1
 */

namespace Cjw\FieldTypesBundle\FieldType\CjwGeoAddress;

use eZ\Publish\Core\FieldType\Value as BaseValue;

/**
 * Value for MapLocation field type
 */
class Value extends BaseValue
{
    /**
     * Latitude of the location
     *
     * @var float
     */
    public $latitude;

    /**
     * Longitude of the location
     *
     * @var float
     */
    public $longitude;

    /**
     * Altitude of the location
     *
     * @var float
     */
    public $altitude;

    /**
     * Display address for the location
     *
     * @var string
     */
    public $address;

    /**
     * Display address for the location
     *
     * @var string
     */
    public $country;

    /**
     * Display address for the location
     *
     * @var string
     */
    public $adress1;

    /**
     * Display address for the location
     *
     * @var string
     */
    public $adress2;

    /**
     * Display address for the location
     *
     * @var string
     */
    public $contact;

    /**
     * @var string
     */
    public $zip;

    /**
     * @var string
     */
    public $town;

    /**
     * @var string
     */
    public $province;

    /**
     * @var string
     */
    public $subregion;

    /**
     * @var string
     */
    public $properties;


    /**
     * Construct a new Value object and initialize with $values
     *
     * @param string[]|string $values
     */
    public function __construct( array $values = null )
    {
        foreach ( (array)$values as $key => $value )
        {
            $this->$key = $value;
        }
    }

    /**
     * Returns a string representation of the keyword value.
     *
     * @return string A comma separated list of tags, eg: "php, eZ Publish, html5"
     */
    public function __toString()
    {
        if ( is_array( $this->address ) )
            return implode( ", ", $this->address );

        return (string)$this->address;
    }
}
