<?php

namespace App\Services;

use App\Models\Release;
use App\Models\ReleaseApproval;
use App\Models\ReleaseHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReleaseWorkflowService
{
    public function __construct(private AuditService $audit) {}

    public function transition(Release $release, string $to, User $actor, ?string $comments = null): Release
    {
        return DB::transaction(function () use ($release, $to, $actor, $comments) {
            $release = Release::lockForUpdate()->findOrFail($release->id);
            $from = $release->status;
            $allowed = ['DRAFT' => ['SUBMITTED'], 'SUBMITTED' => ['UNDER_REVIEW'], 'UNDER_REVIEW' => ['APPROVED', 'REJECTED'], 'REJECTED' => ['DRAFT'], 'APPROVED' => ['RELEASED'], 'RELEASED' => ['CLOSED']];
            if (! in_array($to, $allowed[$from] ?? [], true)) {
                throw ValidationException::withMessages(['status' => "Unable to move this release from {$from} to {$to}."]);
            }
            $permissions = ['SUBMITTED' => 'releases.submit', 'UNDER_REVIEW' => 'releases.review', 'APPROVED' => 'releases.approve', 'REJECTED' => 'releases.reject', 'DRAFT' => 'releases.update', 'RELEASED' => 'releases.release', 'CLOSED' => 'releases.close'];
            abort_unless($actor->can($permissions[$to]), 403, 'You do not have permission to perform this workflow action.');
            if ($to === 'REJECTED' && blank($comments)) {
                throw ValidationException::withMessages(['comments' => 'A rejection comment is required.']);
            }
            if ($to === 'APPROVED' && $release->creator->hasRole('inventory') && ! $actor->hasPermission('releases.approve')) {
                abort(403);
            }
            $release->update(['status' => $to]);
            ReleaseHistory::create(['release_id' => $release->id, 'user_id' => $actor->id, 'action' => $to, 'previous_status' => $from, 'new_status' => $to, 'comments' => $comments]);
            if (in_array($to, ['APPROVED', 'REJECTED'], true)) {
                ReleaseApproval::create(['release_id' => $release->id, 'approver_id' => $actor->id, 'decision' => $to, 'comments' => $comments, 'previous_status' => $from, 'new_status' => $to, 'ip_address' => request()->ip()]);
            }
            $this->audit->record($to, 'Releases', "Release {$release->number} moved from {$from} to {$to}", $release, ['status' => $from], ['status' => $to], 'SUCCESS', ['comments' => $comments]);
            if ($to === 'SUBMITTED' && $release->created_by === $actor->id && ($actor->hasRole('manager') || $actor->hasRole('admin'))) {
                $release->update(['status' => 'APPROVED']);
                ReleaseHistory::create(['release_id' => $release->id, 'user_id' => $actor->id, 'action' => 'APPROVAL_NOT_REQUIRED', 'previous_status' => 'SUBMITTED', 'new_status' => 'APPROVED', 'comments' => 'Approval not required for manager/admin submission.']);
                $this->audit->record('APPROVAL_NOT_REQUIRED', 'Releases', "Release {$release->number} approved without a separate approval step", $release, ['status' => 'SUBMITTED'], ['status' => 'APPROVED']);
            }

            return $release;
        });
    }
}
