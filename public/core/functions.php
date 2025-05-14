<?php
function l($ja, $en) {
  $language = ($_SESSION['lang'] == 'JP') ? $ja : (isset($_SESSION['lang']) ? $en : $en);
  print $language;
}

function dd($text) {
  var_dump($text);
  exit;
}

function random($num) {
  $str = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPUQRSTUVWXYZ';
  return substr(str_shuffle($str), 0, $num ?? 10);
}

function getAccessRole($pdo, $page_uid, $user_uid) {
    // オーナー判定
    $stmt = $pdo->prepare("SELECT * FROM facility_ai_data WHERE page_uid = ? AND user_uid = ? LIMIT 1");
    $stmt->execute([$page_uid, $user_uid]);
    if ($stmt->fetch()) return 'owner';

    // 共同管理者 or スタッフ判定
    $stmt = $pdo->prepare("SELECT managers_json FROM facility_ai_data WHERE page_uid = ? LIMIT 1");
    $stmt->execute([$page_uid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return false;

    $managers = json_decode($row['managers_json'], true);
    foreach ($managers as $manager) {
        if ($manager['uid'] === $user_uid) {
            return $manager['role']; // 'manager' or 'staff'
        }
    }

    return false; // アクセス権なし
}

/**
 * 画像をリサイズ＋回転補正＋PNG変換するヘルパー
 *
 * @param string $bin       元画像のバイナリ
 * @param int    $maxWidth  デフォルトの最大幅(px)。squareモード時は正方形の辺長として扱う
 * @param string $mode      'default' or 'square'
 * @return string           処理後のPNGバイナリ
 * @throws RuntimeException 読み込み失敗やEXIF未発見時
 */
function processImage(string $bin, int $maxWidth = 1200, string $mode = 'default'): string
{
    // --- EXIFから向き情報取得 ---
    $tmp = tempnam(sys_get_temp_dir(), 'img_');
    file_put_contents($tmp, $bin);
    $orientation = null;
    if (function_exists('exif_read_data')) {
        $exif = @exif_read_data($tmp);
        if (!empty($exif['Orientation'])) {
            $orientation = $exif['Orientation'];
        }
    }

    // --- GDでイメージ生成・回転補正 ---
    $src = @imagecreatefromstring($bin);
    if ($src === false) {
        @unlink($tmp);
        throw new RuntimeException('Invalid image data');
    }
    // 透過を維持するため透明色を背景に指定
    $transparent = imagecolorallocatealpha($src, 0, 0, 0, 127);
    switch ($orientation) {
        case 3: $src = imagerotate($src, 180, $transparent); break;
        case 6: $src = imagerotate($src, 270, $transparent); break;
        case 8: $src = imagerotate($src,  90, $transparent); break;
    }
    imagesavealpha($src, true); // 回転後に alpha を復元

    $w = imagesx($src);
    $h = imagesy($src);

    // --- モード別リサイズ／トリミング ---
    if ($mode === 'square') {
        // 正方形カバー：短辺を基準にリサイズ→中央クロップ
        $side  = $maxWidth;
        $ratio = $side / min($w, $h);
        $nw    = (int)($w * $ratio);
        $nh    = (int)($h * $ratio);
        $tmpImg = imagecreatetruecolor($nw, $nh);
        imagealphablending($tmpImg, false);     // 透過を有効に
        imagesavealpha($tmpImg, true);
        $transTmp = imagecolorallocatealpha($tmpImg, 0, 0, 0, 127); // 完全透明
        imagefill($tmpImg, 0, 0, $transTmp);

        imagecopyresampled($tmpImg, $src, 0,0, 0,0, $nw,$nh, $w,$h);

        $dst = imagecreatetruecolor($side, $side);
        // PNG用に透明背景を設定
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $trans = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefill($dst, 0, 0, $trans);
        $x = (int)(($nw - $side) / 2);
        $y = (int)(($nh - $side) / 2);
        imagecopy($dst, $tmpImg, 0,0, $x,$y, $side,$side);
        imagedestroy($tmpImg);

    } else {
        // デフォルト：幅だけリサイズ
        if ($w > $maxWidth) {
            $ratio = $maxWidth / $w;
            $nw    = $maxWidth;
            $nh    = (int)($h * $ratio);
            $dst   = imagecreatetruecolor($nw, $nh);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $trans = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefill($dst, 0, 0, $trans);
            imagecopyresampled($dst, $src, 0,0, 0,0, $nw,$nh, $w,$h);
        } else {
            // 幅内ならオリジナルをPNG互換にコピー
            $dst = imagecreatetruecolor($w, $h);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $trans = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefill($dst, 0, 0, $trans);
            imagecopy($dst, $src, 0,0, 0,0, $w,$h);
        }
    }

    // --- PNG出力 ---
    ob_start();
    imagepng($dst);
    $out = ob_get_clean();

    // --- 後片付け ---
    imagedestroy($dst);
    if ($mode !== 'default' || ($w > $maxWidth)) {
        imagedestroy($src);
    }
    @unlink($tmp);

    return $out;
}
