<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Nodes;

use Pterodactyl\Models\Node;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Services\Allocations\AssignmentService;
use Pterodactyl\Services\Allocations\AllocationDeletionService;
use Pterodactyl\Transformers\Api\Application\AllocationTransformer;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Allocations\GetAllocationsRequest;
use Pterodactyl\Http\Requests\Api\Application\Allocations\StoreAllocationRequest;
use Pterodactyl\Http\Requests\Api\Application\Allocations\DeleteAllocationRequest;

class AllocationController extends ApplicationApiController
{
    /**
     * @var \Pterodactyl\Services\Allocations\AssignmentService
     */
    private $assignmentService;

    /**
     * @var \Pterodactyl\Services\Allocations\AllocationDeletionService
     */
    private $deletionService;

    /**
     * AllocationController constructor.
     *
     * @param \Pterodactyl\Services\Allocations\AssignmentService $assignmentService
     * @param \Pterodactyl\Services\Allocations\AllocationDeletionService $deletionService
     */
    public function __construct(
        AssignmentService $assignmentService,
        AllocationDeletionService $deletionService
    ) {
        parent::__construct();

        $this->assignmentService = $assignmentService;
        $this->deletionService = $deletionService;
    }

    /**
     * Return all of the allocations that exist for a given node.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Allocations\GetAllocationsRequest $request
     * @param \Pterodactyl\Models\Node $node
     *
     * @return array
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index(GetAllocationsRequest $request, Node $node): array
    {
        $allocations = $node->allocations()->paginate(50);

        return $this->fractal->collection($allocations)
            ->transformWith($this->getTransformer(AllocationTransformer::class))
            ->toArray();
    }

    /**
     * Store new allocations for a given node.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Allocations\StoreAllocationRequest $request
     * @param \Pterodactyl\Models\Node $node
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\Service\Allocation\CidrOutOfRangeException
     * @throws \Pterodactyl\Exceptions\Service\Allocation\InvalidPortMappingException
     * @throws \Pterodactyl\Exceptions\Service\Allocation\PortOutOfRangeException
     * @throws \Pterodactyl\Exceptions\Service\Allocation\TooManyPortsInRangeException
     */
    public function store(StoreAllocationRequest $request, Node $node): JsonResponse
    {
        $this->assignmentService->handle($node, $request->validated());

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Delete a specific allocation from the Panel.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Allocations\DeleteAllocationRequest $request
     * @param \Pterodactyl\Models\Node $node
     * @param \Pterodactyl\Models\Allocation $allocation
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\Service\Allocation\ServerUsingAllocationException
     */
    public function delete(DeleteAllocationRequest $request, Node $node, Allocation $allocation): JsonResponse
    {
        $this->deletionService->handle($allocation);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
