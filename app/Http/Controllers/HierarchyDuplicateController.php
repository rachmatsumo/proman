<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubProgram;
use App\Models\Milestone;
use App\Models\Activity;
use Illuminate\Support\Facades\DB;

class HierarchyDuplicateController extends Controller
{
    public function duplicate(Request $request)
    {
        $request->validate([
            'type' => 'required|in:sub_program,milestone,activity',
            'id' => 'required|integer',
        ]);

        $type = $request->type;
        $id = $request->id;
        $withMilestones = $request->boolean('with_milestones');
        $withActivities = $request->boolean('with_activities');

        return DB::transaction(function () use ($type, $id, $withMilestones, $withActivities) {
            if ($type === 'sub_program') {
                return $this->duplicateSubProgram($id, $withMilestones, $withActivities);
            } elseif ($type === 'milestone') {
                return $this->duplicateMilestone($id, $withActivities);
            } elseif ($type === 'activity') {
                return $this->duplicateActivity($id);
            }
        });
    }

    protected function duplicateSubProgram($id, $withMilestones, $withActivities)
    {
        $original = SubProgram::findOrFail($id);
        $clone = $original->replicate();
        $clone->name = $original->name . ' (Copy)';
        
        // Find current min sort_order to put at top
        $minOrder = SubProgram::where('program_id', $original->program_id)->min('sort_order') ?? 0;
        $clone->sort_order = $minOrder - 1;
        $clone->save();

        if ($withMilestones) {
            foreach ($original->milestones as $ms) {
                $this->cloneMilestoneToSub($ms, $clone->id, $withActivities, false);
            }
        }

        return response()->json(['success' => true, 'message' => 'Sub Program berhasil diduplikasi.']);
    }

    protected function duplicateMilestone($id, $withActivities)
    {
        $original = Milestone::findOrFail($id);
        $clone = $this->cloneMilestoneToSub($original, $original->sub_program_id, $withActivities, true);
        
        return response()->json(['success' => true, 'message' => 'Milestone berhasil diduplikasi.']);
    }

    protected function cloneMilestoneToSub($original, $subProgramId, $withActivities, $addSuffix = false)
    {
        $clone = $original->replicate();
        $clone->sub_program_id = $subProgramId;
        if ($addSuffix) {
            $clone->name = $original->name . ' (Copy)';
        }
        
        $minOrder = Milestone::where('sub_program_id', $subProgramId)->min('sort_order') ?? 0;
        $clone->sort_order = $minOrder - 1;
        $clone->save();

        if ($withActivities) {
            foreach ($original->activities as $act) {
                $this->cloneActivityToMs($act, $clone->id, false);
            }
        }
        return $clone;
    }

    protected function duplicateActivity($id)
    {
        $original = Activity::findOrFail($id);
        $this->cloneActivityToMs($original, $original->milestone_id, true);
        
        return response()->json(['success' => true, 'message' => 'Activity berhasil diduplikasi.']);
    }

    protected function cloneActivityToMs($original, $milestoneId, $addSuffix = false)
    {
        $clone = $original->replicate();
        $clone->milestone_id = $milestoneId;
        if ($addSuffix) {
            $clone->name = $original->name . ' (Copy)';
        }
        
        $minOrder = Activity::where('milestone_id', $milestoneId)->min('sort_order') ?? 0;
        $clone->sort_order = $minOrder - 1;
        $clone->save();
        
        return $clone;
    }
}
