# <?php
# 
# /**
#  * ============================================================================
#  * COMMENTED OUT FOR PURGE - 2026-02-04
#  * ============================================================================
#  * 
#  * Class: SubitemsController
#  * Location: AppBundle\Controller\SubitemsController
#  * Status: UNDESIRED - Marked for removal
#  * 
#  * This controller is no longer needed and has been commented out pending purge.
#  * Subitems listing functionality is now handled by framework routing and API.
#  * Please remove this file during the next cleanup cycle.
#  * 
#  * Last modified: 2026-02-04 18:25:00 UTC
#  * ============================================================================
#  */
# 
# /*
# namespace AppBundle\Controller;
# 
# use eZ\Publish\API\Repository\ContentService;
# use eZ\Publish\API\Repository\LocationService;
# use Symfony\Bundle\FrameworkBundle\Controller\Controller;
# use Symfony\Component\HttpFoundation\JsonResponse;
# use Symfony\Component\HttpFoundation\Request;
# 
# class SubitemsController extends Controller
# {
#     public function listAction(Request $request, $locationId)
#     {
#         $limit = (int)$request->query->get('limit', 10);
#         $offset = (int)$request->query->get('offset', 0);
#         $sortBy = $request->query->get('sortBy', 'name');
#         $sortOrder = (int)$request->query->get('sortOrder', 1);
#         
#         try {
#             $repository = $this->get('ezpublish.api.repository');
#             $locationService = $repository->getLocationService();
#             $contentService = $repository->getContentService();
#             
#             $location = $locationService->loadLocation($locationId);
#             $children = $locationService->loadLocationChildren($location, $offset, $limit);
#             
#             $items = [];
#             foreach ($children->locations as $child) {
#                 $content = $contentService->loadContentByContentInfo($child->contentInfo);
#                 $items[] = [
#                     'node_id' => $child->id,
#                     'contentobject_id' => $content->id,
#                     'name' => $content->getName(),
#                     'class_id' => $content->contentType->id,
#                     'class_name' => $content->contentType->name,
#                     'modified' => $content->contentInfo->modificationDate->getTimestamp(),
#                     'published' => $content->contentInfo->publishedDate->getTimestamp(),
#                     'can_edit' => true,
#                 ];
#             }
#             
#             return new JsonResponse([
#                 'data' => $items,
#                 'meta' => [
#                     'totalRecords' => $children->totalCount,
#                 ]
#             ]);
#         } catch (\Exception $e) {
#             return new JsonResponse(['error' => $e->getMessage()], 500);
#         }
#     }
# }
# */
