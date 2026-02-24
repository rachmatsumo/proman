<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Agenda;
use App\Models\User;
use Carbon\Carbon;

class AgendaController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date', Carbon::today()->format('Y-m-d'));
        $agendas = Agenda::with('pics')->where('date', $date)->orderBy('start_time')->get();
        $users = User::orderBy('name')->get();

        return view('agenda.index', compact('agendas', 'users', 'date'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'nullable|after:start_time',
            'location' => 'nullable|string|max:255',
            'uic' => 'nullable|string|max:255',
            'meeting_id' => 'nullable|string|max:255',
            'status' => 'required|in:Pending,Done,Cancelled',
            'pics' => 'array',
            'pics.*' => 'exists:users,id',
        ]);

        $agenda = Agenda::create($validated);
        
        if (isset($validated['pics'])) {
            $agenda->pics()->sync($validated['pics']);
        }

        return response()->json(['success' => true, 'message' => 'Agenda created successfully']);
    }

    public function update(Request $request, Agenda $agenda)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'nullable',
            'location' => 'nullable|string|max:255',
            'uic' => 'nullable|string|max:255',
            'meeting_id' => 'nullable|string|max:255',
            'status' => 'required|in:Pending,Done,Cancelled',
            'pics' => 'array',
            'pics.*' => 'exists:users,id',
        ]);

        $agenda->update($validated);
        
        if (isset($validated['pics'])) {
            $agenda->pics()->sync($validated['pics']);
        }

        return response()->json(['success' => true, 'message' => 'Agenda updated successfully']);
    }

    public function destroy(Agenda $agenda)
    {
        $agenda->delete();
        return response()->json(['success' => true, 'message' => 'Agenda deleted successfully']);
    }
}
