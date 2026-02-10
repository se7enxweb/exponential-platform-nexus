<?php

namespace Cjw\PublishToolsBundle\API\Repository\Values\Content\Query\Criterion\Value;

use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Value;

/**
 * Struct that stores extra value information for a TagKeyword criterion object.
 */
class FlowBlockTypeValue extends Value
{
    /**
     * One or more languages to match in. If empty, Criterion will match in all available languages.
     *
     * @var string[]
     */
    public $blockTypes;

    /**
     * Whether to use always available flag in addition to provided languages.
     *
     * @var bool
     */
    public $blockNodeId = null;

    /**
     * Constructor.
     *
     * @param string[] $blockTypes
     * @param int $blockNodeId
     */
    public function __construct(array $blockTypes = null, $blockNodeId = null)
    {
        $this->blockTypes = $blockTypes;
        $this->blockNodeId = (int) $blockNodeId;
    }
}
