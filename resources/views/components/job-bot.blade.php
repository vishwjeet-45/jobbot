{{--
    JobBot Component
    Usage: @include('jobbot::components.job-bot')
    Or publish: php artisan vendor:publish --tag=jobbot-views
--}}

@php
    $cfg      = config('jobbot.ui', []);
    $botName  = $cfg['bot_name']    ?? 'Job Bot';
    $initials = $cfg['bot_initials'] ?? 'JB';
    $greeting = $cfg['greeting']    ?? "Namaste! 👋 Main <strong>{$botName}</strong> hoon.";
    $chips    = $cfg['quick_chips'] ?? [];
@endphp

<style>
    :root {
        --jb-primary:    #5b21b6;
        --jb-primary-lt: #7c3aed;
        --jb-primary-bg: #1e0a3c;
        --jb-surface:    #150626;
        --jb-border:     rgba(139,92,246,.25);
        --jb-text:       #f3eeff;
        --jb-muted:      #a78bfa;
        --jb-green:      #34d399;
        --jb-red:        #f87171;
        --jb-radius:     18px;
    }
    /* ── Trigger ── */
    #jb-trigger{position:fixed;bottom:1.5rem;right:1.5rem;z-index:1060;width:56px;height:56px;border-radius:50%;background:var(--jb-primary-lt);border:none;box-shadow:0 8px 24px rgba(91,33,182,.45);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:transform .2s,background .2s}
    #jb-trigger:hover{background:var(--jb-primary);transform:scale(1.08)}
    #jb-trigger.open{transform:scale(.9)}
    #jb-badge{position:absolute;top:-4px;right:-4px;background:var(--jb-red);color:#fff;font-size:10px;font-weight:700;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;animation:pulse 1.4s infinite;display:none}
    @keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.7;transform:scale(1.15)}}
    /* ── Window ── */
    #jb-window{position:fixed;bottom:5.5rem;right:1.5rem;z-index:1055;width:400px;max-height:620px;border-radius:var(--jb-radius);background:var(--jb-surface);border:1px solid var(--jb-border);box-shadow:0 20px 60px rgba(0,0,0,.6);display:flex;flex-direction:column;overflow:hidden;transform-origin:bottom right;transition:opacity .25s,transform .25s;opacity:0;transform:translateY(16px) scale(.96);pointer-events:none}
    #jb-window.show{opacity:1;transform:translateY(0) scale(1);pointer-events:all}
    /* ── Header ── */
    .jb-header{background:linear-gradient(135deg,#3b0764,var(--jb-primary-lt));padding:.8rem 1rem;display:flex;align-items:center;gap:.75rem}
    .jb-avatar{width:36px;height:36px;border-radius:50%;background:#c4b5fd;color:#3b0764;font-weight:700;font-size:.75rem;display:flex;align-items:center;justify-content:center;position:relative;flex-shrink:0}
    .jb-dot{position:absolute;bottom:1px;right:1px;width:9px;height:9px;border-radius:50%;background:var(--jb-green);border:2px solid var(--jb-primary)}
    .jb-header-info{flex:1}
    .jb-header-title{color:#fff;font-size:.875rem;font-weight:600;margin:0}
    .jb-header-sub{color:#c4b5fd;font-size:.7rem;display:flex;align-items:center;gap:4px;margin:0}
    .jb-online-dot{width:6px;height:6px;border-radius:50%;background:var(--jb-green);display:inline-block}
    .jb-header-btn{background:transparent;border:none;color:#c4b5fd;width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background .15s,color .15s}
    .jb-header-btn:hover{background:rgba(255,255,255,.1);color:#fff}
    /* ── Chips ── */
    #jb-suggestions{padding:.6rem .75rem;display:flex;flex-wrap:wrap;gap:.4rem}
    .jb-chip{font-size:.7rem;padding:.28rem .75rem;border-radius:999px;border:1px solid rgba(139,92,246,.45);color:var(--jb-muted);background:transparent;cursor:pointer;transition:all .15s}
    .jb-chip:hover{background:rgba(124,58,237,.3);color:#fff;border-color:var(--jb-primary-lt)}
    /* ── Messages ── */
    #jb-messages{flex:1;overflow-y:auto;padding:.75rem;display:flex;flex-direction:column;gap:.65rem;max-height:360px;scrollbar-width:thin;scrollbar-color:var(--jb-primary) transparent}
    #jb-messages::-webkit-scrollbar{width:4px}
    #jb-messages::-webkit-scrollbar-thumb{background:var(--jb-primary);border-radius:4px}
    .jb-row{display:flex;flex-direction:column}
    .jb-row.bot{align-items:flex-start}
    .jb-row.user{align-items:flex-end}
    .jb-bubble-wrap{display:flex;align-items:flex-end;gap:.5rem;max-width:88%}
    .jb-mini-avatar{width:24px;height:24px;border-radius:50%;background:var(--jb-primary);color:#fff;font-size:.6rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-bottom:4px}
    .jb-bubble{padding:.55rem .85rem;border-radius:16px;font-size:.82rem;color:var(--jb-text);line-height:1.5}
    .jb-bubble.bot{background:rgba(109,40,217,.28);border:1px solid var(--jb-border);border-bottom-left-radius:4px}
    .jb-bubble.user{background:linear-gradient(135deg,var(--jb-primary-lt),var(--jb-primary));border-bottom-right-radius:4px}
    .jb-time{font-size:.65rem;color:rgba(167,139,250,.55);margin-top:3px;padding:0 4px}
    /* ── Table ── */
    .jb-table-wrap{overflow-x:auto;margin-top:.5rem}
    .jb-table{width:100%;border-collapse:collapse;font-size:.72rem}
    .jb-table th{padding:.4rem .5rem;text-align:left;color:var(--jb-muted);font-weight:600;border-bottom:1px solid rgba(139,92,246,.3);white-space:nowrap}
    .jb-table td{padding:.35rem .5rem;color:#ddd6fe;border-bottom:1px solid rgba(109,40,217,.15)}
    .jb-table tr:last-child td{border-bottom:none}
    .jb-table tr:hover td{background:rgba(109,40,217,.15)}
    /* ── Resume card ── */
    .jb-resume-card{background:rgba(91,33,182,.18);border:1px solid var(--jb-border);border-radius:10px;padding:.65rem .85rem;margin-top:.5rem;font-size:.78rem}
    .jb-resume-card .field{color:var(--jb-muted);font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;margin-bottom:1px}
    .jb-resume-card .value{color:var(--jb-text);font-weight:500;margin-bottom:.5rem}
    .jb-skill-tag{display:inline-block;font-size:.65rem;padding:.15rem .55rem;border-radius:999px;background:rgba(139,92,246,.25);border:1px solid rgba(139,92,246,.4);color:#c4b5fd;margin:.15rem .1rem}
    /* ── Upload zone ── */
    .jb-upload-zone{border:2px dashed rgba(139,92,246,.4);border-radius:10px;text-align:center;padding:.9rem;margin-top:.5rem;cursor:pointer;transition:background .2s;color:var(--jb-muted);font-size:.78rem}
    .jb-upload-zone:hover{background:rgba(109,40,217,.15)}
    .jb-upload-zone input{display:none}
    /* ── Typing ── */
    .jb-typing{display:flex;align-items:center;gap:.45rem;padding:.55rem .85rem;background:rgba(109,40,217,.28);border:1px solid var(--jb-border);border-radius:16px;border-bottom-left-radius:4px;width:fit-content}
    .jb-dot-bounce{width:6px;height:6px;border-radius:50%;background:var(--jb-muted);animation:bounce .9s infinite}
    .jb-dot-bounce:nth-child(2){animation-delay:.15s}
    .jb-dot-bounce:nth-child(3){animation-delay:.3s}
    @keyframes bounce{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-5px)}}
    /* ── Input ── */
    .jb-input-area{padding:.65rem .75rem;border-top:1px solid var(--jb-border);background:rgba(10,0,20,.8)}
    .jb-input-row{display:flex;align-items:center;gap:.5rem;background:rgba(109,40,217,.14);border:1px solid var(--jb-border);border-radius:12px;padding:.45rem .75rem}
    .jb-input{flex:1;background:transparent;border:none;outline:none;color:var(--jb-text);font-size:.82rem}
    .jb-input::placeholder{color:rgba(167,139,250,.5)}
    .jb-send{width:32px;height:32px;border-radius:8px;border:none;background:var(--jb-primary-lt);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;transition:background .15s,opacity .15s}
    .jb-send:disabled{opacity:.4;cursor:default}
    .jb-send:not(:disabled):hover{background:var(--jb-primary)}
    .jb-footer-text{text-align:center;font-size:.62rem;color:rgba(139,92,246,.45);margin-top:.4rem}
    /* ── Mic ── */
    .jb-mic{width:32px;height:32px;border-radius:8px;border:none;background:transparent;color:rgba(167,139,250,.6);display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;transition:color .15s,background .15s}
    .jb-mic:hover{color:var(--jb-muted);background:rgba(109,40,217,.2)}
    .jb-mic.recording{color:var(--jb-red);background:rgba(248,113,113,.15);animation:micPulse .8s infinite}
    @keyframes micPulse{0%,100%{box-shadow:0 0 0 0 rgba(248,113,113,.4)}50%{box-shadow:0 0 0 6px rgba(248,113,113,0)}}
    /* ── Confirm bar ── */
    .jb-confirm-bar{display:none;padding:.45rem .75rem;background:rgba(52,211,153,.08);border-top:1px solid rgba(52,211,153,.2);font-size:.75rem;color:#34d399;align-items:center;gap:.5rem}
    .jb-confirm-bar.show{display:flex}
    .jb-confirm-btn{padding:.2rem .65rem;border-radius:6px;border:1px solid;font-size:.72rem;cursor:pointer;font-weight:600;transition:all .15s}
    .jb-confirm-yes{border-color:#34d399;color:#34d399;background:rgba(52,211,153,.1)}
    .jb-confirm-yes:hover{background:rgba(52,211,153,.25)}
    .jb-confirm-no{border-color:rgba(248,113,113,.6);color:var(--jb-red);background:rgba(248,113,113,.08)}
    .jb-confirm-no:hover{background:rgba(248,113,113,.2)}
    @media(max-width:480px){#jb-window{width:calc(100vw - 2rem);right:1rem}}
</style>

{{-- Trigger --}}
<button id="jb-trigger" title="{{ $botName }}" onclick="jobBot.toggle()">
    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
    </svg>
    <span id="jb-badge">0</span>
</button>

{{-- Window --}}
<div id="jb-window" role="dialog" aria-label="{{ $botName }}">
    <div class="jb-header">
        <div class="jb-avatar">{{ $initials }}<span class="jb-dot"></span></div>
        <div class="jb-header-info">
            <p class="jb-header-title">{{ $botName }}</p>
            <p class="jb-header-sub"><span class="jb-online-dot"></span> Online · Find jobs &amp; candidates</p>
        </div>
        <button class="jb-header-btn" title="Clear" onclick="jobBot.clear()">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
        <button class="jb-header-btn" title="Close" onclick="jobBot.toggle()">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <div id="jb-suggestions">
        @foreach($chips as $chip)
            <button class="jb-chip" onclick="jobBot.send('{{ $chip['query'] }}')">{{ $chip['label'] }}</button>
        @endforeach
    </div>

    <div id="jb-messages"></div>

    <div class="jb-input-area">
        <div class="jb-input-row">
            <label title="Upload Resume" style="cursor:pointer;color:rgba(167,139,250,.6);display:flex;align-items:center;">
                <input type="file" id="jb-file-input" accept=".pdf,.doc,.docx" onchange="jobBot.uploadResume(this)" style="display:none;">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
            </label>
            <input id="jb-input" class="jb-input" type="text" placeholder="Search jobs, candidates..." onkeydown="if(event.key==='Enter')jobBot.send()" autocomplete="off">
            <button id="jb-mic" class="jb-mic" title="Voice input" onclick="jobBot.toggleMic()">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4M12 3a4 4 0 014 4v4a4 4 0 01-8 0V7a4 4 0 014-4z"/></svg>
            </button>
            <button id="jb-send" class="jb-send" onclick="jobBot.send()" disabled>
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
            </button>
        </div>
        <p class="jb-footer-text">Powered by {{ $botName }}</p>
    </div>

    <div class="jb-confirm-bar" id="jb-confirm-bar">
        <span id="jb-confirm-text" style="flex:1;"></span>
        <button class="jb-confirm-btn jb-confirm-yes" onclick="jobBot.confirmAction(true)">✅ Yes, karo</button>
        <button class="jb-confirm-btn jb-confirm-no"  onclick="jobBot.confirmAction(false)">❌ Nahi</button>
    </div>
</div>

<script>
const jobBot = (() => {
    const QUERY_URL  = "{{ route('jobbot.query') }}";
    const RESUME_URL = "{{ route('jobbot.resume') }}";
    const BOT_NAME   = "{{ $initials }}";
    const GREETING   = {!! json_encode($greeting) !!};
    const CSRF       = () => document.querySelector('meta[name=csrf-token]')?.content ?? '';

    let isOpen=false, isTyping=false, unread=0, msgId=1;
    let mediaRecorder=null, audioChunks=[], isRecording=false, pendingConfirm=null;
    let typingEl=null;

    const $=id=>document.getElementById(id);
    const win=()=>$('jb-window'), msgs=()=>$('jb-messages');
    const inp=()=>$('jb-input'), btn=()=>$('jb-send');
    const badge=()=>$('jb-badge'), chips=()=>$('jb-suggestions');

    function init(){
        pushBot(GREETING);
        inp().addEventListener('input',()=>{ btn().disabled=!inp().value.trim()||isTyping; });
    }

    function toggle(){
        isOpen=!isOpen;
        win().classList.toggle('show',isOpen);
        $('jb-trigger').classList.toggle('open',isOpen);
        if(isOpen){ unread=0; renderBadge(); setTimeout(()=>inp().focus(),120); scrollBottom(); chips().style.display=msgId<=2?'':'none'; }
    }

    function clear(){
        msgs().innerHTML=''; msgId=1;
        pushBot('Chat clear ho gaya! 🧹<br>Kya dhundna hai — candidates, jobs, ya resume parse?');
        chips().style.display='';
    }

    function send(text){
        const q=(text||inp().value).trim();
        if(!q||isTyping) return;
        if(!text) inp().value='';
        btn().disabled=true;
        chips().style.display='none';
        pushUser(q);
        setTyping(true);

        fetch(QUERY_URL,{
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF()},
            body:JSON.stringify({query:q}),
        }).then(r=>r.json()).then(data=>{setTyping(false);handleResponse(data);})
          .catch(()=>{setTyping(false);pushBot('⚠️ Network error. Thodi der baad try karein.');});
    }

    function uploadResume(input){
        const file=input.files[0]; if(!file) return;
        pushUser(`📎 Uploading: ${file.name}`);
        setTyping(true);
        const fd=new FormData();
        fd.append('resume',file);
        fd.append('_token',CSRF());
        fetch(RESUME_URL,{method:'POST',body:fd})
            .then(r=>r.json()).then(data=>{setTyping(false);handleResponse(data);})
            .catch(()=>{setTyping(false);pushBot('⚠️ Resume upload fail. PDF ya DOCX try karein (max 5MB).');});
        input.value='';
    }

    function handleResponse(data){
        const d=data.data;
        if(!d){ pushBot(data.message); return; }
        if(d.type==='resume_upload_prompt'){ pushBotWithUpload(data.message); return; }
        if(d.type==='resume_result'){ pushResumeResult(data.message,d.extracted,d.skills); return; }
        if(d.type==='profile'||d.columns){ pushTable(data.message,d.columns,d.rows); return; }
        pushBot(data.message);
    }

    function pushUser(text){
        const row=document.createElement('div');
        row.className='jb-row user';
        row.innerHTML=`<div style="max-width:82%"><div class="jb-bubble user">${esc(text)}</div><div class="jb-time" style="text-align:right">${now()}</div></div>`;
        msgs().appendChild(row); scrollBottom();
        if(!isOpen){unread++;renderBadge();}
    }

    function pushBot(html){
        const row=document.createElement('div');
        row.className='jb-row bot';
        row.innerHTML=`<div class="jb-bubble-wrap"><div class="jb-mini-avatar">${BOT_NAME}</div><div><div class="jb-bubble bot">${html}</div><div class="jb-time">${now()}</div></div></div>`;
        msgs().appendChild(row); scrollBottom();
        if(!isOpen){unread++;renderBadge();}
    }

    function pushTable(header,columns,rows){
        let t=`<div class="jb-table-wrap"><table class="jb-table"><thead><tr>`;
        columns.forEach(c=>t+=`<th>${esc(c)}</th>`);
        t+=`</tr></thead><tbody>`;
        rows.length===0
            ? t+=`<tr><td colspan="${columns.length}" style="text-align:center;color:var(--jb-muted)">No results</td></tr>`
            : rows.forEach(r=>{t+='<tr>';columns.forEach(c=>t+=`<td>${esc(String(r[c]??'—'))}</td>`);t+='</tr>';});
        t+=`</tbody></table></div>`;
        pushBot(`${header}${t}`);
    }

    function pushResumeResult(header,ext,skills){
        const tags=(skills||[]).map(s=>`<span class="jb-skill-tag">${esc(s)}</span>`).join('')||'<span style="color:var(--jb-muted)">None detected</span>';
        const card=`<div class="jb-resume-card">
            <div class="field">Name</div><div class="value">${esc(ext.name||'—')}</div>
            <div class="field">Email</div><div class="value">${esc(ext.email||'—')}</div>
            <div class="field">Phone</div><div class="value">${esc(ext.phone||'—')}</div>
            <div class="field">Experience</div><div class="value">${esc(ext.total_experience_years||'—')}</div>
            <div class="field">Skills</div><div class="value">${tags}</div>
        </div>
        <div style="margin-top:.6rem;padding:.5rem .6rem;background:rgba(52,211,153,.08);border:1px solid rgba(52,211,153,.25);border-radius:8px;font-size:.75rem;color:#34d399;">
            💾 Data sahi lage to <strong>"save karo"</strong> type karein.
        </div>`;
        pushBot(`${header}${card}`);
    }

    function pushBotWithUpload(header){
        const id='jb-uz-'+msgId++;
        const row=document.createElement('div');
        row.className='jb-row bot';
        row.innerHTML=`<div class="jb-bubble-wrap"><div class="jb-mini-avatar">${BOT_NAME}</div><div style="max-width:88%"><div class="jb-bubble bot">${header}<label class="jb-upload-zone" for="${id}"><svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="margin:0 auto .3rem;display:block"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>Click to upload PDF/DOCX (max 5MB)<input type="file" id="${id}" accept=".pdf,.doc,.docx" onchange="jobBot.uploadResume(this)"></label></div><div class="jb-time">${now()}</div></div></div>`;
        msgs().appendChild(row); scrollBottom();
    }

    function setTyping(on){
        isTyping=on; btn().disabled=on||!inp().value.trim();
        if(on&&!typingEl){
            const row=document.createElement('div'); row.className='jb-row bot';
            row.innerHTML=`<div class="jb-bubble-wrap"><div class="jb-mini-avatar">${BOT_NAME}</div><div class="jb-typing"><div class="jb-dot-bounce"></div><div class="jb-dot-bounce"></div><div class="jb-dot-bounce"></div></div></div>`;
            typingEl=row; msgs().appendChild(row); scrollBottom();
        } else if(!on&&typingEl){ typingEl.remove(); typingEl=null; }
    }

    // ── Mic ──
    async function toggleMic(){ isRecording?stopRecording():await startRecording(); }
    async function startRecording(){
        try{
            const stream=await navigator.mediaDevices.getUserMedia({audio:true});
            audioChunks=[]; mediaRecorder=new MediaRecorder(stream);
            mediaRecorder.ondataavailable=e=>{if(e.data.size>0)audioChunks.push(e.data);};
            mediaRecorder.onstop=()=>{stream.getTracks().forEach(t=>t.stop());sendAudioToServer();};
            mediaRecorder.start(); isRecording=true; setMicUI(true);
        }catch(e){pushBot('⚠️ Microphone access nahi mila.');}
    }
    function stopRecording(){if(mediaRecorder&&mediaRecorder.state!=='inactive')mediaRecorder.stop();isRecording=false;setMicUI(false);}
    function setMicUI(rec){$('jb-mic').classList.toggle('recording',rec);}
    function sendAudioToServer(){
        if(!audioChunks.length)return;
        const blob=new Blob(audioChunks,{type:'audio/webm'});
        const fd=new FormData(); fd.append('audio',blob,'voice.webm');
        setTyping(true);
        fetch('https://justairports.com/api/speech-to-text',{method:'POST',body:fd})
            .then(r=>{if(!r.ok)throw new Error(`HTTP ${r.status}`);return r.json();})
            .then(data=>{
                setTyping(false);
                const text=(data.text||'').trim();
                if(!text){pushBot('⚠️ Voice samajh nahi aya. Type karein.');return;}
                inp().value=text; btn().disabled=false;
                pushBot(`🎙️ Suna: "<strong>${esc(text)}</strong>"`);
                setTimeout(()=>send(text),400);
            }).catch(e=>{setTyping(false);pushBot(`⚠️ Voice fail (${e.message}).`);});
        audioChunks=[];
    }

    // ── Confirm ──
    function confirmAction(yes){
        $('jb-confirm-bar').classList.remove('show'); pendingConfirm=null;
        yes?send('haan'):pushBot('👍 Action cancel kar diya.');
    }

    // ── Utils ──
    function scrollBottom(){const m=msgs();requestAnimationFrame(()=>m.scrollTop=m.scrollHeight);}
    function renderBadge(){const b=badge();b.style.display=unread>0&&!isOpen?'flex':'none';b.textContent=unread>9?'9+':unread;}
    function now(){return new Date().toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit',hour12:true});}
    function esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

    return {init,toggle,clear,send,uploadResume,toggleMic,confirmAction};
})();

document.addEventListener('DOMContentLoaded', jobBot.init);
</script>
