<?php

namespace App\Http\Controllers;

use App\Models\JobListing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class JobController extends Controller
{
    // ─────────────────────────────────────────────────
    // PUBLIC: list all approved jobs (anyone can see)
    // ─────────────────────────────────────────────────
    public function index(Request $request)
    {
        $jobs = JobListing::with('employer:id,name,email')
            ->where('status', JobListing::STATUS_APPROVED)
            ->when($request->type, fn($q, $type) => $q->where('type', $type))
            ->when($request->location, fn($q, $loc) => $q->where('location', 'like', "%$loc%"))
            ->latest()
            ->paginate(15);

        return response()->json($jobs);
    }

    // ─────────────────────────────────────────────────
    // PUBLIC: show a single approved job
    // ─────────────────────────────────────────────────
    public function show($id)
    {
        $job = JobListing::with('employer:id,name,email')
            ->where('status', JobListing::STATUS_APPROVED)
            ->findOrFail($id);

        return response()->json($job);
    }

    // ─────────────────────────────────────────────────
    // EMPLOYER: create a new job (status=pending)
    // ─────────────────────────────────────────────────
    public function store(Request $request)
    {
        $employer = $request->user();

        $validated = $request->validate([
            'title'           => 'required|array',
            'title.*'         => 'string|max:255',
            'type'            => 'required|in:onsite,remote',
            'location'        => 'nullable|array',
            'location.*'      => 'string|max:255',
            'details'         => 'nullable|array',
            'details.*'       => 'string',
            'job_description' => 'required|array',
            'job_description.*'=> 'string',
            'requirements'    => 'required|array',
            'requirements.*'  => 'string',
            'benefits'        => 'nullable|array',
            'benefits.*'      => 'string',
            'overview'        => 'nullable|array',
            'overview.*'      => 'string',
            'image'           => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('jobs', 'public');
        }

        $job = JobListing::create([
            ...$validated,
            'employer_id' => $employer->id,
            'status'      => JobListing::STATUS_PENDING,
            'image'       => $imagePath,
        ]);

        return response()->json([
            'message' => 'Job submitted for approval.',
            'job'     => $job,
        ], 201);
    }

    // ─────────────────────────────────────────────────
    // EMPLOYER: update own job (only if still pending)
    // ─────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $employer = $request->user();

        $job = JobListing::where('employer_id', $employer->id)->findOrFail($id);

        if ($job->status !== JobListing::STATUS_PENDING) {
            return response()->json([
                'message' => 'Only pending jobs can be edited.',
            ], 403);
        }

        $validated = $request->validate([
            'title'           => 'sometimes|array',
            'title.*'         => 'string|max:255',
            'type'            => 'sometimes|in:onsite,remote',
            'location'        => 'nullable|array',
            'location.*'      => 'string|max:255',
            'details'         => 'nullable|array',
            'details.*'       => 'string',
            'job_description' => 'sometimes|array',
            'job_description.*'=> 'string',
            'requirements'    => 'sometimes|array',
            'requirements.*'  => 'string',
            'benefits'        => 'nullable|array',
            'benefits.*'      => 'string',
            'overview'        => 'nullable|array',
            'overview.*'      => 'string',
            'image'           => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($job->image) {
                Storage::disk('public')->delete($job->image);
            }
            $validated['image'] = $request->file('image')->store('jobs', 'public');
        }

        $job->update($validated);

        return response()->json([
            'message' => 'Job updated successfully.',
            'job'     => $job->fresh(),
        ]);
    }

    // ─────────────────────────────────────────────────
    // EMPLOYER: delete own job (soft delete)
    // ─────────────────────────────────────────────────
    public function destroy(Request $request, $id)
    {
        $employer = $request->user();

        $job = JobListing::where('employer_id', $employer->id)->findOrFail($id);

        $job->delete();

        return response()->json(['message' => 'Job deleted successfully.']);
    }

    // ─────────────────────────────────────────────────
    // EMPLOYER: list own jobs (all statuses)
    // ─────────────────────────────────────────────────
    public function myJobs(Request $request)
    {
        $employer = $request->user();

        $jobs = JobListing::where('employer_id', $employer->id)
            ->latest()
            ->paginate(15);

        return response()->json($jobs);
    }

    // ─────────────────────────────────────────────────
    // ADMIN: list all jobs (all statuses)
    // ─────────────────────────────────────────────────
    public function adminIndex(Request $request)
    {
        $jobs = JobListing::with('employer:id,name,email')
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15);

        return response()->json($jobs);
    }

    // ─────────────────────────────────────────────────
    // ADMIN: approve a pending job
    // ─────────────────────────────────────────────────
    public function approve($id)
    {
        $job = JobListing::findOrFail($id);

        $job->update(['status' => JobListing::STATUS_APPROVED]);

        return response()->json([
            'message' => 'Job approved successfully.',
            'job'     => $job,
        ]);
    }

    // ─────────────────────────────────────────────────
    // ADMIN: reject a pending job
    // ─────────────────────────────────────────────────
    public function reject($id)
    {
        $job = JobListing::findOrFail($id);

        $job->update(['status' => JobListing::STATUS_REJECTED]);

        return response()->json([
            'message' => 'Job rejected.',
            'job'     => $job,
        ]);
    }

    // ─────────────────────────────────────────────────
    // ADMIN: hard delete any job
    // ─────────────────────────────────────────────────
    public function adminDestroy($id)
    {
        $job = JobListing::withTrashed()->findOrFail($id);

        if ($job->image) {
            Storage::disk('public')->delete($job->image);
        }

        $job->forceDelete();

        return response()->json(['message' => 'Job permanently deleted.']);
    }
}
