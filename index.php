<?php
session_start();

// ── Vote options ──
$options = [
    1 => ['label' => '🌸 Spring', 'color' => '#f9a8d4'],
    2 => ['label' => '☀️ Summer', 'color' => '#fcd34d'],
    3 => ['label' => '🍂 Autumn', 'color' => '#fb923c'],
    4 => ['label' => '❄️ Winter', 'color' => '#93c5fd'],
];

// ── Simple file-based storage (no database needed) ──
$dataFile = __DIR__ . '/votes_data.json';

function loadData(string $file): array {
    if (!file_exists($file)) return ['votes' => [1=>0, 2=>0, 3=>0, 4=>0]];
    $d = json_decode(file_get_contents($file), true);
    return is_array($d) ? $d : ['votes' => [1=>0, 2=>0, 3=>0, 4=>0]];
}

function saveData(string $file, array $data): void {
    file_put_contents($file, json_encode($data), LOCK_EX);
}

$data    = loadData($dataFile);
$votes   = $data['votes'];
$message = '';
$error   = '';

// ── Handle vote submission ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $choice = (int)($_POST['choice'] ?? 0);
    if (!isset($_SESSION['voted'])) {
        if (isset($options[$choice])) {
            $votes[$choice]++;
            $data['votes'] = $votes;
            saveData($dataFile, $data);
            $_SESSION['voted'] = $choice;
            $message = "You voted for " . $options[$choice]['label'] . "!";
        } else {
            $error = "Please select an option before voting.";
        }
    } else {
        $error = "You have already voted!";
    }
}

$total     = array_sum($votes);
$hasVoted  = isset($_SESSION['voted']);
$userVote  = $_SESSION['voted'] ?? null;

// ── Reset (dev helper) ──
if (isset($_GET['reset'])) {
    unset($_SESSION['voted']);
    saveData($dataFile, ['votes' => [1=>0, 2=>0, 3=>0, 4=>0]]);
    header('Location: index.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Vote of the Day</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Segoe UI', sans-serif;
    background: #f0f4f8;
    min-height: 100vh;
    display: flex; align-items: center; justify-content: center;
    padding: 24px;
  }
  .card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 4px 28px rgba(0,0,0,0.08);
    max-width: 500px; width: 100%;
    padding: 40px 36px 32px;
  }
  .card-header { text-align: center; margin-bottom: 32px; }
  .card-header .icon { font-size: 48px; display: block; margin-bottom: 12px; }
  .card-header h1 { font-size: 24px; font-weight: 800; color: #1e1e3a; margin-bottom: 6px; }
  .card-header p  { font-size: 14px; color: #888; }

  /* ── Options ── */
  .options { display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px; }

  .option-label {
    display: flex; align-items: center; gap: 14px;
    border: 2px solid #e8eaf0; border-radius: 14px;
    padding: 14px 16px; cursor: pointer;
    transition: border-color .2s, background .2s;
  }
  .option-label:hover { border-color: #a5b4fc; background: #f8f8ff; }
  .option-label input[type="radio"] { display: none; }
  .option-label.selected { border-color: #6366f1; background: #eef2ff; }

  .dot {
    width: 20px; height: 20px; border-radius: 50%;
    border: 2px solid #d0d4e8; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    transition: background .2s, border-color .2s;
  }
  .selected .dot { background: #6366f1; border-color: #6366f1; }
  .selected .dot::after { content: ''; width: 8px; height: 8px;
    border-radius: 50%; background: #fff; display: block; }

  .option-text { font-size: 15px; font-weight: 600; color: #2a2a4a; }

  /* ── Submit ── */
  .btn-vote {
    width: 100%; padding: 14px; border: none; border-radius: 12px;
    background: #6366f1; color: #fff;
    font-size: 15px; font-weight: 700; cursor: pointer;
    transition: background .2s, transform .1s;
    margin-bottom: 10px;
  }
  .btn-vote:hover  { background: #4f46e5; }
  .btn-vote:active { transform: scale(.98); }

  .msg-success {
    background: #ecfdf5; color: #059669;
    border-radius: 10px; padding: 10px 14px;
    font-size: 13px; font-weight: 600; margin-bottom: 18px; text-align: center;
  }
  .msg-error {
    background: #fef2f2; color: #dc2626;
    border-radius: 10px; padding: 10px 14px;
    font-size: 13px; font-weight: 600; margin-bottom: 18px; text-align: center;
  }

  /* ── Results ── */
  .results { display: flex; flex-direction: column; gap: 14px; margin-bottom: 20px; }

  .result-row { }
  .result-meta {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 6px;
  }
  .result-meta .r-label { font-size: 14px; font-weight: 700; color: #2a2a4a; }
  .result-meta .r-count { font-size: 13px; color: #888; }
  .result-meta .r-pct   { font-size: 14px; font-weight: 800; color: #6366f1; }

  .bar-wrap { background: #f0f2f8; border-radius: 20px; height: 10px; overflow: hidden; }
  .bar-fill  {
    height: 100%; border-radius: 20px;
    transition: width .8s cubic-bezier(.4,0,.2,1);
  }
  .result-row.my-vote .r-label::after {
    content: ' ✓'; color: #6366f1;
  }

  .total-line {
    text-align: center; font-size: 13px; color: #aaa;
    border-top: 1px solid #f0f0f8; padding-top: 16px; margin-top: 4px;
  }
  .total-line strong { color: #4a4a6a; }

  .reset-link {
    display: block; text-align: center; margin-top: 14px;
    font-size: 12px; color: #ccc; text-decoration: none;
  }
  .reset-link:hover { color: #999; }

  @media(max-width:480px){
    .card { padding: 28px 18px 24px; }
  }
</style>
</head>
<body>
<div class="card">

  <div class="card-header">
    <span class="icon">🗳️</span>
    <h1>Vote of the Day</h1>
    <p>What is your favourite season? Cast your vote!</p>
  </div>

  <?php if ($message): ?>
    <div class="msg-success"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="msg-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if (!$hasVoted): ?>
  <!-- ── Voting form ── -->
  <form method="POST" id="vote-form">
    <div class="options">
      <?php foreach ($options as $id => $opt): ?>
      <label class="option-label" id="lbl-<?= $id ?>">
        <input type="radio" name="choice" value="<?= $id ?>"
               onchange="selectOpt(<?= $id ?>)"/>
        <div class="dot" id="dot-<?= $id ?>"></div>
        <span class="option-text"><?= $opt['label'] ?></span>
      </label>
      <?php endforeach; ?>
    </div>
    <button type="submit" class="btn-vote">Cast My Vote →</button>
  </form>

  <?php else: ?>
  <!-- ── Results ── -->
  <div class="results">
    <?php foreach ($options as $id => $opt):
      $count = $votes[$id];
      $pct   = $total > 0 ? round($count / $total * 100) : 0;
      $isMe  = ($userVote === $id);
    ?>
    <div class="result-row <?= $isMe ? 'my-vote' : '' ?>">
      <div class="result-meta">
        <span class="r-label"><?= $opt['label'] ?></span>
        <span class="r-count"><?= $count ?> vote<?= $count !== 1 ? 's' : '' ?></span>
        <span class="r-pct"><?= $pct ?>%</span>
      </div>
      <div class="bar-wrap">
        <div class="bar-fill"
             style="width:<?= $pct ?>%;background:<?= $opt['color'] ?>;">
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="total-line">
    <strong><?= $total ?></strong> total vote<?= $total !== 1 ? 's' : '' ?> cast
  </div>
  <?php endif; ?>

  <a class="reset-link" href="?reset=1">↺ Reset votes</a>
</div>

<script>
  function selectOpt(id) {
    document.querySelectorAll('.option-label').forEach(el => el.classList.remove('selected'));
    document.getElementById('lbl-' + id).classList.add('selected');
  }
</script>
</body>
</html>
