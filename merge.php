<?php

mergeDiff();

// 转为JSON数据
function toJson()
{
    $contents = file('merge-full.txt');
    $items = [];
    foreach ($contents as $line => $content) {
        $code = substr($content, 0, 6);
        $value = substr($content, 6);
        $items[$code] = trim($value);
    }
    
    $data = [];
    ksort($items);
    foreach ($items as $code => $name) {
        $p = substr($code, 0, 2); // 省
        $c = substr($code, 2, 2); // 市
        $pName = isset($items[$p . '0000']) ? $items[$p . '0000'] : '';
        $cName = isset($items[$p . $c . '00']) ? $items[$p . $c . '00'] : '';
        $value = [$pName, $cName, $name];
        $data[$code] = implode('', array_unique($value));
    }
    file_put_contents('merge.json', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

// 处理历年重复数据
function mergeFull()
{
    $contents = file('merge.txt');
    $items = [];
    foreach ($contents as $line => $content) {
        $code = substr($content, 0, 6);
        $value = substr($content, 6);
        $items[$code] = $code.$value;
    }
    file_put_contents('merge-full.txt', implode("", $items));
}

// 合并历年数据
function merge()
{
    $contents = '';
    for ($i = 1980; $i < 2022; $i++) {
        $contents .= file_get_contents($i . '.txt') . PHP_EOL;
    }
    file_put_contents('merge.txt', $contents);
}

// 合并市辖区数据
function mergeDiff()
{
    $diff = json_decode(file_get_contents('diff.json'), true);
    $data = json_decode(file_get_contents('merge.json'), true);
    foreach ($diff as $k => $v) {
        if (!isset($data[$k])) {
            $data[$k] = $v;
        }
    }
    ksort($data);
    file_put_contents('merge-full.json', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}
