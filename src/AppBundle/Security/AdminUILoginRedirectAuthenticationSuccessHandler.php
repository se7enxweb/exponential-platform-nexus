<?php

namespace AppBundle\Security;

use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

/**
 * Authentication success handler for admin UI login
 * Uses dynamic siteaccess group configuration instead of hardcoded siteaccess names
 */
class AdminUILoginRedirectAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private $router;
    private $siteaccessGroups;

    public function __construct(RouterInterface $router, array $siteaccessGroups = [])
    {
        $this->router = $router;
        $this->siteaccessGroups = $siteaccessGroups;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        // Check if authenticated user is in an admin siteaccess
        if ($request->attributes->has('siteaccess')) {
            $siteaccess = $request->attributes->get('siteaccess');
            if ($this->isAdminSiteaccess($siteaccess->name)) {
                return new RedirectResponse('/content/dashboard');
            }
        }

        return new RedirectResponse('/');
    }

    private function isAdminSiteaccess(string $siteaccessName): bool
    {
        foreach ($this->siteaccessGroups as $groupName => $groupSiteaccesses) {
            if (in_array($groupName, ['admin_group', 'ngadmin_group', 'legacy_group'], true)) {
                if (in_array($siteaccessName, $groupSiteaccesses, true)) {
                    return true;
                }
            }
        }
        return false;
    }
}
