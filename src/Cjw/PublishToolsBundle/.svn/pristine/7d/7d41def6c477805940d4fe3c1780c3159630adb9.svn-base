<?php
/*

Controller to redirect a make a redirect relative or absolute through the symfony kernel,
so you don't have add custom webserver rules


example routing.yml

# redirect intern
# http://domain.de/redirect_test_intern => redirect to http://domain.de/?redirected=1
cjwpublishtools_redirect_internal_test:
  path: /redirect_test_intern
  defaults:
    _controller: CjwPublishToolsBundle:Redirect:redirect
    url: /?redirected=1
    status: 302

# redirect external
# http://domain.de/redirect_test_extern => redirect to https://www.cjw-network.com/?redirected=1
cjwpublishtools_redirect_internal_test:
  path: /redirect_test_extern
  defaults:
    _controller: CjwPublishToolsBundle:Redirect:redirect
    url: https://www.cjw-network.com/?redirected=1
    status: 302

*/


namespace Cjw\PublishToolsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RedirectController
 * @package Cjw\PublishToolsBundle\Controller
 */
class RedirectController extends Controller
{
    /**
     * @param $url
     * @param int $status
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectAction( $url, $status = 301, Request $request )
    {
        //        $pathInfo = $request->getPathInfo();
        //        $requestUri = $request->getRequestUri();
        //
        //        $url = str_replace($pathInfo, rtrim($pathInfo, ' /'), $requestUri);

        // 308 (Permanent Redirect) is similar to 301 (Moved Permanently) except
        // that it does not allow changing the request method (e.g. from POST to GET)
        //        return $this->redirect($url, 308);

        return $this->redirect( $url, (int) $status );
    }
}
