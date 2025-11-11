<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\InstallationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Installation\InstallationOrderStoreRequest;
use App\Http\Requests\Installation\InstallationOrderUpdateRequest;
use App\Models\InstallationOrder;
use App\Services\Installation\InstallationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InstallationOrderController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected InstallationService $installationService
    ) {}

    /**
     * Display a listing of installation orders.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', InstallationOrder::class);

        $query = InstallationOrder::with([
            'salesOrder',
            'customer',
            'installationAddress',
            'installationSchedules.team',
        ]);

        // Filter by status
        if ($request->has('status')) {
            $query->status($request->status);
        }

        // Filter by customer
        if ($request->has('customer_id')) {
            $query->forCustomer((int) $request->customer_id);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        // Future: Filter by technician role when RBAC is fully implemented
        // For now, show all installations to authenticated users

        $installations = $query->latest()->paginate(15);

        return response()->json($installations);
    }

    /**
     * Store a newly created installation order.
     */
    public function store(InstallationOrderStoreRequest $request): JsonResponse
    {
        $this->authorize('create', InstallationOrder::class);

        try {
            $installation = $this->installationService->createFromSalesOrder(
                $request->sales_order_id,
                $request->validated()
            );

            $installation->load([
                'salesOrder',
                'customer',
                'installationAddress',
            ]);

            return response()->json($installation, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Display the specified installation order.
     */
    public function show(InstallationOrder $installation): JsonResponse
    {
        $this->authorize('view', $installation);

        $installation->load([
            'salesOrder',
            'customer',
            'installationAddress',
            'installationSchedules.team.members',
            'installationPhotos',
            'customerFeedback',
        ]);

        return response()->json($installation);
    }

    /**
     * Update the specified installation order.
     */
    public function update(InstallationOrderUpdateRequest $request, InstallationOrder $installation): JsonResponse
    {
        $this->authorize('update', $installation);

        if ($installation->status === InstallationStatus::Completed) {
            return response()->json([
                'message' => 'Cannot update completed installation order.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $request->validated();

        // Handle status update separately through service
        if (isset($data['status'])) {
            try {
                $newStatus = InstallationStatus::from($data['status']);
                $this->installationService->updateStatus(
                    $installation->id,
                    $newStatus,
                    $data['notes'] ?? null
                );
                unset($data['status'], $data['notes']);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        // Update other fields
        if (! empty($data)) {
            $installation->update($data);
        }

        $installation->load([
            'salesOrder',
            'customer',
            'installationAddress',
            'installationSchedules.team',
        ]);

        return response()->json($installation);
    }

    /**
     * Remove the specified installation order (soft delete).
     */
    public function destroy(Request $request, InstallationOrder $installation): Response|JsonResponse
    {
        $this->authorize('delete', $installation);

        $request->validate([
            'deletion_reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $this->installationService->softDeleteInstallation(
                $installation->id,
                $request->deletion_reason
            );

            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Update installation order status.
     */
    public function updateStatus(Request $request, InstallationOrder $installation): JsonResponse
    {
        $this->authorize('updateStatus', $installation);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', InstallationStatus::values())],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $newStatus = InstallationStatus::from($validated['status']);
            $installation = $this->installationService->updateStatus(
                $installation->id,
                $newStatus,
                $validated['notes'] ?? null
            );

            $installation->load([
                'salesOrder',
                'customer',
                'installationSchedules.team',
            ]);

            return response()->json($installation);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
