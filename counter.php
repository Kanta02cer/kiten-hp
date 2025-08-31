<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// カウンターファイルのパス
$counterFile = 'visit_count.txt';
$lockFile = 'counter.lock';

// ロックファイルを使用して同時アクセスを防ぐ
function acquireLock() {
    global $lockFile;
    $lock = fopen($lockFile, 'w+');
    if (flock($lock, LOCK_EX | LOCK_NB)) {
        return $lock;
    }
    fclose($lock);
    return false;
}

function releaseLock($lock) {
    if ($lock) {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

// 現在の訪問数を取得
function getCurrentCount() {
    global $counterFile;
    if (file_exists($counterFile)) {
        return (int)file_get_contents($counterFile);
    }
    return 67; // 初期値
}

// 訪問数を増加
function incrementCount() {
    global $counterFile;
    $currentCount = getCurrentCount();
    $newCount = $currentCount + 1;
    file_put_contents($counterFile, $newCount);
    return $newCount;
}

// メイン処理
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // 訪問数を増加
    $lock = acquireLock();
    if ($lock) {
        try {
            $newCount = incrementCount();
            echo json_encode([
                'success' => true,
                'count' => $newCount,
                'message' => '訪問数が更新されました'
            ]);
        } finally {
            releaseLock($lock);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'ロックを取得できませんでした'
        ]);
    }
} else {
    // 現在の訪問数を取得
    $currentCount = getCurrentCount();
    echo json_encode([
        'success' => true,
        'count' => $currentCount,
        'message' => '現在の訪問数を取得しました'
    ]);
}
?>
