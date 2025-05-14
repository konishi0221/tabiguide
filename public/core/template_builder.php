<?php
/**
 * Template Builder
 *
 * Usage:
 *   echo buildTemplate('minpaku');                  // 空テンプレ JSON
 *   $arr = buildTemplate('hotel', $data, false);    // データ入り配列
 */

/**
 * @param string $type   template key (minpaku, ryokan, hotel, camp, ...)
 * @param array  $data   overlay data (optional)
 * @param bool   $asJson true: JSON string, false: PHP array
 * @return string|array
 */
function buildTemplate(string $type, array $data = [], bool $asJson = true)
{
    $path = __DIR__ . "/json/template_{$type}.json";
    if (!is_file($path)) {
        throw new RuntimeException("template not found: {$type}");
    }

    $tpl = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

    // merge overlay
    if ($data) {
        $tpl = _tplMerge($tpl, $data);
    }

    return $asJson
        ? json_encode($tpl, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        : $tpl;
}

/** Recursive merge helper */
function _tplMerge(array $tpl, array $data): array
{
    foreach ($data as $k => $v) {
        if (is_array($v) && isset($tpl[$k]) && is_array($tpl[$k])) {
            $tpl[$k] = _tplMerge($tpl[$k], $v);
        } else {
            $tpl[$k] = $v;
        }
    }
    return $tpl;
}
