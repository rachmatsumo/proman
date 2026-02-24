<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Milestone;
use App\Models\SubProgram;
use App\Http\Requests\StoreMilestoneRequest;
use Illuminate\Http\Request;

class MilestoneController extends Controller
{
    public function index()
    {
        $milestones = Milestone::with('subProgram.program')->latest()->get();
        return view('milestones.index', compact('milestones'));
    }

    public function create(Request $request)
    {
        $subPrograms = SubProgram::with('program')->get();
        $selectedSubProgram = $request->query('sub_program_id');
        return view('milestones.create', compact('subPrograms', 'selectedSubProgram'));
    }

    public function store(StoreMilestoneRequest $request)
    {
        Milestone::create($request->validated());
        return redirect()->back()->with('success', 'Milestone berhasil ditambahkan.');
    }

    public function show(string $id)
    {
        $milestone = Milestone::findOrFail($id);
        return view('milestones.show', compact('milestone'));
    }

    public function edit(string $id)
    {
        $milestone = Milestone::findOrFail($id);
        $subPrograms = SubProgram::with('program')->get();
        return view('milestones.edit', compact('milestone', 'subPrograms'));
    }

    public function update(StoreMilestoneRequest $request, string $id)
    {
        $milestone = Milestone::findOrFail($id);
        $milestone->update($request->validated());
        return redirect()->back()->with('success', 'Milestone berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $milestone = Milestone::findOrFail($id);
        $milestone->delete();
        return redirect()->back()->with('success', 'Milestone berhasil dihapus.');
    }
}
