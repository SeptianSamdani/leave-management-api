<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\LeaveQuota;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LeaveService
{
    /**
     * Create leave request
     */
    public function createLeaveRequest($userId, array $data)
    {
        return DB::transaction(function () use ($userId, $data) {
            // Get or create quota for current year
            $quota = LeaveQuota::firstOrCreate(
                [
                    'user_id' => $userId,
                    'year' => now()->year
                ],
                [
                    'total' => 12,
                    'used' => 0,
                    'remaining' => 12
                ]
            );

            // Calculate total days
            $totalDays = $this->calculateWorkingDays($data['start_date'], $data['end_date']);

            // Validate quota
            if (!$quota->canTakeLeave($totalDays)) {
                throw new \Exception("Insufficient leave quota. You have {$quota->remaining} days remaining.");
            }

            // Check for overlapping leave
            if ($this->hasOverlappingLeave($userId, $data['start_date'], $data['end_date'])) {
                throw new \Exception("You already have a leave request for this period.");
            }

            // Handle file upload
            $attachmentPath = null;
            if (isset($data['attachment'])) {
                $attachmentPath = $data['attachment']->store('leave-attachments', 'public');
            }

            // Create leave request
            $leaveRequest = LeaveRequest::create([
                'user_id' => $userId,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'total_days' => $totalDays,
                'reason' => $data['reason'],
                'attachment' => $attachmentPath,
                'status' => 'pending',
            ]);

            return $leaveRequest->load('user');
        });
    }

    /**
     * Approve leave request
     */
    public function approveLeaveRequest($leaveRequestId, $adminId, $notes = null)
    {
        return DB::transaction(function () use ($leaveRequestId, $adminId, $notes) {
            $leaveRequest = LeaveRequest::findOrFail($leaveRequestId);

            if (!$leaveRequest->isPending()) {
                throw new \Exception("Only pending leave requests can be approved.");
            }

            // Get quota
            $quota = LeaveQuota::where('user_id', $leaveRequest->user_id)
                ->where('year', now()->year)
                ->firstOrFail();

            // Check quota again
            if (!$quota->canTakeLeave($leaveRequest->total_days)) {
                throw new \Exception("Employee has insufficient leave quota.");
            }

            // Deduct quota
            $quota->deductQuota($leaveRequest->total_days);

            // Update leave request
            $leaveRequest->update([
                'status' => 'approved',
                'approved_by' => $adminId,
                'approved_at' => now(),
                'admin_notes' => $notes,
            ]);

            return $leaveRequest->load(['user', 'approver']);
        });
    }

    /**
     * Reject leave request
     */
    public function rejectLeaveRequest($leaveRequestId, $adminId, $notes = null)
    {
        $leaveRequest = LeaveRequest::findOrFail($leaveRequestId);

        if (!$leaveRequest->isPending()) {
            throw new \Exception("Only pending leave requests can be rejected.");
        }

        $leaveRequest->update([
            'status' => 'rejected',
            'approved_by' => $adminId,
            'approved_at' => now(),
            'admin_notes' => $notes,
        ]);

        return $leaveRequest->load(['user', 'approver']);
    }

    /**
     * Cancel leave request (restore quota if approved)
     */
    public function cancelLeaveRequest($leaveRequestId, $userId)
    {
        return DB::transaction(function () use ($leaveRequestId, $userId) {
            $leaveRequest = LeaveRequest::where('id', $leaveRequestId)
                ->where('user_id', $userId)
                ->firstOrFail();

            // If approved, restore quota
            if ($leaveRequest->isApproved()) {
                $quota = LeaveQuota::where('user_id', $userId)
                    ->where('year', now()->year)
                    ->firstOrFail();

                $quota->restoreQuota($leaveRequest->total_days);
            }

            // Delete leave request
            if ($leaveRequest->attachment) {
                Storage::disk('public')->delete($leaveRequest->attachment);
            }

            $leaveRequest->delete();

            return true;
        });
    }

    /**
     * Calculate working days (excluding weekends)
     */
    private function calculateWorkingDays($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($start->gt($end)) {
            throw new \Exception("Start date must be before end date.");
        }

        $totalDays = 0;
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (!$date->isWeekend()) {
                $totalDays++;
            }
        }

        if ($totalDays === 0) {
            throw new \Exception("Leave period must include at least one working day.");
        }

        return $totalDays;
    }

    /**
     * Check for overlapping leave requests
     */
    private function hasOverlappingLeave($userId, $startDate, $endDate)
    {
        return LeaveRequest::where('user_id', $userId)
            ->where('status', '!=', 'rejected')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                    });
            })
            ->exists();
    }

    /**
     * Get leave statistics
     */
    public function getLeaveStatistics($userId)
    {
        $quota = LeaveQuota::where('user_id', $userId)
            ->where('year', now()->year)
            ->first();

        $leaveRequests = LeaveRequest::where('user_id', $userId)
            ->whereYear('start_date', now()->year)
            ->get();

        return [
            'quota' => [
                'total' => $quota->total ?? 12,
                'used' => $quota->used ?? 0,
                'remaining' => $quota->remaining ?? 12,
            ],
            'leave_summary' => [
                'pending' => $leaveRequests->where('status', 'pending')->count(),
                'approved' => $leaveRequests->where('status', 'approved')->count(),
                'rejected' => $leaveRequests->where('status', 'rejected')->count(),
            ]
        ];
    }
}