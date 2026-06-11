<?php

namespace Ddt\JobBot\Handlers;

use Illuminate\Http\JsonResponse;

// ─────────────────────────────────────────────
// Resume Upload Prompt Handler
// ─────────────────────────────────────────────
class ParseResumeHandler extends BaseHandler
{
    public function handle(string $lower, string $original, mixed $user): JsonResponse
    {
        return $this->respond(
            '📎 Resume upload karein — main aapka naam, phone, email aur skills extract kar dunga!',
            ['type' => 'resume_upload_prompt']
        );
    }
}

// ─────────────────────────────────────────────
// Add Candidate (starts multi-step flow)
// ─────────────────────────────────────────────
class AddCandidateHandler extends BaseHandler
{
    public function handle(string $lower, string $original, mixed $user): JsonResponse
    {
        session([
            'pending_intent'  => 'add_candidate',
            'pending_step'    => 1,
            'candidate_draft' => [],
        ]);

        return $this->respond(
            '➕ Candidate add karte hain! Sabse pehle candidate ka <strong>pura naam</strong> batayein:',
            null
        );
    }
}

// ─────────────────────────────────────────────
// My Profile Handler
// ─────────────────────────────────────────────
class MyProfileHandler extends BaseHandler
{
    public function handle(string $lower, string $original, mixed $user): JsonResponse
    {
        if (!$user) {
            return $this->respond('⚠️ Aap logged in nahi hain.', null);
        }

        // Dynamically resolve Candidate model (project me define hoga)
        $candidateModel = config('jobbot.models.candidate', 'App\\Models\\Candidate');
        $candidate      = null;

        if (class_exists($candidateModel)) {
            $candidate = $candidateModel::where('user_id', $user->id)->first();
        }

        return $this->respond(
            '👤 Aapki profile details:',
            [
                'type'    => 'profile',
                'columns' => ['Field', 'Value'],
                'rows'    => [
                    ['Field' => 'Name',         'Value' => $user->name],
                    ['Field' => 'Email',         'Value' => $user->email],
                    ['Field' => 'Mobile',        'Value' => $user->mobile_number ?? '—'],
                    ['Field' => 'Position',      'Value' => $user->position ?? '—'],
                    ['Field' => 'Experience',    'Value' => $user->experience_type ?? '—'],
                    ['Field' => 'Availability',  'Value' => $candidate?->availability ?? '—'],
                    ['Field' => 'Location',      'Value' => $candidate?->hometown ?? '—'],
                ],
            ]
        );
    }
}

// ─────────────────────────────────────────────
// Help Handler
// ─────────────────────────────────────────────
class HelpHandler extends BaseHandler
{
    public function handle(string $lower, string $original, mixed $user): JsonResponse
    {
        $botName = config('jobbot.ui.bot_name', 'Job Bot');

        return $this->respond(
            "🤖 Main ye cheezein kar sakta hoon:<br><br>" .
            "🔍 <strong>Candidate Search</strong><br>" .
            "• \"Laravel developer candidates dhundo\"<br>" .
            "• \"React skill wale candidates\"<br><br>" .
            "💼 <strong>Job Search</strong><br>" .
            "• \"PHP ke liye jobs\"<br>" .
            "• \"Python developer jobs available\"<br><br>" .
            "📄 <strong>Resume Parse</strong><br>" .
            "• \"Resume upload\" — naam, phone, email & skills extract<br><br>" .
            "➕ <strong>Candidate Add</strong><br>" .
            "• \"Add candidate\" — step-by-step form<br><br>" .
            "👤 <strong>My Profile</strong><br>" .
            "• \"My profile\" — apni details dekho<br><br>" .
            "🌐 <strong>AI Fallback</strong> — kuch bhi poochho, AI jawab dega!",
            null
        );
    }
}

// ─────────────────────────────────────────────
// Save Resume Draft Handler
// ─────────────────────────────────────────────
class SaveResumeHandler extends BaseHandler
{
    public function handle(string $lower, string $original, mixed $user): JsonResponse
    {
        $draft = session('resume_draft');

        if (!$draft) {
            return $this->respond('⚠️ Koi resume draft nahi mila. Pehle resume upload karein.', null);
        }

        $name   = trim($draft['name']  ?? '');
        $email  = trim($draft['email'] ?? '');
        $phone  = preg_replace('/\D/', '', $draft['phone'] ?? '');
        $skills = $draft['skills'] ?? [];

        if (!$name || !$email) {
            return $this->respond('⚠️ Name ya email missing hai resume data me. Resume dobara upload karein.', null);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->respond("⚠️ Email valid nahi hai: <strong>{$email}</strong>.", null);
        }

        $userModel = config('jobbot.models.user', 'App\\Models\\User');

        if ($userModel::where('email', $email)->exists()) {
            return $this->respond("⚠️ Is email se pehle se account exist karta hai: <strong>{$email}</strong>", null);
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $nameParts = explode(' ', $name, 2);

            $newUser = $userModel::create([
                'name'          => $name,
                'first_name'    => $nameParts[0],
                'last_name'     => $nameParts[1] ?? '',
                'email'         => $email,
                'mobile_number' => $phone ?: null,
                'password'      => bcrypt($name . '@123'),
            ]);

            // Assign role if Spatie is available
            if (method_exists($newUser, 'assignRole')) {
                $candidateRole = config('jobbot.candidate_role', 'Candidate');
                $newUser->assignRole($candidateRole);
            }

            $candidateModel = config('jobbot.models.candidate', 'App\\Models\\Candidate');
            if (class_exists($candidateModel)) {
                $candidateModel::create(['user_id' => $newUser->id]);
            }

            // Attach skills
            if (!empty($skills)) {
                $skillModel = config('jobbot.models.skill', 'App\\Models\\Skill');

                foreach ($skills as $skillName) {
                    $skillName = trim($skillName);
                    if (!$skillName) continue;

                    if (class_exists($skillModel)) {
                        $skill = $skillModel::firstOrCreate(['name' => $skillName]);
                        \Illuminate\Support\Facades\DB::table('skillables')->insertOrIgnore([
                            'skill_id'       => $skill->id,
                            'skillable_id'   => $newUser->id,
                            'skillable_type' => $userModel,
                        ]);
                    }
                }
            }

            \Illuminate\Support\Facades\DB::commit();
            session()->forget('resume_draft');

            return $this->respond(
                "✅ Candidate successfully add ho gaya!<br><br>" .
                "👤 Name: <strong>{$newUser->name}</strong><br>" .
                "📧 Email: <strong>{$newUser->email}</strong><br>" .
                "📱 Mobile: <strong>" . ($newUser->mobile_number ?: '—') . "</strong><br>" .
                "🏷️ Skills: <strong>" . (empty($skills) ? '—' : implode(', ', $skills)) . "</strong><br>" .
                "🆔 User ID: <strong>{$newUser->id}</strong>",
                null
            );

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('JobBot: Resume draft save failed', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
            ]);

            return $this->respond('❌ Candidate save karte waqt error aaya. Logs check karein.', null);
        }
    }
}
