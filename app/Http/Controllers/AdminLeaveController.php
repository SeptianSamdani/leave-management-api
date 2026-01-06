<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Services\LeaveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminLeaveController extends Controller
{
    protected $leaveService;

    public function __construct(LeaveService $leaveService)
    {
        $this->leaveService = $leaveService;
    }

    /**
     * Get all leave requests (Admin only)
     */
    public function index(Request $request)
    {
        $query = LeaveRequest::with(['user', 'approver']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('start_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('end_date', '<=', $request->end_date);
        }

        $leaveRequests = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $leaveRequests
        ]);
    }

    /**
     * Get specific leave request
     */
    public function show($id)
    {
        $leaveRequest = LeaveRequest::with(['user', 'approver'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $leaveRequest
        ]);
    }

    /**
     * Approve leave request
     */
    public function approve(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'admin_notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $leaveRequest = $this->leaveService->approveLeaveRequest(
                $id,
                $request->user()->id,
                $request->admin_notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Leave request approved successfully',
                'data' => $leaveRequest
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Reject leave request
     */
    public function reject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'admin_notes' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $leaveRequest = $this->leaveService->rejectLeaveRequest(
                $id,
                $request->user()->id,
                $request->admin_notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Leave request rejected',
                'data' => $leaveRequest
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get dashboard statistics
     */
    public function dashboard()
    {
        $statistics = [
            'total_requests' => LeaveRequest::count(),
            'pending_requests' => LeaveRequest::pending()->count(),
            'approved_requests' => LeaveRequest::approved()->count(),
            'rejected_requests' => LeaveRequest::rejected()->count(),
            'recent_requests' => LeaveRequest::with('user')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }
}