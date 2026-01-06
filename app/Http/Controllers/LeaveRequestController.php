<?php

namespace App\Http\Controllers;

use App\Services\LeaveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeaveRequestController extends Controller
{
    protected $leaveService;

    public function __construct(LeaveService $leaveService)
    {
        $this->leaveService = $leaveService;
    }

    /**
     * Get all leave requests for authenticated user
     */
    public function index(Request $request)
    {
        $leaveRequests = $request->user()
            ->leaveRequests()
            ->with('approver')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $leaveRequests
        ]);
    }

    /**
     * Create new leave request
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|min:10',
            'attachment' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $leaveRequest = $this->leaveService->createLeaveRequest(
                $request->user()->id,
                $request->all()
            );

            return response()->json([
                'success' => true,
                'message' => 'Leave request submitted successfully',
                'data' => $leaveRequest
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get specific leave request
     */
    public function show(Request $request, $id)
    {
        $leaveRequest = $request->user()
            ->leaveRequests()
            ->with('approver')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $leaveRequest
        ]);
    }

    /**
     * Cancel leave request
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->leaveService->cancelLeaveRequest($id, $request->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'Leave request cancelled successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get leave statistics
     */
    public function statistics(Request $request)
    {
        $statistics = $this->leaveService->getLeaveStatistics($request->user()->id);

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }
}