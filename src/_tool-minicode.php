<?php
//CoolniPHP框架的打包压缩工具

//文件列表
$files = scandir(dirname(__FILE__));

//打包合并
$code = '';
foreach ($files as $f) {
    if (!in_array($f, array('.', '..', '_tool-minicode.php'))) {
        $file = trim(dirname(__FILE__) . "/$f");
        if (!is_dir($file)) {
            $phpcode = php_strip_whitespace($file);
            $code .= $phpcode;
        }
    }
}

$code = trim(str_replace(array('?><?php', '<?php', '?>'), '', $code));
$date = date('Ymd');
$code = "<?php define('CLN_PHP_VER','{$date}'); {$code}";

//编码静态字符串
$data = [];
preg_match_all('/"([^"]+)"/is', $code, $data);
foreach ($data[1] as $d) {
    if (stripos($d, "\r\n") || stripos($d, "\n")) {
        $arr = [];
        foreach (array_map('trim', explode("\n", $d)) as $l) {
            $l !== '' && $arr[] = $l;
        }
        $str = implode('\n ', $arr);
        $code = str_replace($d, $str, $code);
    }
}

//生成文件
echo file_put_contents(dirname(__FILE__) . '/../CoolniPHP.php', $code);
