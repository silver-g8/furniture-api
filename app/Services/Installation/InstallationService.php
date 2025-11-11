<?php

declare(strict_types=1);

namespace App\Services\Installation;

use App\Enums\InstallationStatus;
use App\Models\InstallationOrder;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;

class InstallationService
{
    /**
     * Create installation order from sales order.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \Exception
     */
    public function createFromSalesOrder(int $salesOrderId, array $data): InstallationOrder
    {
        $salesOrder = SalesOrder::findOrFail($salesOrderId);

        // Validate sales order status
        if (! in_array($salesOrder->status, ['paid', 'completed'])) {
            throw new \Exception('Sales order must be in paid or completed status to create installation order.');
        }

        DB::beginTransaction();

        try {
            $installationOrder = InstallationOrder::create([
                'sales_order_id' => $salesOrderId,
                'customer_id' => $data['customer_id'] ?? $salesOrder->customer_id,
                'installation_address_id' => $data['installation_address_id'] ?? null,
                'installation_address_override' => $data['installation_address_override'] ?? null,
                'installation_contact_name' => $data['installation_contact_name'] ?? null,
                'installation_contact_phone' => $data['installation_contact_phone'] ?? null,
                'status' => InstallationStatus::Draft,
                'notes' => $data['notes'] ?? null,
            ]);

            DB::commit();

            return $installationOrder;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update installation order status.
     *
     * @throws \Exception
     */
    public function updateStatus(int $id, InstallationStatus $newStatus, ?string $notes = null): InstallationOrder
    {
        $installation = InstallationOrder::findOrFail($id);

        if (! $this->canTransitionTo($installation->status, $newStatus)) {
            throw new \Exception("Cannot transition from {$installation->status->value} to {$newStatus->value}");
        }

        // Additional validation for completion
        if ($newStatus === InstallationStatus::Completed) {
            if (! $installation->hasRequiredAfterPhotos()) {
                throw new \Exception('At least one "after" photo is required to complete installation.');
            }
        }

        DB::beginTransaction();

        try {
            $installation->status = $newStatus;

            // Handle SLA pause/resume
            if ($newStatus->shouldPauseSla() && ! $installation->isSlaPaused()) {
                $installation->sla_paused_at = now();
                $installation->sla_resumed_at = null;
            } elseif ($newStatus === InstallationStatus::Scheduled && $installation->isSlaPaused()) {
                $installation->sla_resumed_at = now();
            }

            if ($notes) {
                $installation->notes = ($installation->notes ? $installation->notes."\n\n" : '').$notes;
            }

            $installation->save();

            DB::commit();

            return $installation;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Check if status transition is valid.
     */
    public function canTransitionTo(InstallationStatus $current, InstallationStatus $new): bool
    {
        return $current->canTransitionTo($new);
    }

    /**
     * Complete installation order.
     *
     * @throws \Exception
     */
    public function completeInstallation(int $id, string $notes): InstallationOrder
    {
        return $this->updateStatus($id, InstallationStatus::Completed, $notes);
    }

    /**
     * Soft delete installation order.
     *
     * @throws \Exception
     */
    public function softDeleteInstallation(int $id, string $reason): bool
    {
        $installation = InstallationOrder::findOrFail($id);

        if ($installation->status === InstallationStatus::Completed) {
            throw new \Exception('Cannot delete completed installation order.');
        }

        DB::beginTransaction();

        try {
            $installation->deletion_reason = $reason;
            $installation->save();
            $installation->delete();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
