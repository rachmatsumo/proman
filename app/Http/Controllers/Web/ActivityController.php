<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Milestone;
use App\Http\Requests\StoreActivityRequest;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index()
    {
        $activities = Activity::with('milestone.subProgram.program')->latest()->get();
        return view('activities.index', compact('activities'));
    }

    public function create(Request $request)
    {
        $milestones = Milestone::with('subProgram.program')->get();
        $selectedMilestone = $request->query('milestone_id');
        return view('activities.create', compact('milestones', 'selectedMilestone'));
    }

    public function store(StoreActivityRequest $request)
    {
        Activity::create($request->validated());
        return redirect()->back()->with('success', 'Activity berhasil ditambahkan.');
    }

    public function show(string $id)
    {
        $activity = Activity::findOrFail($id);
        return view('activities.show', compact('activity'));
    }

    public function edit(string $id)
    {
        $activity = Activity::findOrFail($id);
        $milestones = Milestone::with('subProgram.program')->get();
        return view('activities.edit', compact('activity', 'milestones'));
    }

    public function update(StoreActivityRequest $request, string $id)
    {
        $activity = Activity::findOrFail($id);
        $activity->update($request->validated());
        return redirect()->back()->with('success', 'Activity berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $activity = Activity::findOrFail($id);
        $activity->delete();
        return redirect()->back()->with('success', 'Activity berhasil dihapus.');
    }
}
