<?php
// BOLDYASE SYSTEM CONTROL CENTER V2
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ================== AUTO-FIXERS (LOGIC UNCHANGED) ==================
if (isset($_GET['fix'])) {
    if ($_GET['fix'] === 'uploads') {
        $upload_dir = __DIR__ . '/../uploads';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
            $msg = 'Uploads map aangemaakt!';
        } elseif (!is_writable($upload_dir)) {
            chmod($upload_dir, 0777);
            $msg = 'Uploads map nu schrijfbaar!';
        } else {
            $msg = 'Uploads map was al ok!';
        }
        header("Location: " . basename(__FILE__) . "?fixed=uploads&msg=" . urlencode($msg));
        exit();
    }
    if ($_GET['fix'] === 'include' && isset($_GET['file'])) {
        $file = basename($_GET['file']);
        $dir = __DIR__ . '/../includes/components/';
        $path = $dir . $file;
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        if (!file_exists($path)) {
            file_put_contents($path, "<?php\n// BOLDYASE COMPONENT: $file\n?>");
            $msg = "$file component aangemaakt!";
        } else {
            $msg = "$file bestaat al!";
        }
        header("Location: " . basename(__FILE__) . "?fixed=include&file=$file&msg=" . urlencode($msg));
        exit();
    }
}

// Testmail (LOGIC UNCHANGED)
if (isset($_GET['testmail'])) {
    $to = 'jouw@mailadres.nl'; // Zet hier je eigen e-mail!
    $ok = mail($to, 'Boldyase Testmail', 'Dit is een testmail vanuit jouw dashboard');
    header("Location: " . basename(__FILE__) . "?msg=" . urlencode($ok ? "Testmail verstuurd naar $to" : "Testmail gefaald naar $to"));
    exit();
}

// ================== REFACTORED HELPER FUNCTIONS ==================

/**
 * Genereert een enkele testregel. Returns HTML string.
 */
function testrule($title, $result, $msgOk = 'OK', $msgFail = 'FOUT', $action = '', $details = '') {
    $color = $result ? 'var(--success)' : 'var(--danger)';
    $icon  = $result ? '✅' : '❌';
    $statusTxt = $result ? $msgOk : $msgFail;
    $btn = $action ? "<div class='action-btn-wrap'>$action</div>" : '';
    
    $html = "<div class='syscheck-row'>
        <div class='syscheck-title'>$icon $title</div>
        <div class='syscheck-status' style='color:$color;'>$statusTxt</div>
        $btn
    </div>";
    
    if ($details) {
        $html .= "<div class='syscheck-detail'>$details</div>";
    }
    return $html;
}

/**
 * Helper to get ini values safely.
 */
function get_ini_value($setting) {
    $value = ini_get($setting);
    return ($value === false || $value === '') ? 'Niet beschikbaar' : $value;
}

/**
 * Renders the start of a card widget.
 */
function render_card_start($title, $icon = '⚙️') {
    return "<div class='card'>
            <div class='card-header'>$icon $title</div>
            <div class='card-body'>";
}

/**
 * Renders the end of a card widget.
 */
function render_card_end() {
    return "</div></div>";
}

$all_ok = true; // Global status tracker
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<title>BOLDYASE SYSTEM CONTROL CENTER V2</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --bg: #181a20;
    --card: #232532;
    --text: #e3e3e9;
    --text-muted: #8e94b8;
    --primary: #6366F1;
    --primary-hover: #4f52c4;
    --success: #34d399;
    --danger: #f87171;
    --gray: #64748b;
    --border: #303342;
    --shadow: 0 8px 25px -8px rgba(0,0,0,0.4);
    --radius: 16px;
}
body {
    background: var(--bg);
    color: var(--text);
    font-family: 'Inter', sans-serif;
    margin: 0;
    padding: 24px;
}
.main-header {
    text-align: center;
    font-weight: 700;
    font-size: 2.2rem;
    margin: 16px 0 32px;
    letter-spacing: .02em;
}
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 24px;
    max-width: 1400px;
    margin: 0 auto;
}
.card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
}
.card-header {
    font-size: 1.25em;
    font-weight: 600;
    padding: 16px 20px;
    background: rgba(0,0,0,0.1);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 10px;
}
.card-body {
    padding: 8px 20px 16px;
}
.syscheck-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
    font-size: 1.05em;
}
.card-body .syscheck-row:last-child { border-bottom: none; }
.syscheck-title {
    font-weight: 500;
    flex-grow: 1;
    display: flex;
    align-items: center;
    gap: 8px;
}
.syscheck-status {
    font-weight: 600;
    text-align: left;
    flex-shrink: 0;
    width: 150px;
}
.syscheck-detail {
    color: var(--gray);
    font-size: 0.9em;
    padding: 0 8px 8px 30px;
    border-bottom: 1px solid var(--border);
}
.action-btn-wrap {
    flex-shrink: 0;
}
.action-btn {
    display: inline-block;
    background: var(--primary);
    color: #fff;
    border-radius: 6px;
    padding: 5px 16px;
    font-weight: 500;
    text-decoration: none;
    font-size: 0.95em;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    transition: background 0.18s;
    cursor: pointer;
    border: none;
}
.action-btn:hover {
    background: var(--primary-hover);
    color: #fff;
}
.alert {
    border-radius: 8px;
    margin: 0 auto 24px;
    padding: 14px 18px;
    text-align: center;
    font-size: 1.1em;
    font-weight: 600;
    max-width: 1000px;
}
.success-alert {
    background: rgba(52, 211, 153, 0.1);
    color: var(--success);
    border: 1px solid var(--success);
}
.error-alert {
    background: rgba(248, 113, 113, 0.1);
    color: var(--danger);
    border: 1px solid var(--danger);
}
.log-viewer {
    background: #1e1f27;
    color: var(--text-muted);
    margin: 1em 0;
    padding: 16px;
    border-radius: 8px;
    font-size: .95em;
    max-height: 250px;
    overflow: auto;
}
.log-viewer b { color: var(--primary); }
.log-viewer pre {
    color: #c1c8de;
    overflow-x: auto;
    max-height: 200px;
    margin: 8px 0 0;
    white-space: pre-wrap;
}
.syscheck-footer {
    margin-top: 40px;
    text-align: center;
}
.syscheck-links {
    display: flex;
    gap: 12px;
    justify-content: center;
    margin: 18px 0 5px;
    flex-wrap: wrap;
}
.syscheck-linkbtn {
    background: var(--primary);
    color: #fff;
    border-radius: 9px;
    padding: 10px 23px;
    font-weight: 600;
    text-decoration: none;
    font-size: 1.1em;
    transition: background 0.18s;
}
.syscheck-linkbtn:hover { background: var(--primary-hover); }
@media (max-width: 600px) {
    body { padding: 8px; }
    .dashboard-grid { grid-template-columns: 1fr; }
    .main-header { font-size: 1.5rem; }
    .syscheck-row { flex-direction: column; align-items: flex-start; gap: 8px; }
    .syscheck-status { width: auto; }
}
</style>
</head>
<body>

<h2 class="main-header">BOLDYASE SYSTEM CONTROL CENTER</h2>

<div class="main-container">
    <?php
    if (isset($_GET['msg'])) {
        echo "<div class='alert success-alert'>".htmlspecialchars($_GET['msg'])."</div>";
    }
    ?>

    <div class="dashboard-grid">
        <?php
        // ================== CARD 1: SYSTEM & PHP INFO (NEW) ==================
        echo render_card_start('Systeem & PHP Informatie', '🖥️');
        
        testrule('PHP Versie', version_compare(phpversion(), '8.0', '>='), phpversion(), 'Verouderd (< 8.0)');
        testrule('Memory Limit', (int)get_ini_value('memory_limit') >= 128, get_ini_value('memory_limit'), 'Aanbevolen: >=128M');
        testrule('Max Execution Time', (int)get_ini_value('max_execution_time') >= 30, get_ini_value('max_execution_time') . 's', 'Aanbevolen: >=30s');
        testrule('Upload Max Filesize', (int)get_ini_value('upload_max_filesize') >= 8, get_ini_value('upload_max_filesize'), 'Aanbevolen: >=8M');
        testrule('Sessie Status', session_status() === PHP_SESSION_ACTIVE, 'Actief', 'Inactief');

        echo render_card_end();

        // ================== CARD 2: APPLICATION FILES ==================
        echo render_card_start('Applicatie Bestanden', '📁');

        $composerJson = file_exists(__DIR__.'/../composer.json');
        echo testrule('composer.json', $composerJson, 'Aanwezig', 'Ontbreekt', !$composerJson ? "<a class='action-btn' href='https://getcomposer.org/' target='_blank'>Info</a>" : '');
        
        $composerLock = file_exists(__DIR__.'/../composer.lock');
        echo testrule('composer.lock', $composerLock, 'Aanwezig', 'Ontbreekt', !$composerLock ? "<span class='action-btn' onclick='alert(\"Open terminal: composer install\")'>Fix</span>" : '');

        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            echo testrule('Composer Autoload', true, 'OK', 'FOUT');
        } catch (Throwable $e) {
            echo testrule('Composer Autoload', false, 'FOUT', 'FOUT', '', $e->getMessage());
            $all_ok = false;
        }

        $upload_dir = __DIR__ . '/../uploads';
        $writable = is_dir($upload_dir) && is_writable($upload_dir);
        $action = !$writable ? "<a class='action-btn' href='?fix=uploads'>Fix</a>" : '';
        echo testrule('Uploads map schrijfbaar', $writable, 'Schrijfbaar', 'Niet schrijfbaar', $action);

        echo render_card_end();

        // ================== CARD 3: DATABASE ==================
        echo render_card_start('Database', '🗃️');
        
        $pdo_check = false;
        try {
            require_once __DIR__ . '/../includes/db.php';
            $pdo_check = isset($pdo) && $pdo instanceof PDO;
            echo testrule('Database Connectie', $pdo_check, 'Verbonden', 'Gefaald');
            if ($pdo_check) {
                $result = $pdo->query("SELECT 1")->fetchColumn();
                echo testrule('Database Query', $result == 1, 'OK', 'Gefaald');
            } else {
                $all_ok = false;
            }
        } catch (Throwable $e) {
            echo testrule('Database Connectie', false, 'Gefaald', 'Gefaald', '', $e->getMessage());
            $all_ok = false;
        }

        if ($pdo_check) {
            try {
                $required = ['producten_new', 'users_new', 'meldingen_new'];
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($required as $tbl) {
                    $ok = in_array($tbl, $tables);
                    echo testrule("Tabel: `$tbl`", $ok, 'OK', 'Ontbreekt', !$ok ? "<a class='action-btn' href='../sql/sqldatabase_setup.sql' target='_blank'>Import</a>" : '');
                    if (!$ok) $all_ok = false;
                }
            } catch (Exception $e) {
                echo testrule("Database Tabellen Check", false, 'FOUT', 'FOUT', '', $e->getMessage());
            }
        }
        
        echo render_card_end();

        // ================== CARD 4: SERVICES & ENVIRONMENT ==================
        echo render_card_start('Services & Omgeving', '🌐');
        
        try {
            $dotenvLoaded = getenv('DB_HOST') || isset($_ENV['DB_HOST']);
            echo testrule('.env Geladen', $dotenvLoaded, 'Beschikbaar', 'Niet gevonden');
            echo testrule('DB_HOST Variabel', getenv('DB_HOST') ? true : false, getenv('DB_HOST') ?: 'OK', 'Ontbreekt');
        } catch (Throwable $e) {
            echo testrule('.env Geladen', false, 'FOUT', 'FOUT', '', $e->getMessage());
            $all_ok = false;
        }

        $apiOk = false; $advies = '';
        try {
            $apiUrl = "http://localhost:3000/api/suggestie";
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['x-api-key: JouwSterkeApiToken123!']);
            $response = curl_exec($ch);
            if ($response) {
                $data = json_decode($response, true);
                $apiOk = isset($data['advies']);
                $advies = $apiOk ? "Advies ontvangen: \"{$data['advies']}\"" : "Ongeldig antwoord van API.";
            } else {
                $advies = 'cURL error: ' . curl_error($ch);
            }
            curl_close($ch);
            $action = "<a class='action-btn' href='javascript:alert(\"Start node index.js in je projectmap!\")'>Info</a>";
            echo testrule('Node.js/AI-API', $apiOk, 'Bereikbaar', 'Niet bereikbaar', !$apiOk ? $action : '', $advies);
        } catch (Throwable $e) {
            echo testrule('Node.js/AI-API', false, 'FOUT', 'FOUT', '', $e->getMessage());
        }

        echo render_card_end();
        
        // ================== CARD 5: INCLUDES & COMPONENTS ==================
        echo render_card_start('Includes & Componenten', '🧩');
        
        $includes = [
            '/../includes/db.php', '/../includes/auth.php', '/../includes/ai_api.php',
            '/../includes/components/sidebar.php', '/../includes/components/dashboard-header.php',
            '/../includes/components/dashboard-notificaties.php', '/../includes/components/dashboard-stats.php',
            '/../includes/components/dashboard-performance.php', '/../includes/components/dashboard-queries.php',
        ];
        foreach ($includes as $inc) {
            $exists = file_exists(__DIR__ . $inc);
            $file = basename($inc);
            $action = !$exists ? "<a class='action-btn' href='?fix=include&file=$file'>Fix</a>" : '';
            echo testrule($file, $exists, 'OK', 'FOUT', $action);
            if (!$exists) $all_ok = false;
        }
        
        echo render_card_end();

        // ================== CARD 6: LOGS & INFO ==================
        echo render_card_start('Logs & Informatie', '📜');
        
        $log_file = __DIR__ . '/../logs/php_error.log';
        if (file_exists($log_file) && filesize($log_file) > 0) {
            $lines = array_slice(file($log_file), -20);
            echo "<div class='log-viewer'><b>Laatste PHP Errors:</b><pre>".htmlspecialchars(implode('', $lines))."</pre></div>";
        } else {
            echo testrule('PHP Error Log', true, 'Leeg of niet gevonden');
        }

        $changelog_file = __DIR__.'/../CHANGELOG.md';
        if (file_exists($changelog_file)) {
            $log = array_slice(file($changelog_file), 0, 15);
            echo "<div class='log-viewer'><b>Changelog:</b><pre>".htmlspecialchars(implode('', $log))."</pre></div>";
        }
        
        echo render_card_end();
        ?>
    </div>

    <div class="final-status">
        <?php
        echo "<hr style='border:0;border-top:1.5px solid var(--border);margin:40px 0;'>";
        if ($all_ok) {
            echo "<div class='alert success-alert'>🚀 Systeem OK – alle kritieke tests zijn succesvol uitgevoerd!</div>";
        } else {
            echo "<div class='alert error-alert'>❗ Er zijn problemen gevonden. Controleer de rode items hierboven en gebruik de 'Fix' knoppen.</div>";
        }
        ?>
    </div>

    <div class="syscheck-footer">
        <div class="syscheck-links">
            <a class="syscheck-linkbtn" href="dashboard.php" target="_blank">Dashboard</a>
            <a class="syscheck-linkbtn" href="../public/database_test.php" target="_blank">Database Test</a>
            <a class="syscheck-linkbtn" href="../" target="_blank">Homepage</a>
            <a class="syscheck-linkbtn" href="https://github.com/jouwnaam/boldyase" target="_blank">GitHub</a>
            <a class="syscheck-linkbtn" href="?testmail=1">Stuur Testmail</a>
        </div>
        <div style="color:var(--text-muted);font-size:0.97em;margin-top:20px;">
            <span>Boldyase System Control &copy; <?= date('Y') ?> – AI Ops Manager</span>
        </div>
    </div>
</div>
</body>
</html>
