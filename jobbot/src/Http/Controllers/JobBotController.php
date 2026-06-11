<?php

namespace Ddt\JobBot\Http\Controllers;

use Ddt\JobBot\Handlers\FindCandidatesHandler;
use Ddt\JobBot\Handlers\FindJobsHandler;
use Ddt\JobBot\Services\IntentLoader;
use Ddt\JobBot\Services\ResumeParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JobBotController extends Controller
{
    public function __construct(
        private readonly IntentLoader       $intentLoader,
        private readonly ResumeParserService $resumeParser,
    ) {}

    // ─────────────────────────────────────────────
    // MAIN QUERY ENDPOINT
    // ─────────────────────────────────────────────
    public function query(Request $request): JsonResponse
    {
        $request->validate(['query' => 'required|string|max:500']);

        $original = trim($request->input('query'));
        $lower    = Str::lower($original);
        $user     = auth()->user();

        // ── Pending multi-step flow ──
        if (session()->has('pending_intent')) {
            return $this->continueFlow(
                session('pending_intent'),
                session('pending_step', 1),
                $original,
                $lower,
                $user
            );
        }

        // ── Intent detection ──
        $intent = $this->intentLoader->detect($lower);

        if ($intent) {
            $handler = $this->intentLoader->resolveHandler($intent['handler']);

            if ($handler) {
                return $handler->handle($lower, $original, $user);
            }
        }

        // ── AI Fallback ──
        return $this->handleOpenRouter($original);
    }

    // ─────────────────────────────────────────────
    // RESUME UPLOAD ENDPOINT
    // ─────────────────────────────────────────────
    public function uploadResume(Request $request): JsonResponse
    {
        $maxSize  = config('jobbot.resume.max_size_kb', 5120);
        $mimes    = implode(',', config('jobbot.resume.allowed_mime', ['pdf', 'doc', 'docx']));

        $request->validate([
            'resume' => "required|file|mimes:{$mimes}|max:{$maxSize}",
        ]);

        $parsed = $this->resumeParser->parse($request->file('resume'));

        if (!$parsed) {
            return $this->respond('⚠️ Resume ka text extract nahi ho saka. PDF ya DOCX try karein.', null);
        }

        $extracted = config('jobbot.resume.ai_enabled', true)
            ? ($this->extractWithAI($parsed['raw_text']) ?? $parsed)
            : $parsed;

        if (!$extracted || (!($extracted['name'] ?? null) && !($extracted['email'] ?? null))) {
            return $this->respond(
                '🤔 Resume se data extract nahi ho saka. Manually dein ya alag format try karein.',
                null
            );
        }

        session(['resume_draft' => $extracted]);

        return $this->respond(
            '📄 Resume successfully parsed! Yeh details mili:',
            [
                'type'      => 'resume_result',
                'extracted' => $extracted,
                'skills'    => $extracted['skills'] ?? [],
            ]
        );
    }

    // ─────────────────────────────────────────────
    // MULTI-STEP FLOW
    // ─────────────────────────────────────────────
    private function continueFlow(string $intentKey, int $step, string $original, string $lower, $user): JsonResponse
    {
        if ($intentKey === 'find_candidates') {
            session()->forget(['pending_intent', 'pending_step']);
            return app(FindCandidatesHandler::class)->searchBySkill(trim($original));
        }

        if ($intentKey === 'find_jobs') {
            session()->forget(['pending_intent', 'pending_step']);
            return app(FindJobsHandler::class)->searchBySkill(trim($original));
        }

        if ($intentKey === 'add_candidate') {
            return $this->handleAddCandidateFlow($step, $original, $lower);
        }

        session()->forget(['pending_intent', 'pending_step']);
        return $this->handleOpenRouter($original);
    }

    private function handleAddCandidateFlow(int $step, string $original, string $lower): JsonResponse
    {
        $draft = session('candidate_draft', []);

        switch ($step) {
            case 1:
                $draft['name'] = trim($original);
                session(['candidate_draft' => $draft, 'pending_step' => 2]);
                return $this->respond("✅ Naam: <strong>{$draft['name']}</strong><br><br>Ab candidate ka <strong>email</strong> batayein:", null);

            case 2:
                if (!filter_var(trim($original), FILTER_VALIDATE_EMAIL)) {
                    return $this->respond('⚠️ Valid email enter karein (e.g. john@gmail.com):', null);
                }
                $draft['email'] = trim($original);
                session(['candidate_draft' => $draft, 'pending_step' => 3]);
                return $this->respond("✅ Email: <strong>{$draft['email']}</strong><br><br>Ab <strong>mobile number</strong> batayein:", null);

            case 3:
                $mobile = preg_replace('/\D/', '', trim($original));
                if (strlen($mobile) < 10) {
                    return $this->respond('⚠️ Valid 10-digit mobile number enter karein:', null);
                }
                $draft['mobile'] = $mobile;
                session(['candidate_draft' => $draft, 'pending_step' => 4]);
                return $this->respond(
                    "✅ Almost done! Yeh details save karein?<br><br>" .
                    "👤 Name: <strong>{$draft['name']}</strong><br>" .
                    "📧 Email: <strong>{$draft['email']}</strong><br>" .
                    "📱 Mobile: <strong>{$draft['mobile']}</strong><br><br>" .
                    "\"<em>yes</em>\" ya \"<em>haan</em>\" type karein confirm karne ke liye.",
                    null
                );

            case 4:
                if (in_array($lower, ['yes', 'haan', 'ha', 'y', 'ok', 'confirm', 'save'])) {
                    return $this->createCandidateFromDraft($draft);
                }
                session()->forget(['pending_intent', 'pending_step', 'candidate_draft']);
                return $this->respond('❌ Candidate add karna cancel kar diya.', null);
        }

        session()->forget(['pending_intent', 'pending_step', 'candidate_draft']);
        return $this->respond('Kuch galat ho gaya. Dobara try karein.', null);
    }

    private function createCandidateFromDraft(array $draft): JsonResponse
    {
        session()->forget(['pending_intent', 'pending_step', 'candidate_draft']);

        $userModel      = config('jobbot.models.user',      'App\\Models\\User');
        $candidateModel = config('jobbot.models.candidate', 'App\\Models\\Candidate');

        if ($userModel::where('email', $draft['email'])->exists()) {
            return $this->respond("⚠️ Is email se pehle se account exist karta hai: <strong>{$draft['email']}</strong>", null);
        }

        $nameParts = explode(' ', $draft['name'], 2);

        $user = $userModel::create([
            'name'          => $draft['name'],
            'first_name'    => $nameParts[0],
            'last_name'     => $nameParts[1] ?? '',
            'email'         => $draft['email'],
            'mobile_number' => $draft['mobile'],
            'status'        => 1,
            'password'      => bcrypt(Str::random(12)),
        ]);

        if (method_exists($user, 'assignRole')) {
            $user->assignRole(config('jobbot.candidate_role', 'Candidate'));
        }

        if (class_exists($candidateModel)) {
            $candidateModel::create(['user_id' => $user->id]);
        }

        return $this->respond(
            "✅ Candidate successfully add ho gaya!<br><br>" .
            "👤 Name: <strong>{$user->name}</strong><br>" .
            "📧 Email: <strong>{$user->email}</strong><br>" .
            "🆔 User ID: <strong>{$user->id}</strong>",
            null
        );
    }

    // ─────────────────────────────────────────────
    // AI RESUME EXTRACTION
    // ─────────────────────────────────────────────
    private function extractWithAI(string $text): ?array
    {
        $apiKey = config('jobbot.openrouter_key');
        $model  = config('jobbot.openrouter_model', 'nex-agi/nex-n2-pro:free');

        if (!$apiKey) return null;

        try {
            $response = Http::timeout(30)
                ->withHeaders(['Authorization' => "Bearer {$apiKey}", 'Content-Type' => 'application/json'])
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model'       => $model,
                    'temperature' => 0,
                    'max_tokens'  => 1000,
                    'messages'    => [
                        ['role' => 'system', 'content' => 'You are a resume parser. Return ONLY valid JSON. No markdown. No explanation.'],
                        ['role' => 'user',   'content' =>
                            "Extract from resume:\n\n" .
                            '{"name":"","email":"","phone":"","total_experience_years":"","skills":[""]}'."\n\n" .
                            "Resume:\n" . Str::limit($text, 5000)
                        ],
                    ],
                ]);

            if (!$response->successful()) return null;

            $content = data_get($response->json(), 'choices.0.message.content', '');
            $clean   = trim(preg_replace('/```json|```/i', '', $content));

            preg_match('/\{.*\}/s', $clean, $matches);
            if (!isset($matches[0])) return null;

            $data = json_decode($matches[0], true);
            if (json_last_error() !== JSON_ERROR_NONE) return null;

            return [
                'name'                   => $data['name']                   ?? null,
                'email'                  => $data['email']                  ?? null,
                'phone'                  => $data['phone']                  ?? null,
                'total_experience_years' => $data['total_experience_years'] ?? null,
                'skills'                 => is_array($data['skills'] ?? null) ? $data['skills'] : [],
            ];

        } catch (\Throwable $e) {
            Log::error('JobBot: AI extraction failed', ['message' => $e->getMessage()]);
            return null;
        }
    }

    // ─────────────────────────────────────────────
    // AI FALLBACK (OpenRouter)
    // ─────────────────────────────────────────────
    private function handleOpenRouter(string $query): JsonResponse
    {
        $apiKey = config('jobbot.openrouter_key');

        if (!$apiKey) {
            return $this->respond(
                "🤔 \"<em>{$query}</em>\" samajh nahi aaya.<br>\"<em>help</em>\" type karein available commands ke liye.",
                null
            );
        }

        $cacheKey = 'jobbot_or_' . md5(Str::lower(trim($query)));
        $ttl      = config('jobbot.cache_ttl', 5);

        $reply = Cache::remember($cacheKey, now()->addMinutes($ttl), function () use ($query, $apiKey) {
            $botName = config('jobbot.ui.bot_name', 'Job Bot');
            $date    = now()->format('d M Y, l');

            $response = Http::timeout(15)
                ->withHeaders(['Authorization' => "Bearer {$apiKey}", 'Content-Type' => 'application/json'])
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model'       => config('jobbot.openrouter_model', 'nex-agi/nex-n2-pro:free'),
                    'temperature' => 0.7,
                    'max_tokens'  => 400,
                    'messages'    => [[
                        'role'    => 'user',
                        'content' => "Tum \"{$botName}\" ho — ek job portal ka AI helper.\nAaj ki date: {$date} IST.\n\nUser: \"{$query}\"\n\nHinglish me jawab do, max 5 lines, friendly tone.",
                    ]],
                ]);

            return $response->ok() ? $response->json('choices.0.message.content') : null;
        });

        if (!$reply) {
            return $this->respond('⚠️ AI se response nahi mila. Thodi der baad dobara try karein.', null);
        }

        return $this->respond($this->mdToHtml($reply), null);
    }

    // ─────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────
    private function mdToHtml(string $text): string
    {
        $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $text);
        $text = preg_replace('/^- (.+)$/m', '• $1', $text);
        return nl2br(trim($text));
    }

    private function respond(string $message, ?array $data): JsonResponse
    {
        return response()->json(['message' => $message, 'data' => $data]);
    }
}
