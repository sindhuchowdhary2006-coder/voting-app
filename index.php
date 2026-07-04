<?php
session_start();

// Seed baseline vote counts (shared across session)
if (!isset($_SESSION['votes'])) {
    $_SESSION['votes'] = [
        0=>['a'=>42,'b'=>58], 1=>['a'=>55,'b'=>45],
        2=>['a'=>63,'b'=>37], 3=>['a'=>48,'b'=>52],
        4=>['a'=>38,'b'=>62], 5=>['a'=>50,'b'=>50],
        6=>['a'=>41,'b'=>59], 7=>['a'=>67,'b'=>33],
        8=>['a'=>44,'b'=>56], 9=>['a'=>53,'b'=>47],
    ];
}

// AJAX vote handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $body = json_decode(file_get_contents('php://input'), true);
    $qid  = (int)($body['qid'] ?? -1);
    $side = ($body['side'] === 'a') ? 'a' : 'b';
    if ($qid >= 0 && $qid <= 9) {
        $_SESSION['votes'][$qid][$side]++;
        $a = $_SESSION['votes'][$qid]['a'];
        $b = $_SESSION['votes'][$qid]['b'];
        $t = $a + $b;
        echo json_encode(['pctA' => round($a/$t*100), 'pctB' => round($b/$t*100)]);
    } else {
        echo json_encode(['error'=>'invalid']);
    }
    exit;
}

$seedVotes = json_encode($_SESSION['votes']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Would You Rather? 🤔</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{
  font-family:'Segoe UI',sans-serif;
  background:linear-gradient(135deg,#e8f4fd 0%,#fce8f4 100%);
  min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;
}
.page{display:none;width:100%;justify-content:center;animation:fadeIn .4s ease;}
.page.active{display:flex;}
@keyframes fadeIn{from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:translateY(0);}}

/* ── INTRO ── */
.intro-card{
  background:#fff;border-radius:28px;box-shadow:0 8px 40px rgba(140,100,200,.13);
  max-width:520px;width:100%;padding:50px 40px 42px;text-align:center;
}
.big-emoji{font-size:64px;margin-bottom:16px;display:block;animation:bob 2.5s ease-in-out infinite;}
@keyframes bob{0%,100%{transform:translateY(0) rotate(-3deg);}50%{transform:translateY(-10px) rotate(3deg);}}
.intro-card h1{font-size:30px;font-weight:900;color:#3b3b6b;margin-bottom:8px;}
.intro-card .sub{font-size:15px;color:#8888aa;line-height:1.6;margin-bottom:24px;}
.live-banner{
  display:flex;align-items:center;justify-content:center;gap:8px;
  background:#f3f0ff;border-radius:12px;padding:10px 18px;
  font-size:13px;font-weight:700;color:#7c68cc;margin-bottom:24px;
}
.live-dot{width:8px;height:8px;border-radius:50%;background:#a78bfa;
          animation:pulse 1.2s ease-in-out infinite;}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1);}50%{opacity:.4;transform:scale(.6);}}
.feature-row{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-bottom:24px;}
.chip{background:#f3f0ff;color:#7c68cc;border-radius:20px;padding:7px 16px;font-size:12px;font-weight:700;}
.rules{background:#fafafa;border-radius:16px;padding:18px 20px;text-align:left;margin-bottom:28px;}
.rules p{font-size:13px;color:#6666aa;line-height:1.9;}
.rules p b{color:#9b85d9;}
.btn-play{
  width:100%;background:linear-gradient(135deg,#a78bfa,#f472b6);
  color:#fff;border:none;border-radius:16px;padding:16px;
  font-size:16px;font-weight:800;cursor:pointer;
  box-shadow:0 4px 20px rgba(167,139,250,.35);transition:transform .15s,box-shadow .15s;
}
.btn-play:hover{transform:translateY(-2px);box-shadow:0 8px 28px rgba(167,139,250,.45);}
.btn-play:active{transform:scale(.97);}

/* ── GAME ── */
.game-wrap{max-width:580px;width:100%;}
.hud{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;}
.hud-score{background:#fff;border-radius:12px;padding:8px 18px;
           font-size:13px;font-weight:700;color:#7c68cc;box-shadow:0 2px 10px rgba(140,100,200,.1);}
.hud-score span{color:#3b3b6b;font-size:16px;}
.progress-wrap{background:#e8e0f8;border-radius:20px;height:8px;flex:1;margin:0 14px;overflow:hidden;}
.progress-bar{height:100%;border-radius:20px;background:linear-gradient(90deg,#a78bfa,#f472b6);transition:width .5s ease;}
.q-num{font-size:12px;font-weight:700;color:#b0a0d0;}
.question-card{
  background:#fff;border-radius:24px;box-shadow:0 6px 32px rgba(140,100,200,.12);
  padding:32px 28px 22px;text-align:center;margin-bottom:18px;
}
.q-label{display:inline-block;background:#fdf0ff;color:#c084fc;border-radius:20px;
         padding:4px 14px;font-size:11px;font-weight:800;letter-spacing:1px;
         text-transform:uppercase;margin-bottom:14px;}
.question-card h2{font-size:19px;font-weight:800;color:#3b3b6b;line-height:1.4;margin-bottom:4px;}
.question-card .hint{font-size:12px;color:#b0b0cc;}
.vs-row{display:grid;grid-template-columns:1fr auto 1fr;gap:12px;align-items:stretch;margin-bottom:16px;}
.choice{
  background:#fff;border-radius:20px;box-shadow:0 4px 20px rgba(140,100,200,.10);
  padding:24px 16px;cursor:pointer;text-align:center;
  transition:transform .2s,box-shadow .2s;
  border:3px solid transparent;position:relative;overflow:hidden;
}
.choice:hover{transform:translateY(-4px);box-shadow:0 10px 32px rgba(140,100,200,.18);}
.choice.picked-a{border-color:#a78bfa;background:#fdf8ff;}
.choice.picked-b{border-color:#f472b6;background:#fff8fc;}
.choice.win{border-color:#34d399;}
.choice.lose{opacity:.5;}
.choice-emoji{font-size:36px;margin-bottom:10px;display:block;}
.choice-text{font-size:13px;font-weight:700;color:#4a4a6a;line-height:1.4;}
.choice-fill{position:absolute;bottom:0;left:0;width:100%;transition:height .7s ease;height:0;}
.choice-a .choice-fill{background:linear-gradient(90deg,#a78bfa,#818cf8);}
.choice-b .choice-fill{background:linear-gradient(90deg,#f472b6,#fb7185);}
.choice-pct{font-size:22px;font-weight:900;margin-top:8px;display:none;}
.choice-a .choice-pct{color:#a78bfa;}
.choice-b .choice-pct{color:#f472b6;}
.vs-badge{
  background:linear-gradient(135deg,#a78bfa,#f472b6);color:#fff;
  border-radius:50%;width:46px;height:46px;display:flex;align-items:center;
  justify-content:center;font-size:13px;font-weight:900;
  box-shadow:0 4px 14px rgba(167,139,250,.4);flex-shrink:0;align-self:center;
}
.verdict{
  background:#fff;border-radius:16px;padding:14px 20px;text-align:center;
  font-size:14px;font-weight:700;color:#6b5cc8;
  box-shadow:0 2px 12px rgba(140,100,200,.1);
  min-height:48px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;
}
.btn-next{
  width:100%;background:linear-gradient(135deg,#a78bfa,#f472b6);
  color:#fff;border:none;border-radius:14px;padding:14px;
  font-size:14px;font-weight:800;cursor:pointer;
  box-shadow:0 4px 16px rgba(167,139,250,.3);transition:transform .15s;display:none;
}
.btn-next:hover{transform:translateY(-2px);}
.btn-next:active{transform:scale(.97);}

/* ── RESULTS ── */
.result-card{
  background:#fff;border-radius:28px;box-shadow:0 8px 40px rgba(140,100,200,.14);
  max-width:520px;width:100%;padding:48px 36px 40px;text-align:center;
}
.trophy{font-size:72px;margin-bottom:6px;display:block;animation:pop .5s ease-out;}
@keyframes pop{from{transform:scale(.3) rotate(-20deg);}to{transform:scale(1) rotate(0);}}
.result-card h2{font-size:26px;font-weight:900;color:#3b3b6b;margin-bottom:6px;}
.result-card .res-sub{font-size:14px;color:#9090bb;margin-bottom:24px;}
.score-big{
  font-size:58px;font-weight:900;
  background:linear-gradient(135deg,#a78bfa,#f472b6);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
  margin-bottom:4px;
}
.score-label{font-size:13px;color:#b0b0cc;margin-bottom:24px;}
.personality{
  background:linear-gradient(135deg,#fdf4ff,#fff0f9);
  border-radius:18px;padding:18px 22px;margin-bottom:24px;
}
.p-title{font-size:11px;font-weight:800;letter-spacing:1px;
         text-transform:uppercase;color:#c084fc;margin-bottom:6px;}
.p-badge{font-size:20px;font-weight:900;color:#3b3b6b;margin-bottom:4px;}
.p-desc{font-size:13px;color:#8888aa;line-height:1.5;}
.summary-list{text-align:left;margin-bottom:24px;}
.summary-row{
  display:flex;justify-content:space-between;align-items:center;
  padding:9px 0;border-bottom:1px solid #f0eeff;font-size:13px;color:#6666aa;
}
.summary-row:last-child{border-bottom:none;}
.summary-row .picked{font-weight:700;color:#3b3b6b;}
.btn-again{
  width:100%;background:linear-gradient(135deg,#a78bfa,#f472b6);
  color:#fff;border:none;border-radius:16px;padding:15px;
  font-size:15px;font-weight:800;cursor:pointer;
  box-shadow:0 4px 20px rgba(167,139,250,.35);transition:transform .15s;
}
.btn-again:hover{transform:translateY(-2px);}
.confetti-canvas{position:fixed;pointer-events:none;top:0;left:0;width:100%;height:100%;z-index:999;}
@media(max-width:480px){
  .intro-card{padding:36px 22px 30px;}
  .result-card{padding:36px 20px 28px;}
  .question-card{padding:24px 16px 18px;}
  .choice{padding:18px 10px;}
}
</style>
</head>
<body>

<!-- ══ INTRO PAGE ══ -->
<div class="page active" id="pg-intro">
  <div class="intro-card">
    <span class="big-emoji">🤔</span>
    <h1>Would You Rather?</h1>
    <p class="sub">The ultimate dilemma game — no right answers,<br>just your gut vs the crowd!</p>
    <div class="live-banner">
      <div class="live-dot"></div>
      Live votes powered by PHP &amp; sessions
    </div>
    <div class="feature-row">
      <div class="chip">🎮 10 Rounds</div>
      <div class="chip">⚡ Fast &amp; Fun</div>
      <div class="chip">📊 Real Votes</div>
      <div class="chip">🏆 Personality Score</div>
    </div>
    <div class="rules">
      <p>🎯 <b>Pick one</b> option each round — no skipping!</p>
      <p>📊 <b>See live vote %</b> after you choose.</p>
      <p>🏆 <b>Earn 10 pts</b> for matching the majority.</p>
      <p>🧠 <b>Discover your personality</b> at the end!</p>
    </div>
    <button class="btn-play" onclick="startGame()">Let's Play! 🚀</button>
  </div>
</div>

<!-- ══ GAME PAGE ══ -->
<div class="page" id="pg-game">
  <div class="game-wrap">
    <div class="hud">
      <div class="hud-score">Score <span id="score-display">0</span></div>
      <div class="progress-wrap"><div class="progress-bar" id="prog-bar" style="width:0%"></div></div>
      <div class="q-num" id="q-num">1 / 10</div>
    </div>
    <div class="question-card">
      <span class="q-label">🤔 Would You Rather…</span>
      <h2 id="q-text"></h2>
      <p class="hint" id="q-hint"></p>
    </div>
    <div class="vs-row">
      <div class="choice choice-a" id="choice-a" onclick="pick('a')">
        <span class="choice-emoji" id="emoji-a"></span>
        <div class="choice-text" id="text-a"></div>
        <div class="choice-pct" id="pct-a"></div>
        <div class="choice-fill" id="fill-a"></div>
      </div>
      <div class="vs-badge">VS</div>
      <div class="choice choice-b" id="choice-b" onclick="pick('b')">
        <span class="choice-emoji" id="emoji-b"></span>
        <div class="choice-text" id="text-b"></div>
        <div class="choice-pct" id="pct-b"></div>
        <div class="choice-fill" id="fill-b"></div>
      </div>
    </div>
    <div class="verdict" id="verdict">👆 Pick one to see how others voted!</div>
    <button class="btn-next" id="btn-next" onclick="nextQ()">Next Question →</button>
  </div>
</div>

<!-- ══ RESULTS PAGE ══ -->
<div class="page" id="pg-result">
  <div class="result-card">
    <span class="trophy" id="trophy-icon">🏆</span>
    <h2 id="res-title">Results!</h2>
    <p class="res-sub" id="res-sub"></p>
    <div class="score-big" id="res-score"></div>
    <div class="score-label">points earned</div>
    <div class="personality">
      <div class="p-title">Your Personality Type</div>
      <div class="p-badge" id="p-badge"></div>
      <div class="p-desc" id="p-desc"></div>
    </div>
    <div class="summary-list" id="summary-list"></div>
    <button class="btn-again" onclick="startGame()">Play Again 🔄</button>
  </div>
</div>

<canvas class="confetti-canvas" id="confetti-canvas"></canvas>

<script>
const SEED = <?= $seedVotes ?>;

const QUESTIONS = [
  {text:"Fight one horse-sized duck",hint:"Size matters… or does it?",
   a:{emoji:"🦆",text:"Fight one horse-sized duck"},
   b:{emoji:"🐴",text:"Fight 100 duck-sized horses"}},
  {text:"Live without the internet",hint:"Think carefully…",
   a:{emoji:"📵",text:"No internet forever"},
   b:{emoji:"🌍",text:"Never leave your city"}},
  {text:"Choose a superpower",hint:"Only one, forever!",
   a:{emoji:"🦅",text:"Fly anywhere instantly"},
   b:{emoji:"🧠",text:"Read anyone's mind"}},
  {text:"Pick your nightmare",hint:"No escape from either!",
   a:{emoji:"🥶",text:"Always be a little cold"},
   b:{emoji:"🥵",text:"Always be a little hot"}},
  {text:"Time travel direction",hint:"You can never return to now…",
   a:{emoji:"🏛️",text:"500 years in the past"},
   b:{emoji:"🚀",text:"500 years into the future"}},
  {text:"Pick your strange ability",hint:"You have no choice!",
   a:{emoji:"😂",text:"Laugh at funerals uncontrollably"},
   b:{emoji:"😴",text:"Fall asleep every time you sit"}},
  {text:"Celebrity swap for a year",hint:"One full year!",
   a:{emoji:"⭐",text:"The looks of any celebrity"},
   b:{emoji:"🧠",text:"The brain of any genius"}},
  {text:"Your food fate forever",hint:"Every single meal!",
   a:{emoji:"🍕",text:"Only pizza forever"},
   b:{emoji:"🍣",text:"Only sushi forever"}},
  {text:"Know your fate",hint:"No exceptions ever!",
   a:{emoji:"🔮",text:"Know HOW you will die"},
   b:{emoji:"📅",text:"Know WHEN you will die"}},
  {text:"Your final dilemma",hint:"Choose your nightmare…",
   a:{emoji:"🕷️",text:"Spiders fill your room once a year"},
   b:{emoji:"🐍",text:"One snake always in your home"}},
];

const PERSONALITIES = [
  {min:0, max:2, badge:"🌊 The Free Spirit",   desc:"You march to your own beat. Bold, original, unpredictable."},
  {min:3, max:5, badge:"🎯 The Maverick",      desc:"Half rebel, half pragmatist. You question norms but know when to flow."},
  {min:6, max:7, badge:"🤝 The Crowd Pleaser", desc:"You're in sync with the masses. Empathetic and community-minded!"},
  {min:8, max:9, badge:"🧠 The Trendspotter",  desc:"Almost always with the majority. You just *get* what people think."},
  {min:10,max:10,badge:"🔮 The Hive Mind",     desc:"Perfect score! You think exactly like the crowd. Are you psychic?"},
];

let current=0, score=0, picked=null, answers=[], livePcts={};

function show(id){
  document.querySelectorAll('.page').forEach(p=>p.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  window.scrollTo(0,0);
}

function startGame(){
  current=0; score=0; picked=null; answers=[]; livePcts={};
  show('pg-game');
  loadQ();
}

function loadQ(){
  const q=QUESTIONS[current];
  picked=null;
  document.getElementById('q-text').textContent=q.text;
  document.getElementById('q-hint').textContent=q.hint;
  document.getElementById('emoji-a').textContent=q.a.emoji;
  document.getElementById('text-a').textContent=q.a.text;
  document.getElementById('emoji-b').textContent=q.b.emoji;
  document.getElementById('text-b').textContent=q.b.text;
  document.getElementById('q-num').textContent=`${current+1} / ${QUESTIONS.length}`;
  document.getElementById('prog-bar').style.width=`${(current/QUESTIONS.length)*100}%`;
  document.getElementById('score-display').textContent=score;
  document.getElementById('verdict').textContent='👆 Pick one to see how others voted!';
  document.getElementById('btn-next').style.display='none';
  ['a','b'].forEach(s=>{
    const el=document.getElementById('choice-'+s);
    el.classList.remove('picked-a','picked-b','win','lose');
    el.style.pointerEvents='auto';
    document.getElementById('pct-'+s).style.display='none';
    document.getElementById('fill-'+s).style.height='0';
  });
}

function pick(side){
  if(picked) return;
  picked=side;
  document.getElementById('choice-a').style.pointerEvents='none';
  document.getElementById('choice-b').style.pointerEvents='none';
  document.getElementById('choice-'+side).classList.add('picked-'+side);
  document.getElementById('verdict').textContent='⏳ Counting votes…';

  fetch('', {
    method:'POST',
    headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
    body: JSON.stringify({qid:current, side:side})
  })
  .then(r=>r.json())
  .then(data=>{
    const pA=data.pctA, pB=data.pctB;
    livePcts[current]={pA,pB};
    const majority = pA>=pB ? 'a' : 'b';
    setTimeout(()=>{
      document.getElementById('fill-a').style.height=pA+'%';
      document.getElementById('fill-b').style.height=pB+'%';
      document.getElementById('pct-a').style.display='block';
      document.getElementById('pct-b').style.display='block';
      document.getElementById('pct-a').textContent=pA+'%';
      document.getElementById('pct-b').textContent=pB+'%';
    },200);

    document.getElementById('choice-'+majority).classList.add('win');
    document.getElementById('choice-'+(majority==='a'?'b':'a')).classList.add('lose');

    let gained=0;
    if(side===majority){score+=10;gained=10;}
    document.getElementById('score-display').textContent=score;

    const match=["🎉 With the majority!","🙌 Popular choice!","👏 Most people agree!"];
    const diff =["🦄 Against the tide!","🌊 You're in the minority!","🤔 Rare choice — respect!"];
    const pool = side===majority ? match : diff;
    const msg  = pool[Math.floor(Math.random()*pool.length)];
    document.getElementById('verdict').textContent=msg+(gained?' +10 pts ✨':'');

    answers.push({q:QUESTIONS[current].text,
                  pickedEmoji:QUESTIONS[current][side].emoji,
                  pickedText:QUESTIONS[current][side].text,
                  majority});

    document.getElementById('btn-next').style.display='block';
    document.getElementById('btn-next').textContent=
      current+1<QUESTIONS.length ? 'Next Question →' : 'See Results 🏆';
  })
  .catch(()=>{
    document.getElementById('verdict').textContent='⚠️ Error recording vote.';
  });
}

function nextQ(){
  current++;
  if(current>=QUESTIONS.length){showResults();return;}
  loadQ();
}

function showResults(){
  show('pg-result');
  document.getElementById('prog-bar').style.width='100%';
  const matches=answers.filter((a,i)=>a.majority===( 
    (SEED[i]?.a??50)>=(SEED[i]?.b??50)?'a':'b'
  )).length;

  document.getElementById('res-score').textContent=score;
  document.getElementById('res-title').textContent=
    score>=80?'🔥 Crushing It!':score>=50?'😎 Nice Run!':'🌟 Good Game!';
  document.getElementById('res-sub').textContent=
    `You matched the crowd on ${matches} out of ${QUESTIONS.length} questions`;

  const p=PERSONALITIES.find(x=>matches>=x.min&&matches<=x.max);
  document.getElementById('p-badge').textContent=p.badge;
  document.getElementById('p-desc').textContent=p.desc;

  document.getElementById('summary-list').innerHTML=answers.map((a,i)=>{
    const matched=a.majority===(
      (SEED[i]?.a??50)>=(SEED[i]?.b??50)?'a':'b'
    );
    return `<div class="summary-row">
      <span>${i+1}. ${QUESTIONS[i].a.emoji}/${QUESTIONS[i].b.emoji}</span>
      <span class="picked">${a.pickedEmoji} ${a.pickedText.substring(0,24)}…</span>
      <span>${matched?'✅':'🔵'}</span>
    </div>`;
  }).join('');

  if(score>=80) confetti();
}

function confetti(){
  const canvas=document.getElementById('confetti-canvas');
  const ctx=canvas.getContext('2d');
  canvas.width=window.innerWidth; canvas.height=window.innerHeight;
  const pieces=Array.from({length:130},()=>({
    x:Math.random()*canvas.width,
    y:Math.random()*canvas.height-canvas.height,
    r:Math.random()*6+4, d:Math.random()*120,
    color:`hsl(${Math.random()*360},80%,68%)`,
    tilt:Math.random()*10-10, tiltAngle:0,
    tiltSpeed:Math.random()*.1+.05
  }));
  let f=0;
  (function draw(){
    ctx.clearRect(0,0,canvas.width,canvas.height);
    pieces.forEach(p=>{
      ctx.beginPath(); ctx.lineWidth=p.r; ctx.strokeStyle=p.color;
      ctx.moveTo(p.x+p.tilt+p.r/4,p.y);
      ctx.lineTo(p.x+p.tilt,p.y+p.tilt+p.r/2);
      ctx.stroke();
      p.tiltAngle+=p.tiltSpeed;
      p.y+=(Math.cos(f/10+p.d)+1+p.r/2)/2;
      p.tilt=Math.sin(p.tiltAngle)*15;
      if(p.y>canvas.height+20) p.y=-20;
    });
    if(++f<300) requestAnimationFrame(draw);
    else ctx.clearRect(0,0,canvas.width,canvas.height);
  })();
}
</script>
</body>
</html>
