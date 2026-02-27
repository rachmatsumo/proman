<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SubActivity;
use App\Models\Activity;
use Illuminate\Http\Request;

class SubActivityController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'activity_id' => 'required|exists:activities,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'progress' => 'required|integer|min:0|max:100',
            'status' => 'required|string',
            'uic' => 'nullable|string|max:255',
            'pic' => 'nullable|string|max:255',
        ]);

        $minOrder = SubActivity::where('activity_id', $data['activity_id'])->min('sort_order') ?? 0;
        $data['sort_order'] = $minOrder - 1;

        $subActivity = SubActivity::create($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Sub Activity berhasil ditambahkan.',
                'data' => $subActivity
            ]);
        }

        return redirect()->back()->with('success', 'Sub Activity berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $subActivity = SubActivity::findOrFail($id);
        
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'progress' => 'required|integer|min:0|max:100',
            'status' => 'required|string',
            'uic' => 'nullable|string|max:255',
            'pic' => 'nullable|string|max:255',
        ]);

        $subActivity->update($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Sub Activity berhasil diperbarui.',
                'data' => $subActivity
            ]);
        }

        return redirect()->back()->with('success', 'Sub Activity berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $subActivity = SubActivity::findOrFail($id);
        $subActivity->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Sub Activity berhasil dihapus.'
            ]);
        }

        return redirect()->back()->with('success', 'Sub Activity berhasil dihapus.');
    }
}
