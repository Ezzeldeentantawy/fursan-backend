<?php

namespace App\Http\Controllers;

use App\Models\JobApply;
use App\Models\JobListing;
use Illuminate\Http\Request;

class JobApplyController extends Controller
{
    // ─────────────────────────────────────────────────
    // POST /jobs/{job}/apply
    // Only authenticated candidates who have a CV on file can apply.
    // ─────────────────────────────────────────────────
    public function apply(Request $request, $jobId)
    {
        $candidate = $request->user();

        // Must be a candidate
        if ($candidate->role !== 'candidate') {
            return response()->json([
                'message' => 'Only candidates can apply for jobs.',
            ], 403);
        }

        // Must have a CV uploaded
        if (empty($candidate->cv)) {
            return response()->json([
                'message' => 'You must upload a CV to your profile before applying.',
            ], 422);
        }

        // Job must exist and be approved
        $job = JobListing::where('status', JobListing::STATUS_APPROVED)->findOrFail($jobId);

        // Prevent duplicate applications
        $alreadyApplied = JobApply::where('job_id', $job->id)
            ->where('candidate_id', $candidate->id)
            ->exists();

        if ($alreadyApplied) {
            return response()->json([
                'message' => 'You have already applied for this job.',
            ], 409);
        }

        $request->validate([
            'cover_letter' => 'nullable|string|max:5000',
        ]);

        $application = JobApply::create([
            'job_id'       => $job->id,
            'employer_id'  => $job->employer_id,
            'candidate_id' => $candidate->id,
            'cover_letter' => $request->cover_letter,
            'status'       => 'pending',
        ]);

        return response()->json([
            'message'     => 'Application submitted successfully.',
            'application' => $application->load('job:id,title,type,location'),
        ], 201);
    }

    // ─────────────────────────────────────────────────
    // GET /candidate/applications
    // List all jobs this candidate has applied to.
    // ─────────────────────────────────────────────────
    public function myApplications(Request $request)
    {
        $candidate = $request->user();

        $applications = JobApply::with('job:id,title,type,location,status')
            ->where('candidate_id', $candidate->id)
            ->latest()
            ->paginate(15);

        return response()->json($applications);
    }

    // ─────────────────────────────────────────────────
    // DELETE /candidate/applications/{application}
    // Withdraw (delete) a pending application.
    // ─────────────────────────────────────────────────
    public function withdraw(Request $request, $id)
    {
        $candidate = $request->user();

        $application = JobApply::where('candidate_id', $candidate->id)->findOrFail($id);

        if ($application->status !== 'pending') {
            return response()->json([
                'message' => 'You can only withdraw pending applications.',
            ], 403);
        }

        $application->delete();

        return response()->json(['message' => 'Application withdrawn successfully.']);
    }

    // ─────────────────────────────────────────────────
    // GET /employer/jobs/{job}/applications
    // Employer views all applications for one of their jobs.
    // ─────────────────────────────────────────────────
    public function jobApplications(Request $request, $jobId)
    {
        $employer = $request->user();

        // Ensure this job belongs to the employer
        $job = JobListing::where('employer_id', $employer->id)->findOrFail($jobId);

        $applications = JobApply::with('candidate:id,name,email,phone,cv,avatar')
            ->where('job_id', $job->id)
            ->latest()
            ->paginate(15);

        return response()->json($applications);
    }

    // ─────────────────────────────────────────────────
    // PATCH /employer/applications/{application}/status
    // Employer updates the status of an application.
    // ─────────────────────────────────────────────────
    public function updateStatus(Request $request, $id)
    {
        $employer = $request->user();

        $application = JobApply::where('employer_id', $employer->id)->findOrFail($id);

        $request->validate([
            'status' => 'required|in:reviewed,accepted,rejected',
        ]);

        $application->update(['status' => $request->status]);

        return response()->json([
            'message'     => 'Application status updated.',
            'application' => $application->fresh(),
        ]);
    }
}
