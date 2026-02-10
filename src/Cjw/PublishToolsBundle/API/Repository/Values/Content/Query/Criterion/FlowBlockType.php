<?php

namespace Cjw\PublishToolsBundle\API\Repository\Values\Content\Query\Criterion;

use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator\Specifications;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Value;
use eZ\Publish\API\Repository\Values\Content\Query\CriterionInterface;


/**
 * A criterion that matches content which is in a FlowBlockType
 *
 * Supported operators:
 * - IN: matches against a list of FlowBlockType (with OR operator)
 * - EQ: matches against one FlowBlockType e.g. 'ContentSlider'
 */
class FlowBlockType extends Criterion implements CriterionInterface
{
    /**
     * Creates a new FlowBlock criterion.
     *
     * @param int|int[] $value One or more FlowBlockType IDs that must be matched
     * @param string operator 'in' or 'not in'
     * @param string $target Field definition identifier to use
     * @param \eZ\Publish\API\Repository\Values\Content\Query\Criterion\Value $valueData
     *
     * @throws \InvalidArgumentException if a non numeric id is given
     * @throws \InvalidArgumentException if the value type doesn't match the operator
     */
    public function __construct( $value, $operator = 'not in', $target = null, Value $valueData )
    {
        parent::__construct( $target, $operator, $value, $valueData );
    }


    public function getSpecifications()
    {
        return [
//            new Specifications(
//                'not in',
//                Specifications::FORMAT_ARRAY,
//                Specifications::TYPE_INTEGER | Specifications::TYPE_STRING
//            ),
//            new Specifications(
//                Operator::IN,
//                Specifications::FORMAT_ARRAY,
//                Specifications::TYPE_INTEGER | Specifications::TYPE_STRING
//            )
            new Specifications(
                'not in',
                Specifications::TYPE_STRING,
                Specifications::TYPE_INTEGER | Specifications::TYPE_STRING
            ),
            new Specifications(
                Operator::IN,
                Specifications::TYPE_STRING,
                Specifications::TYPE_INTEGER | Specifications::TYPE_STRING
            )
        ];
    }

    public static function createFromQueryBuilder($target, $operator, $value)
    {
        return new self($value);
    }
}
