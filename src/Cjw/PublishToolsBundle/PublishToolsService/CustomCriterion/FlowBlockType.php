<?php

namespace Cjw\PublishToolsBundle\PublishToolsService\CustomCriterion;



use Cjw\PublishToolsBundle\API\Repository\Values\Content\Query\Criterion\FlowBlockType as FlowBlockTypeCriterion;
use Cjw\PublishToolsBundle\API\Repository\Values\Content\Query\Criterion\Value\FlowBlockTypeValue as FlowBlockTypeCriterionValue;

/**
 * Class FlowBlockType
 *
 *
 *  #10523 PublishToolsService - Custom Criterion - FlowBlockType
 *
 *
 *   example:  get all Locations which are not assigned to a Flow Block Type (ContentGrid or CntentSlider) of the current Page (BlockNodeId)
 *
 *   {% set listChildren = cjw_fetch_content( [ currentLocationId ], { 'depth': '3',
 *                                                                 'main_location_only': true,
 *                                                                 'limit': listLimit,
 *                                                                 'offset': listOffset,
 *                                                                 'include': listContenttypes,
 *                                                                 'language': [ cjw_lang_get_default_code() ],
 *                                                                 'sortby': [ [ 'DatePublished', 'DESC' ] ],
 *                                                                 'custom_criterion': [
 *                                                                                       [ '\\Cjw\\PublishToolsBundle\\PublishToolsService\\CustomCriterion\\FlowBlockType',
 *                                                                                           {'block_types': [  'ContentGrid', 'ContentSlider' ],
 *                                                                                               'block_node_id': blockNodeId,
 *                                                                                               'operator': 'not in'}
 *                                                                                       ],
 *
 *                                                                                     ],
 *
 *                                                                 'count': true } ) %}
 *     {% set listCount = listChildren[currentLocationId]['count'] %}
 *
 *
 *
 *
 * @package Cjw\PublishToolsBundle\PublishToolsService\CustomCriterion
 */
class FlowBlockType
{

    public function __construct()
    {
    }

    /**
     *
     * example for custom criterion for twig cjw_fetch_content fuction
     *
     *  get all Locations which are not assigned to a Flow Block Type (ContentGrid or CntentSlider) of the current Page (BlockNodeId)
     *
     *  operators:   not in || in
     *
     * 'custom_criterion': [
     *         [ '\\Cjw\\PublishToolsBundle\\PublishToolsService\\CustomCriterion\\FlowBlockType',
     *           {'block_types': [  'ContentGrid', 'ContentSlider' ],
     *            'block_node_id': blockNodeId,
     *            'operator': 'not in'}
     *         ],
     *
     * ],
     * @return Criterion or false
     */
    public function getCriterion( $params )
    {

        $criterion = false;

        if ( isset( $params ) && is_array( $params ) && count( $params ) > 0 )
        {

            $blockNodeId = null;

            // array or Value
            $blockTypes = null;

            $operator = 'not in';

            if ( isset( $params['operator'] ) && in_array( $params['operator'], ['in', 'not in'] ) )
            {
                $operator = $params['operator'];
            }

            if ( isset( $params['block_node_id'] ) )
            {
                $blockNodeId = $params['block_node_id'];
            }

            if ( isset( $params['block_types'] ) )
            {
                $blockTypes = $params['block_types'];
            }

            $valueData =   new FlowBlockTypeCriterionValue( $blockTypes, $blockNodeId );
            $criterion =  new FlowBlockTypeCriterion( 'dummy', $operator, null, $valueData );
        }

        return $criterion;

    }



}
