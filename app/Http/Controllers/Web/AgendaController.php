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
        $view = $request->query('view', 'daily');
        $date = $request->query('date', Carbon::today()->format('Y-m-d'));
        $carbonDate = Carbon::parse($date);
        $users = User::orderBy('name')->get();

        if ($view === 'weekly') {
            $startOfWeek = $carbonDate->copy()->startOfWeek();
            $endOfWeek = $carbonDate->copy()->endOfWeek();
            
            $agendas = Agenda::with('pics')
                ->whereBetween('date', [$startOfWeek->format('Y-m-d'), $endOfWeek->format('Y-m-d')])
                ->orderBy('date')
                ->orderBy('start_time')
                ->get()
                ->groupBy(function($item) {
                    return \Carbon\Carbon::parse($item->date)->format('Y-m-d');
                });

            return view('agenda.index', compact('agendas', 'users', 'date', 'view', 'startOfWeek', 'endOfWeek'));
        }

        // Daily view (default)
        $agendas = Agenda::with('pics')->where('date', $date)->orderBy('start_time')->get();
        return view('agenda.index', compact('agendas', 'users', 'date', 'view'));
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
