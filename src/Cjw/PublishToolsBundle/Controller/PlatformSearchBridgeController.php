<?php

declare(strict_types=1);

namespace Cjw\PublishToolsBundle\Controller;

use Netgen\Bundle\SiteBundle\Controller\SearchController as NgSiteSearchController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bridge controller that wraps ngsite.controller.search but handles both 
 * SearchText (legacy) and searchText (ngsite) parameter names.
 * 
 * This allows the route to work with the modern Symfony/eZ Platform ngsite
 * controller while maintaining compatibility with legacy search forms that
 * use SearchText parameter.
 */
class PlatformSearchBridgeController extends NgSiteSearchController
{
    /**
     * Search action that handles both legacy (SearchText) and ngsite (searchText) parameter names
     */
    public function platformSearch(Request $request): Response
    {
        // Support both parameter names: SearchText (legacy) and searchText (ngsite)
        $searchText = $request->query->get('searchText') ?? $request->query->get('SearchText') ?? '';
        
        // If we got a SearchText, convert the query param to searchText for ngsite compatibility
        if ($request->query->has('SearchText') && !$request->query->has('searchText')) {
            $request->query->set('searchText', $searchText);
        }
        
        // Delegate to ngsite search controller
        return $this->search($request);
    }
}
