<?php
session_start();

$configFile  = __DIR__ . '/config.php';
$dataFile    = dirname(__DIR__) . '/data/sponsors.json';

// Auto-create config with default password (admin123) on first visit
if (!file_exists($configFile)) {
    file_put_contents($configFile,
        "<?php\n// Change this hash. Generate: php -r \"echo password_hash('yourpassword', PASSWORD_DEFAULT);\"\n" .
        "define('ADMIN_PASS_HASH', '" . password_hash('admin123', PASSWORD_DEFAULT) . "');\n"
    );
}
require_once $configFile;

$msg = '';

/* ── logout ─────────────────────────────────────────── */
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

/* ── login ──────────────────────────────────────────── */
if (!empty($_POST['password']) && empty($_SESSION['admin'])) {
    if (password_verify($_POST['password'], ADMIN_PASS_HASH)) {
        $_SESSION['admin'] = true;
        $_SESSION['csrf']  = bin2hex(random_bytes(16));
        header('Location: index.php');
        exit;
    }
    $msg = 'Wrong password.';
}

$authed = !empty($_SESSION['admin']);

/* ── handle admin actions ────────────────────────────── */
if ($authed && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) {
        http_response_code(403); die('CSRF token mismatch.');
    }

    $action   = $_POST['action'];
    $sponsors = json_decode(file_get_contents($dataFile), true) ?: [];

    if ($action === 'add') {
        $entry = sanitizeEntry($_POST);
        if ($entry['name'] && $entry['url']) {
            $sponsors[] = $entry;
            $msg = 'Company added.';
        }
    } elseif ($action === 'edit') {
        $idx = (int)($_POST['idx'] ?? -1);
        if (isset($sponsors[$idx])) {
            $sponsors[$idx] = sanitizeEntry($_POST);
            $msg = 'Company updated.';
        }
    } elseif ($action === 'delete') {
        $idx = (int)($_POST['idx'] ?? -1);
        if (isset($sponsors[$idx])) {
            $name = htmlspecialchars($sponsors[$idx]['name'] ?? '');
            array_splice($sponsors, $idx, 1);
            $msg = "\"$name\" deleted.";
        }
    } elseif ($action === 'move') {
        $idx = (int)($_POST['idx'] ?? 0);
        $dir = $_POST['dir'] ?? '';
        if ($dir === 'up' && $idx > 0)                       [$sponsors[$idx-1],$sponsors[$idx]] = [$sponsors[$idx],$sponsors[$idx-1]];
        elseif ($dir === 'down' && $idx < count($sponsors)-1) [$sponsors[$idx],$sponsors[$idx+1]] = [$sponsors[$idx+1],$sponsors[$idx]];
    } elseif ($action === 'chgpass') {
        $np = $_POST['new_pass'] ?? '';
        if (strlen($np) >= 6) {
            $hash = password_hash($np, PASSWORD_DEFAULT);
            file_put_contents($configFile,
                "<?php\n// Change this hash. Generate: php -r \"echo password_hash('yourpassword', PASSWORD_DEFAULT);\"\n" .
                "define('ADMIN_PASS_HASH', '$hash');\n"
            );
            $msg = 'Password updated.';
        } else {
            $msg = 'Password must be at least 6 characters.';
        }
    }

    if (in_array($action, ['add','edit','delete','move'])) {
        $sponsors = array_values(array_filter($sponsors, fn($s) => !empty($s['name']) && !empty($s['url'])));
        file_put_contents($dataFile,
            json_encode($sponsors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );
    }
}

function sanitizeEntry(array $p): array {
    return [
        'name'        => trim(strip_tags($p['name']        ?? '')),
        'url'         => filter_var(trim($p['url'] ?? ''), FILTER_SANITIZE_URL),
        'description' => trim(strip_tags($p['description'] ?? '')),
        'category'    => trim(strip_tags($p['category']    ?? '')),
    ];
}

$sponsors = json_decode(file_get_contents($dataFile), true) ?: [];
$csrf     = $_SESSION['csrf'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin — Company Cards</title>
<link rel="noindex" href="index.php">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
body{background:#f0f2f5}
.adm-wrap{max-width:820px;margin:0 auto;padding:24px 18px}
.adm-header{display:flex;align-items:center;justify-content:space-between;background:#0b1324;color:#fff;padding:14px 22px;border-radius:14px;margin-bottom:22px}
.adm-header h1{margin:0;font-size:20px}
.adm-header a{color:#7dd3fc;font-size:14px}
.card{background:#fff;border:1px solid #dce3ef;border-radius:14px;padding:22px;margin-bottom:20px;box-shadow:0 4px 14px rgba(15,35,75,.05)}
.card h2{margin:0 0 16px;font-size:17px;border-bottom:1px solid #eee;padding-bottom:10px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px}
.form-row.full{grid-template-columns:1fr}
label{font-size:13px;font-weight:700;display:block;margin-bottom:4px;color:#4b5563}
input[type=text],input[type=url],input[type=password],textarea{width:100%;padding:10px 12px;border:1px solid #dce3ef;border-radius:10px;font-size:14px;font-family:inherit;box-sizing:border-box}
input:focus,textarea:focus{outline:none;border-color:#1266f1;box-shadow:0 0 0 3px rgba(18,102,241,.12)}
textarea{resize:vertical;min-height:70px}
.btn-primary{background:#1266f1;color:#fff;border:none;border-radius:10px;padding:10px 18px;font-weight:700;font-size:14px;cursor:pointer}
.btn-primary:hover{background:#0c4fbe}
.btn-sm{border:1px solid #dce3ef;background:#fff;border-radius:8px;padding:5px 10px;font-size:12px;cursor:pointer;font-weight:600}
.btn-danger{border-color:#fca5a5;color:#dc2626}.btn-danger:hover{background:#fef2f2}
.btn-edit{border-color:#93c5fd;color:#1266f1}.btn-edit:hover{background:#eff6ff}
.btn-move{border-color:#d1d5db;color:#6b7280}
.company-row{display:flex;align-items:flex-start;gap:14px;padding:12px 0;border-top:1px solid #f3f4f6}
.company-row:first-child{border-top:0}
.company-info{flex:1;min-width:0}
.company-info strong{display:block;font-size:15px}
.company-info .url{font-size:12px;color:#6b7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.company-info .cat{font-size:12px;background:#edf4ff;color:#1266f1;padding:2px 8px;border-radius:999px;font-weight:700;display:inline-block;margin-top:4px}
.company-actions{display:flex;gap:6px;flex-shrink:0}
.msg{padding:12px 16px;border-radius:10px;margin-bottom:16px;font-weight:600;background:#dcfce7;color:#166534;border:1px solid #bbf7d0}
.login-box{max-width:360px;margin:60px auto 0}
.empty{color:#9ca3af;font-size:14px;text-align:center;padding:20px}
</style>
</head>
<body>
<div class="adm-wrap">

<?php if (!$authed): ?>
<!-- ── login ── -->
<div class="login-box card">
  <h2 style="text-align:center;margin-bottom:20px">&#128274; Admin Login</h2>
  <?php if ($msg): ?><div class="msg" style="background:#fee2e2;color:#991b1b;border-color:#fca5a5"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <form method="post">
    <label>Password</label>
    <input type="password" name="password" autofocus style="margin-bottom:14px">
    <button class="btn-primary" style="width:100%">Login</button>
  </form>
  <p style="font-size:12px;color:#9ca3af;margin-top:14px;text-align:center">Default password: <code>admin123</code> &mdash; change it after first login.</p>
</div>

<?php else: ?>
<!-- ── dashboard ── -->
<div class="adm-header">
  <h1>&#127970; Company Cards Admin</h1>
  <div style="display:flex;gap:14px;align-items:center">
    <a href="../index.php" target="_blank">&#8592; View Site</a>
    <a href="?logout=1" style="color:#fca5a5">Logout</a>
  </div>
</div>

<?php if ($msg): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<!-- ── add / edit form ── -->
<div class="card" id="formCard">
  <h2 id="formTitle">&#10133; Add New Company</h2>
  <form method="post" id="companyForm">
    <input type="hidden" name="csrf"   value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="action" value="add" id="formAction">
    <input type="hidden" name="idx"    value=""    id="formIdx">
    <div class="form-row">
      <div>
        <label>Company Name *</label>
        <input type="text" name="name" id="fName" required placeholder="Acme Corp">
      </div>
      <div>
        <label>Category</label>
        <input type="text" name="category" id="fCategory" placeholder="Technology">
      </div>
    </div>
    <div class="form-row full">
      <div>
        <label>Website URL *</label>
        <input type="url" name="url" id="fUrl" required placeholder="https://example.com">
      </div>
    </div>
    <div class="form-row full">
      <div>
        <label>Short Description</label>
        <textarea name="description" id="fDesc" placeholder="One or two sentences about this company (shown as the card text)…"></textarea>
      </div>
    </div>
    <div style="display:flex;gap:10px;align-items:center">
      <button class="btn-primary" type="submit">Save Company</button>
      <button class="btn-sm" type="button" onclick="resetForm()">Cancel / Clear</button>
    </div>
  </form>
</div>

<!-- ── company list ── -->
<div class="card">
  <h2>&#128203; Current Company Cards (<?= count($sponsors) ?>)</h2>
  <?php if (!$sponsors): ?>
    <p class="empty">No companies yet &mdash; add one above.</p>
  <?php else: ?>
    <div id="companyList">
    <?php foreach ($sponsors as $i => $s): ?>
      <div class="company-row">
        <div class="company-info">
          <strong><?= htmlspecialchars($s['name'] ?? '') ?></strong>
          <div class="url"><a href="<?= htmlspecialchars($s['url'] ?? '') ?>" target="_blank" rel="noopener"><?= htmlspecialchars($s['url'] ?? '') ?></a></div>
          <?php if (!empty($s['description'])): ?>
            <div style="font-size:13px;color:#4b5563;margin-top:4px"><?= htmlspecialchars($s['description']) ?></div>
          <?php endif; ?>
          <?php if (!empty($s['category'])): ?>
            <span class="cat"><?= htmlspecialchars($s['category']) ?></span>
          <?php endif; ?>
        </div>
        <div class="company-actions">
          <!-- move up/down -->
          <?php if ($i > 0): ?>
            <form method="post" style="display:inline">
              <input type="hidden" name="csrf"   value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="action" value="move">
              <input type="hidden" name="idx"    value="<?= $i ?>">
              <input type="hidden" name="dir"    value="up">
              <button class="btn-sm btn-move" title="Move up">&#8593;</button>
            </form>
          <?php endif; ?>
          <?php if ($i < count($sponsors) - 1): ?>
            <form method="post" style="display:inline">
              <input type="hidden" name="csrf"   value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="action" value="move">
              <input type="hidden" name="idx"    value="<?= $i ?>">
              <input type="hidden" name="dir"    value="down">
              <button class="btn-sm btn-move" title="Move down">&#8595;</button>
            </form>
          <?php endif; ?>
          <!-- edit -->
          <button class="btn-sm btn-edit"
            onclick="editCompany(<?= $i ?>, <?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)">
            &#9998; Edit
          </button>
          <!-- delete -->
          <form method="post" style="display:inline"
            onsubmit="return confirm('Delete \'<?= addslashes(htmlspecialchars($s['name'] ?? '')) ?>\'?')">
            <input type="hidden" name="csrf"   value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="idx"    value="<?= $i ?>">
            <button class="btn-sm btn-danger" type="submit">&#128465; Delete</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- ── change password ── -->
<div class="card">
  <h2>&#128273; Change Admin Password</h2>
  <form method="post" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
    <input type="hidden" name="csrf"   value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="action" value="chgpass">
    <div>
      <label>New Password (min 6 chars)</label>
      <input type="password" name="new_pass" style="width:220px" minlength="6">
    </div>
    <button class="btn-primary" type="submit">Update Password</button>
  </form>
</div>

<script>
function editCompany(idx, data) {
    document.getElementById('formTitle').textContent  = '✏️ Edit Company';
    document.getElementById('formAction').value       = 'edit';
    document.getElementById('formIdx').value          = idx;
    document.getElementById('fName').value            = data.name        || '';
    document.getElementById('fUrl').value             = data.url         || '';
    document.getElementById('fCategory').value        = data.category    || '';
    document.getElementById('fDesc').value            = data.description || '';
    document.getElementById('formCard').scrollIntoView({behavior: 'smooth'});
    document.getElementById('fName').focus();
}
function resetForm() {
    document.getElementById('formTitle').textContent  = '➕ Add New Company';
    document.getElementById('formAction').value       = 'add';
    document.getElementById('formIdx').value          = '';
    document.getElementById('companyForm').reset();
}
</script>

<?php endif; ?>
</div><!-- /adm-wrap -->
</body>
</html>
