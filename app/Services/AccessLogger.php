<?php

namespace App\Services;

use App\Models\AccessLog;
use App\Models\User;

class AccessLogger
{
    /** Satu aksi yang sama dari pelaku yang sama dalam 5 menit dicatat sekali. */
    public static function record(User $actor, ?string $studentRef, string $action): void
    {
        $query = AccessLog::where('actor_id', $actor->id)
            ->where('action', $action)
            ->where('created_at', '>=', now()->subMinutes(5));

        if ($studentRef === null) {
            $query->whereNull('student_ref');
        } else {
            $query->where('student_ref', $studentRef);
        }

        if (! $query->exists()) {
            AccessLog::create([
                'actor_id' => $actor->id,
                'actor_role' => $actor->role,
                'student_ref' => $studentRef,
                'action' => $action,
                'created_at' => now(),
            ]);
        }
    }
}
