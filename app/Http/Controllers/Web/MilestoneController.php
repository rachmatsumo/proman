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
        $data = $request->validated();
        
        // Validate total weight
        $currentWeight = Milestone::where('sub_program_id', $data['sub_program_id'])->sum('bobot');
        if (($currentWeight + ($data['bobot'] ?? 0)) > 100) {
            $msg = "Total bobot milestone dalam sub program ini tidak boleh melebihi 100%. (Saat ini: $currentWeight%)";
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->withErrors(['bobot' => $msg])->withInput();
        }

        $minOrder = Milestone::where('sub_program_id', $data['sub_program_id'])->min('sort_order') ?? 0;
        $data['sort_order'] = $minOrder - 1;
        
        $milestone = Milestone::create($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Milestone berhasil ditambahkan.',
                'data' => $milestone
            ]);
        }

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
        $data = $request->validated();

        // Validate total weight
        $currentWeight = Milestone::where('sub_program_id', $milestone->sub_program_id)
            ->where('id', '!=', $id)
            ->sum('bobot');
        
        if (($currentWeight + ($data['bobot'] ?? 0)) > 100) {
            $msg = "Total bobot milestone dalam sub program ini tidak boleh melebihi 100%. (Saat ini: $currentWeight%)";
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->withErrors(['bobot' => $msg])->withInput();
        }

        $milestone->update($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Milestone berhasil diperbarui.',
                'data' => $milestone
            ]);
        }

        return redirect()->back()->with('success', 'Milestone berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $milestone = Milestone::findOrFail($id);
        $milestone->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Milestone berhasil dihapus.'
            ]);
        }

        return redirect()->back()->with('success', 'Milestone berhasil dihapus.');
    }
}
