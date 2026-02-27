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
            'type' => 'required|in:sub_program,milestone,activity,sub_activity',
            'id' => 'required|integer',
        ]);

        $type = $request->type;
        $id = $request->id;
        
        $withMilestones = $request->has('with_milestones') && filter_var($request->get('with_milestones'), FILTER_VALIDATE_BOOLEAN);
        
        $withActivities = false;
        $withSubActivities = false;

        if ($type === 'sub_program') {
            $withActivities = $request->has('with_activities_sub') && filter_var($request->get('with_activities_sub'), FILTER_VALIDATE_BOOLEAN);
            $withSubActivities = $request->has('with_sub_activities_sub') && filter_var($request->get('with_sub_activities_sub'), FILTER_VALIDATE_BOOLEAN);
        } elseif ($type === 'milestone') {
            $withActivities = $request->has('with_activities_ms') && filter_var($request->get('with_activities_ms'), FILTER_VALIDATE_BOOLEAN);
            $withSubActivities = $request->has('with_sub_activities_ms') && filter_var($request->get('with_sub_activities_ms'), FILTER_VALIDATE_BOOLEAN);
        } elseif ($type === 'activity') {
            $withSubActivities = $request->has('with_sub_activities_act') && filter_var($request->get('with_sub_activities_act'), FILTER_VALIDATE_BOOLEAN);
        }

        return DB::transaction(function () use ($type, $id, $withMilestones, $withActivities, $withSubActivities) {
            if ($type === 'sub_program') {
                return $this->duplicateSubProgram($id, $withMilestones, $withActivities, $withSubActivities);
            } elseif ($type === 'milestone') {
                return $this->duplicateMilestone($id, $withActivities, $withSubActivities);
            } elseif ($type === 'activity') {
                return $this->duplicateActivity($id, $withSubActivities);
            } elseif ($type === 'sub_activity') {
                return $this->duplicateSubActivity($id);
            }
        });
    }

    protected function duplicateSubProgram($id, $withMilestones, $withActivities, $withSubActivities)
    {
        $original = SubProgram::findOrFail($id);
        $clone = $original->replicate();
        $clone->name = $original->name . ' (Copy)';
        
        $minOrder = SubProgram::where('program_id', $original->program_id)->min('sort_order') ?? 0;
        $clone->sort_order = $minOrder - 1;
        $clone->save();

        if ($withMilestones) {
            foreach ($original->milestones->reverse() as $ms) {
                $this->cloneMilestoneToSub($ms, $clone->id, $withActivities, $withSubActivities, false);
            }
        }

        return response()->json(['success' => true, 'message' => 'Sub Program berhasil diduplikasi.']);
    }

    protected function duplicateMilestone($id, $withActivities, $withSubActivities)
    {
        $original = Milestone::findOrFail($id);
        $clone = $this->cloneMilestoneToSub($original, $original->sub_program_id, $withActivities, $withSubActivities, true);
        
        return response()->json(['success' => true, 'message' => 'Milestone berhasil diduplikasi.']);
    }

    protected function cloneMilestoneToSub($original, $subProgramId, $withActivities, $withSubActivities, $addSuffix = false)
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
            foreach ($original->activities->reverse() as $act) {
                $this->cloneActivityToMs($act, $clone->id, $withSubActivities, false);
            }
        }
        return $clone;
    }

    protected function duplicateActivity($id, $withSubActivities)
    {
        $original = Activity::findOrFail($id);
        $this->cloneActivityToMs($original, $original->milestone_id, $withSubActivities, true);
        
        return response()->json(['success' => true, 'message' => 'Activity berhasil diduplikasi.']);
    }

    protected function cloneActivityToMs($original, $milestoneId, $withSubActivities, $addSuffix = false)
    {
        $clone = $original->replicate();
        $clone->milestone_id = $milestoneId;
        if ($addSuffix) {
            $clone->name = $original->name . ' (Copy)';
        }
        
        $minOrder = Activity::where('milestone_id', $milestoneId)->min('sort_order') ?? 0;
        $clone->sort_order = $minOrder - 1;
        $clone->save();
        
        if ($withSubActivities) {
            foreach ($original->subActivities->reverse() as $subAct) {
                $this->cloneSubActivityToAct($subAct, $clone->id, false);
            }
        }
        
        return $clone;
    }

    protected function duplicateSubActivity($id)
    {
        $original = \App\Models\SubActivity::findOrFail($id);
        $this->cloneSubActivityToAct($original, $original->activity_id, true);
        
        return response()->json(['success' => true, 'message' => 'Sub Activity berhasil diduplikasi.']);
    }

    protected function cloneSubActivityToAct($original, $activityId, $addSuffix = false)
    {
        $clone = $original->replicate();
        $clone->activity_id = $activityId;
        if ($addSuffix) {
            $clone->name = $original->name . ' (Copy)';
        }
        
        $minOrder = \App\Models\SubActivity::where('activity_id', $activityId)->min('sort_order') ?? 0;
        $clone->sort_order = $minOrder - 1;
        $clone->save();
        
        return $clone;
    }
}
