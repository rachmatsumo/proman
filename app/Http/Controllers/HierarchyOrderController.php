<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubProgram;
use App\Models\Milestone;
use App\Models\Activity;

class HierarchyOrderController extends Controller
{
    public function updateOrder(Request $request)
    {
        $request->validate([
            'type' => 'required|in:sub_program,milestone,activity',
            'order' => 'required|array',
            'order.*' => 'integer',
        ]);

        $type = $request->type;
        $order = $request->order;

        foreach ($order as $index => $id) {
            if ($type === 'sub_program') {
                SubProgram::where('id', $id)->update(['sort_order' => $index]);
            } elseif ($type === 'milestone') {
                Milestone::where('id', $id)->update(['sort_order' => $index]);
            } elseif ($type === 'activity') {
                Activity::where('id', $id)->update(['sort_order' => $index]);
            }
        }

        return response()->json(['success' => true]);
    }
}
