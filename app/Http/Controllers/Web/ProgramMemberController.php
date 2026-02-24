<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\Request;

class ProgramMemberController extends Controller
{
    /**
     * Add a member to the program
     */
    public function store(Request $request, Program $program)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:administrator,manager,member',
        ]);

        $program->members()->attach($request->user_id, ['role' => $request->role]);

        return redirect()->back()->with('success', 'Member added successfully.');
    }

    /**
     * Update member's role
     */
    public function update(Request $request, Program $program, User $user)
    {
        $request->validate([
            'role' => 'required|in:administrator,manager,member',
        ]);

        $program->members()->updateExistingPivot($user->id, ['role' => $request->role]);

        return redirect()->back()->with('success', 'Member role updated successfully.');
    }

    /**
     * Remove a member from the program
     */
    public function destroy(Program $program, User $user)
    {
        $program->members()->detach($user->id);

        return redirect()->back()->with('success', 'Member removed successfully.');
    }
}
