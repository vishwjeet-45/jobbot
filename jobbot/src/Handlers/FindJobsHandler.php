<?php

namespace Ddt\JobBot\Handlers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class FindJobsHandler extends BaseHandler
{
    public function handle(string $lower, string $original, mixed $user): JsonResponse
    {
        $skill = $this->extractSkillName($lower);

        if (!$skill) {
            session(['pending_intent' => 'find_jobs', 'pending_step' => 1]);
            return $this->respond('💼 Kis skill ke liye jobs dhunne hain? (e.g. Laravel, React, Python)', null);
        }

        return $this->searchBySkill($skill);
    }

    public function searchBySkill(string $skill): JsonResponse
    {
        $skill = strtolower(trim($skill));
        $limit = config('jobbot.search_limit', 20);

        $jobs = DB::table('jobs as j')
            ->join('employers as e', 'e.id', '=', 'j.employer_id')
            ->leftJoin('skillables as sa', function ($join) {
                $join->on('sa.skillable_id', '=', 'j.id')
                    ->where('sa.skillable_type', '=', 'App\\Models\\Job');
            })
            ->leftJoin('skills as sk', 'sk.id', '=', 'sa.skill_id')
            ->select(
                'j.id', 'j.title', 'j.location', 'j.job_type',
                'j.experience', 'j.salary_min', 'j.salary_max',
                'e.company_name',
                DB::raw("GROUP_CONCAT(DISTINCT sk.name ORDER BY sk.name SEPARATOR ', ') as required_skills")
            )
            ->where('j.status', 1)
            ->where(function ($q) use ($skill) {
                $q->whereRaw('LOWER(sk.name) LIKE ?', ["%{$skill}%"])
                  ->orWhereRaw('LOWER(j.title) LIKE ?', ["%{$skill}%"])
                  ->orWhereRaw('LOWER(j.description) LIKE ?', ["%{$skill}%"]);
            })
            ->groupBy('j.id', 'j.title', 'j.location', 'j.job_type', 'j.experience', 'j.salary_min', 'j.salary_max', 'e.company_name')
            ->orderByDesc('j.created_at')
            ->limit($limit)
            ->get();

        if ($jobs->isEmpty()) {
            return $this->respond("😔 \"<strong>{$skill}</strong>\" ke liye koi job nahi mili.", null);
        }

        return $this->respond(
            "💼 \"<strong>{$skill}</strong>\" ke liye <strong>{$jobs->count()}</strong> jobs mili:",
            [
                'columns' => ['Title', 'Company', 'Location', 'Type', 'Experience', 'Salary', 'Skills Required'],
                'rows'    => $jobs->map(fn($j) => [
                    'Title'           => $j->title,
                    'Company'         => $j->company_name,
                    'Location'        => $j->location ?? '—',
                    'Type'            => $j->job_type ?? '—',
                    'Experience'      => $j->experience ?? '—',
                    'Salary'          => ($j->salary_min && $j->salary_max)
                                            ? "₹{$j->salary_min} - ₹{$j->salary_max}"
                                            : '—',
                    'Skills Required' => $j->required_skills ?? '—',
                ])->toArray(),
            ]
        );
    }
}
