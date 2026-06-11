<?php

namespace Ddt\JobBot\Handlers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class FindCandidatesHandler extends BaseHandler
{
    public function handle(string $lower, string $original, mixed $user): JsonResponse
    {
        $skill = $this->extractSkillName($lower);

        if (!$skill) {
            session(['pending_intent' => 'find_candidates', 'pending_step' => 1]);
            return $this->respond('🔍 Kaunsi skill ke candidates dhunne hain? (e.g. Laravel, React, PHP)', null);
        }

        return $this->searchBySkill($skill);
    }

    public function searchBySkill(string $skill): JsonResponse
    {
        $skill = strtolower(trim($skill));
        $limit = config('jobbot.search_limit', 20);

        $candidates = DB::table('users as u')
            ->join('candidates as c', 'c.user_id', '=', 'u.id')
            ->leftJoin('skillables as sa', function ($join) {
                $join->on('sa.skillable_id', '=', 'u.id')
                    ->where('sa.skillable_type', '=', 'App\\Models\\User');
            })
            ->leftJoin('skills as sk', 'sk.id', '=', 'sa.skill_id')
            ->select(
                'u.id', 'u.name', 'u.email', 'u.mobile_number',
                'u.position', 'u.experience_type',
                DB::raw("GROUP_CONCAT(DISTINCT sk.name ORDER BY sk.name SEPARATOR ', ') as skills")
            )
            ->where(function ($q) use ($skill) {
                $q->whereRaw('LOWER(sk.name) LIKE ?', ["%{$skill}%"])
                  ->orWhereRaw('LOWER(u.position) LIKE ?', ["%{$skill}%"]);
            })
            ->groupBy('u.id', 'u.name', 'u.email', 'u.mobile_number', 'u.position', 'u.experience_type')
            ->limit($limit)
            ->get();

        if ($candidates->isEmpty()) {
            return $this->respond("😔 \"<strong>{$skill}</strong>\" skill ke koi candidate nahi mile.", null);
        }

        return $this->respond(
            "🔍 \"<strong>{$skill}</strong>\" skill ke <strong>{$candidates->count()}</strong> candidates mile:",
            [
                'columns' => ['Name', 'Position', 'Experience', 'Skills', 'Contact'],
                'rows'    => $candidates->map(fn($c) => [
                    'Name'       => $c->name,
                    'Position'   => $c->position ?? '—',
                    'Experience' => $c->experience_type ?? '—',
                    'Skills'     => $c->skills ?? '—',
                    'Contact'    => $c->mobile_number ?: $c->email,
                ])->toArray(),
            ]
        );
    }
}
