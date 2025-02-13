<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeaveApplication;
use App\Models\Employee;

class LeaveApplicationController extends Controller
{
    /**
     * List all leave applications (for admin)
     */
    public function index()
    {
        $leaves = LeaveApplication::with(['employee', 'approver'])->orderBy('created_at', 'desc')->get();
        return response()->json($leaves);
    }

    /**
     * Apply for leave (store a new leave request)
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id'    => 'required|exists:employees,id',
            'start_date'     => 'required|string|date_format:Y-m-d|after_or_equal:today',
            'end_date'       => 'required|string|date_format:Y-m-d|after_or_equal:start_date',
            'total_days'     => 'required|integer|min:1',
            'reason'         => 'required|string|max:500',
        ]);

        $leave = LeaveApplication::create([
            'employee_id' => $request->employee_id,
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date,
            'total_days'  => $request->total_days,
            'reason'      => $request->reason,
            'status'      => 'pending',
        ]);

        return response()->json(['message' => 'Leave application submitted successfully!', 'leave' => $leave], 201);
    }

    /**
     * View a single leave application
     */
    public function show($id)
    {
        $leave = LeaveApplication::with(['employee', 'approver'])->findOrFail($id);
        return response()->json($leave);
    }

    /**
     * Approve or reject leave application
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'status'      => 'required|in:approved,rejected',
            'approved_by' => 'required_if:status,approved|exists:employees,id',
            'admin_notes' => 'nullable|string|max:500',
        ]);

        $leave = LeaveApplication::findOrFail($id);
        $leave->update([
            'status'      => $request->status,
            'approved_by' => $request->approved_by,
            'admin_notes' => $request->admin_notes,
        ]);

        // Deduct remaining off days if leave is approved
        if ($request->status === 'approved') {
            $employee = Employee::findOrFail($leave->employee_id);
            $employee->remaining_off_days -= $leave->total_days;
            $employee->save();
        }

        return response()->json(['message' => "Leave request {$request->status} successfully!", 'leave' => $leave]);
    }

    /**
     * Delete a leave application (Only if it's still pending)
     */
    public function destroy($id)
    {
        $leave = LeaveApplication::findOrFail($id);

        if ($leave->status !== 'pending') {
            return response()->json(['message' => 'Only pending leave applications can be deleted.'], 403);
        }

        $leave->delete();

        return response()->json(['message' => 'Leave application deleted successfully.']);
    }
}
