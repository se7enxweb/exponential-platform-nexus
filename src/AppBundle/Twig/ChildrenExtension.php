<?php

namespace AppBundle\Twig;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\Values\Content\Location;

class ChildrenExtension extends \Twig_Extension
{
    private $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('get_children', [$this, 'getChildren']),
        ];
    }

    public function getChildren($location = null, $limit = 100)
    {
        // Return empty array if location is null or not a Location object
        if ($location === null || !($location instanceof Location)) {
            return [];
        }

        try {
            $locationService = $this->repository->getLocationService();
            $children = $locationService->loadLocationChildren($location, 0, $limit);
            
            $childrenData = [];
            foreach ($children->locations as $childLocation) {
                $childContent = $this->repository->getContentService()->loadContent($childLocation->contentInfo->id);
                $childrenData[] = [
                    'location' => $childLocation,
                    'content' => $childContent
                ];
            }
            
            return $childrenData;
        } catch (\Exception $e) {
            error_log("ERROR loading children: " . $e->getMessage());
            return [];
        }
    }

    public function getName()
    {
        return 'children_extension';
    }
}
