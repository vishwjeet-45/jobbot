# 🤖 JobBot — Laravel AI Chatbot Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ddt/jobbot.svg?style=flat-square)](https://packagist.org/packages/ddt/jobbot)
[![License](https://img.shields.io/github/license/vishwjeet-45/jobbot?style=flat-square)](LICENSE)

A plug-and-play AI-powered Job Bot chatbot for Laravel applications.  
Candidates dhundein, jobs search karein, resume parse karein — sab kuch ek chatbot se!

![JobBot Preview](https://raw.githubusercontent.com/vishwjeet-45/jobbot/main/preview.png)

---

## ✨ Features

- 🔍 **Candidate Search** — Skill se candidates dhundo (DB-powered)
- 💼 **Job Search** — Skill/title se jobs dhundo
- 📄 **Resume Parser** — PDF/DOCX upload → AI se name, email, phone, skills extract
- ➕ **Add Candidate** — Step-by-step multi-turn form via chat
- 👤 **My Profile** — Logged-in user ki details
- 🧠 **AI Fallback** — OpenRouter API se general questions ka jawab
- 🎙️ **Voice Input** — Mic se query bolein
- 🔧 **Fully Customizable** — Intents JSON, custom handlers, config sab publishable

---

## 📦 Requirements

| Requirement | Version |
|---|---|
| PHP | ^8.1 |
| Laravel | ^10.0 or ^11.0 |
| pdftotext (poppler-utils) | For PDF parsing |
| antiword | For DOC parsing |

---

## 🚀 Installation

### Step 1 — Composer se install karo

```bash
composer require ddt/jobbot
```

### Step 2 — Install command chalao

```bash
php artisan jobbot:install
```

Yeh command automatically karega:
- ✅ `config/jobbot.php` publish
- ✅ `resources/views/vendor/jobbot/` publish  
- ✅ `jobbot/intents.json` create (customize karne ke liye)
- ✅ `.env` me required keys add

### Step 3 — OpenRouter API key set karo

```env
OPENROUTER_KEY=your_openrouter_api_key_here
JOBBOT_NAME=Job Sphere Assistant
JOBBOT_INITIALS=JS
```

> OpenRouter API key: https://openrouter.ai/keys

### Step 4 — Blade layout me component add karo

```blade
{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    {{-- your content --}}

    @include('jobbot::components.job-bot')
</body>
</html>
```

**That's it! 🎉** Chatbot bottom-right corner me appear ho jayega.

---

## ⚙️ Configuration

`config/jobbot.php`:

```php
return [
    'openrouter_key'   => env('OPENROUTER_KEY'),
    'openrouter_model' => env('JOBBOT_AI_MODEL', 'nex-agi/nex-n2-pro:free'),
    'route_prefix'     => 'jobbot',           // Routes: /jobbot/query, /jobbot/resume
    'middleware'       => ['web', 'auth'],     // Change as needed
    'intents_path'     => null,               // null = package default, ya custom path
    'candidate_role'   => 'Candidate',        // Spatie role name
    'search_limit'     => 20,
    'cache_ttl'        => 5,                  // AI response cache (minutes)

    // Model class overrides
    'models' => [
        'user'      => \App\Models\User::class,
        'candidate' => \App\Models\Candidate::class,
        'skill'     => \App\Models\Skill::class,
    ],

    // UI Customization
    'ui' => [
        'bot_name'    => env('JOBBOT_NAME', 'Job Sphere Assistant'),
        'bot_initials'=> env('JOBBOT_INITIALS', 'JS'),
        'greeting'    => 'Namaste! 👋 Main <strong>Job Sphere Assistant</strong> hoon.',
        'quick_chips' => [
            ['label' => '🔍 Laravel Candidates', 'query' => 'Laravel developer candidates dhundo'],
            ['label' => '💼 React Jobs',          'query' => 'React ke liye jobs'],
            ['label' => '📄 Parse Resume',         'query' => 'Resume upload'],
            ['label' => '➕ Add Candidate',        'query' => 'Add candidate'],
            ['label' => '❓ Help',                 'query' => 'help'],
        ],
    ],
];
```

---

## 🧠 Intents Customize Karna

Install ke baad `jobbot/intents.json` project root me create hota hai:

```json
[
    {
        "key": "find_candidates",
        "patterns": [
            "find candidate",
            "candidate dhundo",
            "developer chahiye"
        ],
        "handler": "find_candidates"
    },
    {
        "key": "my_custom_intent",
        "patterns": [
            "salary check",
            "salary dikhao"
        ],
        "handler": "salary_check"
    }
]
```

Phir `config/jobbot.php` me path set karo:

```php
'intents_path' => base_path('jobbot/intents.json'),
```

---

## 🔧 Custom Handler Banana

Kisi bhi intent ka handler apni class se replace kar sakte hain:

### Step 1 — Handler class banao

```php
<?php
// app/JobBot/SalaryCheckHandler.php

namespace App\JobBot;

use Ddt\JobBot\Contracts\JobBotHandlerInterface;
use Illuminate\Http\JsonResponse;

class SalaryCheckHandler implements JobBotHandlerInterface
{
    public function handle(string $lower, string $original, mixed $user): JsonResponse
    {
        // apna logic yahan
        return response()->json([
            'message' => '💰 Salary data yahan aayega!',
            'data'    => null,
        ]);
    }
}
```

### Step 2 — Config me register karo

```php
// config/jobbot.php
'handlers' => [
    'salary_check'   => \App\JobBot\SalaryCheckHandler::class,
    'find_candidates'=> \App\JobBot\MyFindCandidatesHandler::class, // built-in override
],
```

### Available Built-in Handler Keys

| Key | Description |
|---|---|
| `find_candidates` | Skill se candidates search |
| `find_jobs` | Skill se jobs search |
| `parse_resume` | Resume upload prompt |
| `add_candidate` | Multi-step candidate add form |
| `my_profile` | Logged-in user profile |
| `help` | Help message |
| `save_resume` | Resume draft se candidate save |

---

## 📂 Database Requirements

Package in tables expect karta hai (aapke project me already honi chahiye):

```
users           — id, name, first_name, last_name, email, mobile_number, position, experience_type
candidates      — id, user_id, availability, hometown
jobs            — id, employer_id, title, location, job_type, experience, salary_min, salary_max, status, description
employers       — id, company_name
skills          — id, name
skillables      — skill_id, skillable_id, skillable_type  (polymorphic pivot)
```

---

## 🖥️ Server Requirements (Resume Parsing)

PDF aur DOC parsing ke liye server pe install karo:

```bash
# Ubuntu/Debian
sudo apt-get install poppler-utils antiword

# CentOS/RHEL
sudo yum install poppler-utils antiword
```

---

## 🎨 Views Publish Karna (UI Customize karne ke liye)

```bash
php artisan vendor:publish --tag=jobbot-views
```

Files yahan publish hongi:  
`resources/views/vendor/jobbot/components/job-bot.blade.php`

---

## 📋 All Publish Commands

```bash
# Sab kuch ek saath
php artisan vendor:publish --tag=jobbot

# Sirf config
php artisan vendor:publish --tag=jobbot-config

# Sirf views
php artisan vendor:publish --tag=jobbot-views

# Sirf intents JSON
php artisan vendor:publish --tag=jobbot-intents
```

---

## 🛣️ Routes

Package in routes register karta hai automatically:

| Method | URL | Name | Description |
|---|---|---|---|
| POST | `/jobbot/query` | `jobbot.query` | Chat query |
| POST | `/jobbot/resume` | `jobbot.resume` | Resume upload |

Route prefix change karna ho:

```php
// config/jobbot.php
'route_prefix' => 'chatbot',  // Ab /chatbot/query hoga
```

---

## 💬 Supported Commands (Default)

| User Input | Action |
|---|---|
| `Laravel candidates dhundo` | Laravel skill se candidates search |
| `React ke liye jobs` | React jobs search |
| `Resume upload` | Resume upload prompt |
| `Add candidate` | Step-by-step candidate add |
| `My profile` | Profile details |
| `Help` | All commands list |
| `Save karo` | Resume draft se candidate save |
| Kuch bhi aur... | AI fallback (OpenRouter) |

---

## 🔄 Upgrade

```bash
composer update ddt/jobbot
php artisan vendor:publish --tag=jobbot-config --force  # naya config merge karna ho
```

---

## 📝 Changelog

### v1.0.0
- Initial release
- Candidate & job search
- Resume parsing with AI extraction
- Multi-step candidate add flow
- Voice input support
- Fully customizable intents & handlers

---

## 🤝 Contributing

1. Fork karo
2. Feature branch banao: `git checkout -b feature/my-feature`
3. Commit karo: `git commit -m "Add my feature"`
4. Push karo: `git push origin feature/my-feature`
5. Pull Request open karo

---

## 📄 License

MIT License. Dekho [LICENSE](LICENSE) file.

---

## 👨‍💻 Author

**DDT** — [github.com/vishwjeet-45](https://github.com/vishwjeet-45)

---

> 💡 **Tip:** Koi issue aaye toh [GitHub Issues](https://github.com/vishwjeet-45/jobbot/issues) pe report karo.