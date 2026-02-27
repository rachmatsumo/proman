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
            'type' => 'required|in:sub_program,milestone,key_result,activity,sub_activity',
            'order' => 'required|array',
            'order.*' => 'integer',
            'parent_id' => 'nullable|integer',
        ]);

        $type = $request->type;
        $order = $request->order;
        $parentId = $request->parent_id;

        foreach ($order as $index => $id) {
            if ($type === 'sub_program') {
                SubProgram::where('id', $id)->update(['sort_order' => $index]);
            } elseif ($type === 'milestone') {
                $updateData = ['sort_order' => $index];
                if ($parentId) $updateData['sub_program_id'] = $parentId;
                Milestone::where('id', $id)->update($updateData);
            } elseif ($type === 'key_result') {
                $updateData = ['sort_order' => $index + 1000];
                if ($parentId) $updateData['sub_program_id'] = $parentId;
                Milestone::where('id', $id)->update($updateData);
            } elseif ($type === 'activity') {
                $updateData = ['sort_order' => $index];
                if ($parentId) $updateData['milestone_id'] = $parentId;
                Activity::where('id', $id)->update($updateData);
            }
        }

        return response()->json(['success' => true]);
    }
}
