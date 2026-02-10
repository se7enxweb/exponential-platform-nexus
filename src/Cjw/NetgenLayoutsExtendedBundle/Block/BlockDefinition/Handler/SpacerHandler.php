<?php

declare(strict_types=1);

namespace Cjw\NetgenLayoutsExtendedBundle\Block\BlockDefinition\Handler;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Netgen\Layouts\API\Values\Block\Block;
use Netgen\Layouts\Block\BlockDefinition\BlockDefinitionHandler;
use Netgen\Layouts\Block\DynamicParameters;
use Netgen\Layouts\Parameters\ParameterType;
use Netgen\Layouts\Parameters\ParameterBuilderInterface;
use Symfony\Component\Yaml\Yaml;

final class SpacerHandler extends BlockDefinitionHandler
{
    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @var array
     */
    protected $customBlocksSettings = array();

    /**
     * SpacerHandler constructor.
     *
     * Params are defined in services.yml!
     */
    public function __construct( ConfigResolverInterface $resolver )
    {
        $this->configResolver = $resolver;

        $this->setCustomBlocksSettings();
    }

    /**
     * Set params for netgen layouts.
     *
     * @param ParameterBuilderInterface $builder
     */
    public function buildParameters(ParameterBuilderInterface $builder): void
    {
        $builder->add(
            'image',
            ParameterType\ChoiceType::class,
            [
                'required' => false,
                'options' => $this->getImages()
            ]
        );

        $builder->add(
            'align',
            ParameterType\ChoiceType::class,
            [
                'required' => false,
                'options' => array( 'Links' => 'left', 'Mittig' => 'center', 'Rechts' => 'right' )
            ]
        );
    }

    /**
     * Get params from netgen layouts, works with them and returns they to the templates.
     *
     * @param DynamicParameters $params
     * @param Block $block
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     */
    public function getDynamicParameters(DynamicParameters $params, Block $block): void
    {
        $image = $block->getParameter('image');
        $align = $block->getParameter('align');

        $imageValue = null;
        if ( !$image->isEmpty() )
        {
            $imageValue = $image->getValue();
        }

        $params[ 'image_path' ] = $imageValue;
        $params[ 'align' ]      = $align;
    }

    public function isContextual(Block $block): bool
    {
        return false;
    }

    /**
     * Get custom settings from yaml file.
     */
    public function setCustomBlocksSettings()
    {
        $configFile = __DIR__ . '/../../../../../../src/AppBundle/Resources/config/netgen_layouts_extended.yaml';

        if ( file_exists( $configFile ) )
        {
            $this->customBlocksSettings = Yaml::parse((string)file_get_contents($configFile));
        }
    }

    /**
     * Get image name <=> path hash by config file.
     *
     * @return array
     */
    public function getImages()
    {
        $result = array();

        if ( isset( $this->customBlocksSettings[ 'netgen_layouts_extended' ][ 'spacer' ][ 'images' ] ) )
        {
            foreach( $this->customBlocksSettings[ 'netgen_layouts_extended' ][ 'spacer' ][ 'images' ] as $image )
            {
                $name = $image[ 'name' ];
                $path = $image[ 'path' ];

                $result[ $name ] = $path;
            }
        }
        else
        {
            $result[ 'no-image' ] = 'no-image';
        }

        return $result;
    }
}
