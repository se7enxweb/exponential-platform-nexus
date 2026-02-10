<?php

//namespace Netgen\Layouts\Standard\Block\BlockDefinition\Handler;
namespace AppBundle\Block\BlockDefinition\Handler;

use Netgen\Layouts\Block\BlockDefinition\BlockDefinitionHandler;
use Netgen\Layouts\Block\BlockDefinition\Handler\PagedCollectionsBlockInterface;
use Netgen\Layouts\Parameters\ParameterBuilderInterface;
use Netgen\Layouts\Parameters\ParameterType;
use Netgen\Layouts\Parameters\ParameterType\TextLineType;
use function array_flip;

final class ListExtendedHandler extends BlockDefinitionHandler implements PagedCollectionsBlockInterface
{
    /**
     * The list of columns available. Key should be number of columns, while values
     * should be human readable names of the columns.
     *
     * @var string[]
     */
    private array $columns;

    private array $childrenSubmenu;

    private array $showParentInformations;

    /**
     * @param string[] $columns
     * @param string[] $childrenSubmenu
     * @param string[] $showParentInformations
     */
    public function __construct(array $columns, array $childrenSubmenu, array $showParentInformations )
    {
        $this->columns = $columns;
        $this->childrenSubmenu = $childrenSubmenu;
        $this->showParentInformations = $showParentInformations;
    }

    public function buildParameters(ParameterBuilderInterface $builder): void
    {
        $builder->add(
            'number_of_columns',
            ParameterType\ChoiceType::class,
            [
                'required' => true,
                'options' => array_flip($this->columns),
                'groups' => [self::GROUP_DESIGN],
            ],
        );

        $builder->add(
            'children_submenu',
            ParameterType\ChoiceType::class,
            [
                'required' => true,
                'options' => array_flip($this->childrenSubmenu),
                'groups' => [self::GROUP_DESIGN],
            ],
        );

        $builder->add(
            'show_parent_informations',
            ParameterType\ChoiceType::class,
            [
                'required' => true,
                'options' => array_flip($this->showParentInformations),
                'groups' => [self::GROUP_DESIGN],
            ],
        );
    }
}
