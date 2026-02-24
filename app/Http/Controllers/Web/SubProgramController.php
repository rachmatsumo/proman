<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SubProgram;
use App\Models\Program;
use App\Http\Requests\StoreSubProgramRequest;
use Illuminate\Http\Request;

class SubProgramController extends Controller
{
    public function index()
    {
        $subPrograms = SubProgram::with('program')->latest()->get();
        return view('sub_programs.index', compact('subPrograms'));
    }

    public function create(Request $request)
    {
        $programs = Program::all();
        $selectedProgram = $request->query('program_id');
        return view('sub_programs.create', compact('programs', 'selectedProgram'));
    }

    public function store(StoreSubProgramRequest $request)
    {
        SubProgram::create($request->validated());
        return redirect()->back()->with('success', 'Sub Program berhasil ditambahkan.');
    }

    public function show(string $id)
    {
        $subProgram = SubProgram::findOrFail($id);
        return view('sub_programs.show', compact('subProgram'));
    }

    public function edit(string $id)
    {
        $subProgram = SubProgram::findOrFail($id);
        $programs = Program::all();
        return view('sub_programs.edit', compact('subProgram', 'programs'));
    }

    public function update(StoreSubProgramRequest $request, string $id)
    {
        $subProgram = SubProgram::findOrFail($id);
        $subProgram->update($request->validated());
        return redirect()->back()->with('success', 'Sub Program berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $subProgram = SubProgram::findOrFail($id);
        $subProgram->delete();
        return redirect()->back()->with('success', 'Sub Program berhasil dihapus.');
    }
}
