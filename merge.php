<?php

// 1
// merge();
// 2
// mergeFull();
// 3
// toJson();
// 4
// mergeDiff();exit;

// 1
// toKeyJson(); exit;

// 1
// toTree(); exit;

// 1
loadXml();

// 转为JSON数据
function toJson()
{
    $contents = file('merge.txt');
    $items = [];
    foreach ($contents as $line => $content) {
        $tmp = explode(' ', $content);
        if (isset($tmp['0']) && isset($tmp['1'])) {
            $code = trim($tmp['0']);
            $value = trim($tmp['1']);
            $items[$code] = $value;
        }
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

// 转为JSON数据（父ID做为索引）
function toKeyJson()
{
    $contents = file('2023_1.txt');
    $items = [];
    $codeItems = [];
    foreach ($contents as $line => $content) {
        $tmp = explode(' ', $content);
        if (isset($tmp['0']) && isset($tmp['1'])) {
            $code = trim($tmp['0']);
            $value = trim($tmp['1']);
            $items[$code] = $value;
            $codeItems[] = $code;
        }
    }

    $data = [];
    ksort($items);
    foreach ($items as $code => $name) {
        $p = substr($code, 0, 2);
        $pCode = $p . '0000'; // 省
        $c = substr($code, 2, 2);
        $cCode = $p . $c . '00'; // 市
        $a = substr($code, 4, 2);
        $aCode = $code; // 区
       if (substr($code, -4) === '0000') {
           $data[0][$pCode] = $name;
       } elseif (substr($code, -2) === '00') {
           $data[$pCode][$cCode] = $name;
       } elseif (is_numeric($code)) {
            if (in_array($cCode, $codeItems)) {
                $data[$cCode][$code] = $name;
            } else {
                $data[$pCode][$code] = $name;
            }
       } else {
           if (in_array($code, ['OLF', 'ANT', 'CAT', 'LAW', 'LAZ', 'TPA', 'CLN'])) {
                $data['820000'][$code] = $name;
            } else {
                $data['810000'][$code] = $name;
            }
       }
    }
    file_put_contents('key-json.json', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

// 转为树型结构
function toTree()
{
    $contents = file('2023_1.txt');
    $items = [];
    $codeItems = [];
    foreach ($contents as $content) {
        $tmp = explode(' ', $content);
        if (isset($tmp['0']) && isset($tmp['1'])) {
            $code = trim($tmp['0']);
            $value = trim($tmp['1']);
            $items[$code] = $value;
            $codeItems[] = $code;
        }
    }

    $data = [];
    $cItems = [];
    $aItems = [];
    ksort($items);
    foreach ($items as $code => $name) {
        $p = substr($code, 0, 2); // 省
        $pCode = $p . '0000';
        $c = substr($code, 2, 2); // 市
        $cCode = $p . $c . '00';
        $a = substr($code, 4, 2); // 区
        if (substr($code, -4) === '0000') {
           $data[$pCode] = [
                'id' => $pCode,
                'name' => $name
           ];
        } elseif (substr($code, -2) === '00') {
           $cItems[$pCode][$cCode] = [
                'id' => $cCode,
                'name' => $name
           ];
        } elseif (is_numeric($code)) {
           if (in_array($cCode, $codeItems)) {
               $aItems[$cCode][$code] = [
                    'id' => $code,
                    'name' => $name
               ];
           } else {
                $cItems[$pCode][$code] = [
                    'id' => $code,
                    'name' => $name
                ];
           }
        } else {
            if (in_array($code, ['OLF', 'ANT', 'CAT', 'LAW', 'LAZ', 'TPA', 'CLN'])) {
                $cItems['820000'][$code] = [
                    'id' => $code,
                    'name' => $name
                ];
            } else {
                $cItems['810000'][$code] = [
                    'id' => $code,
                    'name' => $name
                ];
            }
        }
    }

    $tmpItems = [];
    foreach ($cItems as $key => $cItem) {
        foreach ($cItem as $k => $v) {
            if (isset($aItems[$k])) {
                sort($aItems[$k]);
            }
            $tmpItems[$key][$k] = $v;
            $tmpItems[$key][$k]['children'] = $aItems[$k] ?? [];
        }
    }

    foreach ($data as $key => &$value) {
        if (isset($tmpItems[$key])) {
            sort($tmpItems[$key]);
        }
        $value['children'] = $tmpItems[$key] ?? [];
    }
    file_put_contents('tree.json', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

// 处理历年重复数据
function mergeFull()
{
    $contents = file('merge.txt');
    $items = [];
    foreach ($contents as $line => $content) {
        $tmp = explode(' ', $content);
        if (isset($tmp['0']) && isset($tmp['1'])) {
            $code = trim($tmp['0']);
            $value = trim($tmp['1']);
            $items[$code] = $code . ' ' . $value . PHP_EOL;
        }
    }
    ksort($items);
    file_put_contents('merge-full.txt', implode("", $items));
}

// 合并历年数据
function merge()
{
    $contents = '';
    for ($i = 1980; $i < 2024; $i++) {
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

// 来自QQ的 LocList.xml
function loadXml()
{
    $xml = simplexml_load_file('2023.xml');
    $data = [];
    foreach ($xml->children() as $k => $v) {
        $pCode = json_decode(json_encode($v->attributes()['Code']), true)[0];
        $tmp = [
            'id' => $pCode . '0000',
            'name' => json_decode(json_encode($v->attributes()['Name']), true)[0],
            'children' => []
        ];
        
        $city = [];
        foreach ($v->City as $a => $b) {
            $region = [];
            $cCode = str_pad(json_decode(json_encode($b->attributes()['Code']), true)[0], 2, '0', STR_PAD_LEFT);
            foreach ($b->children() as $c => $d) {
                $region[] = [
                    'id' => $pCode . $cCode . str_pad(json_decode(json_encode($d->attributes()['Code']), true)[0], 2, '0', STR_PAD_LEFT),
                    'name' => json_decode(json_encode($d->attributes()['Name']), true)[0]
                ];
            }
            $city[] = [
                'id' => is_numeric($cCode) ? ($pCode . $cCode . '00') : $cCode,
                'name' => json_decode(json_encode($b->attributes()['Name']), true)[0],
                'children' => $region
            ];
        }
        $tmp['children'] = $city;
        $data[] = $tmp;
    }
    file_put_contents('tree-xml.json', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}
