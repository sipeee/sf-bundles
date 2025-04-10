<?php

namespace Company\AutocompleteBundle\Controller;

use Company\AutocompleteBundle\Autocomplete\Manager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class AutocompleteController.
 */
class AutocompleteController
{
    /** @var Manager */
    private $autocompleteManager;

    public function __construct(Manager $autocompleteManager)
    {
        $this->autocompleteManager = $autocompleteManager;
    }

    /**
     * @return JsonResponse
     */
    public function index(Request $request): Response
    {
        $descriptorId = $request->get('descriptor', '');
        $manager = $this->autocompleteManager;

        if (!$manager->hasAutocompleteDescriptor($descriptorId)) {
            throw new NotFoundHttpException();
        }

        $query = $request->query;
        $queryBuilder = $manager->createQueryBuilder($descriptorId);

        $manager->addKeywordConditionToQuery($queryBuilder, $descriptorId, $query->get('q'));

        $queryBuilder
            ->setFirstResult(($query->get('page', 1) - 1) * $query->get('item_per_page', 20))
            ->setMaxResults($query->get('item_per_page', 20));

        return new JsonResponse($manager->hydrateEntitiesToSearchResult(
            $queryBuilder->getQuery()->getResult(), $descriptorId
        ));
    }
}
