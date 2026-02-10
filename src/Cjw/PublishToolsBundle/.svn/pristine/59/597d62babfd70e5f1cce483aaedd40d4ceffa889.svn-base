<?php

/**
 * File containing the DoctrineDatabase Content id criterion handler class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Cjw\PublishToolsBundle\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

use Cjw\PublishToolsBundle\API\Repository\Values\Content\Query\Criterion\FlowBlockType as FlowBlockTypeCriterion;

use eZ\Publish\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;
use eZ\Publish\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\Core\Persistence\Database\SelectQuery;

/**
 * Content ID criterion handler.
 */
class FlowBlockType extends CriterionHandler
{
    /**
     * Check if this criterion handler accepts to handle the given criterion.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query\Criterion $criterion
     *
     * @return bool
     */
    public function accept(Criterion $criterion)
    {
        return $criterion instanceof FlowBlockTypeCriterion;
    }

    /**
     * Generate query expression for a Criterion this handler accepts.
     *
     * accept() must be called before calling this method.
     *
     * @param \eZ\Publish\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter $converter
     * @param \eZ\Publish\Core\Persistence\Database\SelectQuery $query
     * @param \eZ\Publish\API\Repository\Values\Content\Query\Criterion $criterion
     * @param array $languageSettings
     *
     * @return \eZ\Publish\Core\Persistence\Database\Expression
     */
    public function handle(
        CriteriaConverter $converter,
        SelectQuery $query,
        Criterion $criterion,
        array $languageSettings
    ) {


        /** @var \Cjw\PublishToolsBundle\API\Repository\Values\Content\Query\Criterion\Value\FlowBlockTypeValue $valueData */
        $valueData = $criterion->valueData;

        $blockTypes = $valueData->blockTypes;
        $blockNodeId = $valueData->blockNodeId;


//        AND NOT (
//            `ezcontentobject`.`id` IN (
//            SELECT
//            `object_id`
//          FROM
//            `ezm_pool`
//          WHERE
//            `ezm_pool`.`block_id` IN (
//            SELECT
//                `id`
//              FROM
//                `ezm_block`
//              WHERE
//                `ezm_block`.`block_type` = 'ContentSlider'
//            )
//           AND `ezm_pool`.`node_id` = 123
//        )
//      )

//        var_dump( $criterion->value  );die();

//        if ( isset( $criterion->value['block_node_Id'] ) )
//            $blockNodeId = (int) $criterion->value['block_node_Id'];


      //  $blockTypes = $criterion->value['block_type'];

        $subQuery2 = $query->subSelect();

        // beide gesetzt
        if ( $blockNodeId && isset( $blockTypes[0] )  )
        {
            $where2 = $query->expr->lAnd(
                $query->expr->eq(
                    $this->dbHandler->quoteColumn('node_id', 'ezm_block'),
                    $subQuery2->bindValue($blockNodeId, null, \PDO::PARAM_INT)
                ),
                $query->expr->in(
                    $this->dbHandler->quoteColumn('block_type', 'ezm_block'),
                    $blockTypes
                )
            );
        }
        else if ( $blockNodeId )
        {
            $where2 =
                $query->expr->eq(
                    $this->dbHandler->quoteColumn('node_id', 'ezm_block'),
                    $subQuery2->bindValue($blockNodeId, null, \PDO::PARAM_INT)
                );
        }
        else if ( isset( $blockTypes[0] ) )
        {
            $where2 =
                $query->expr->in(
                    $this->dbHandler->quoteColumn('block_type', 'ezm_block'),
                    $blockTypes
                );
        }

        $subQuery2
            ->select($this->dbHandler->quoteColumn('id'))
            ->from($this->dbHandler->quoteTable('ezm_block'))
            ->where( $where2 );

//        if ( !is_array( $blockTypes ) )
//            $blockTypes = [ $blockTypes ];


        $subQuery1 = $query->subSelect();
        $subQuery1
            ->select($this->dbHandler->quoteColumn('object_id'))
            ->from($this->dbHandler->quoteTable('ezm_pool'))
            ->where(
                $query->expr->in(
                    $this->dbHandler->quoteColumn('block_id', 'ezm_pool'),
                    $subQuery2
                )
            );


        $expression = $query->expr->in(
            $this->dbHandler->quoteColumn('id', 'ezcontentobject'),
            $subQuery1
        );


        // not in
        if ( $criterion->operator == 'not in' )
        {
            return $query->expr->not($expression);
        }
        // operator = 'in'
        else
        {
            return $expression;
        }



    }

}
