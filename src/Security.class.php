<?php
/*//=================================
//
//	安全操作类 [更新时间: 2015-7-28]
//
//===================================*/

class Security
{
	
	//字符串安全 - 处理引号
	//支持多维数组遍历
	//参数: $type 1 对引号进行转义, -1 对已经转义的引号进行反转义
	public static function quote($data,$type=1)
	{
		return Iterator($data,(int)$type==-1?'stripslashes':'addslashes');
	}
	
	//处理页面的数据,防止js注入
	//支持多维数组遍历
	//参数: $type 1 将特殊字符转换为HTML编码, -1 将HTML编码的特殊字符转换回字符
	public static function html($data,$type=1)
	{
		return Iterator($data,(int)$type==-1?'htmlspecialchars_decode':'htmlspecialchars');
	}
	
   /**
   * DZ的 加密/解密
   * @param string $string 原文或者密文
   * @param string $operation 操作(ENCODE | DECODE), 默认为 DECODE
   * @param string $key 密钥
    * @param int $expiry 密文有效期, 加密时候有效， 单位 秒，0 为永久有效
    * @return string 处理后的 原文或者 经过 base64_encode 处理后的密文
    *
      * @example
      *
      *  $a = authcode('abc', 'ENCODE', 'key');
      *  $b = authcode($a, 'DECODE', 'key');  // $b(abc)
      *
      *  $a = authcode('abc', 'ENCODE', 'key', 3600);
      *  $b = authcode('abc', 'DECODE', 'key'); // 在一个小时内，$b(abc)，否则 $b 为空
      */
	public static function AuthCode($string,$operation='DECODE',$key='',$expiry=3600)
	{
		// 随机密钥长度 取值 0-32;
		// 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
		// 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
		// 当此值为 0 时，则不产生随机密钥
		$ckey_length = 8;

        if($operation == 'DECODE'){
            //解密时将 -号转回+号，_号转回/
            $string = str_replace(array('-','_'),array('+','/'),$string);
        }

         $key = md5(trim($key));
         $keya = md5(substr($key, 0, 16));
         $keyb = md5(substr($key, 16, 16));
         $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

         $cryptkey = $keya.md5($keya.$keyc);
         $key_length = strlen($cryptkey);

         $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
         $string_length = strlen($string);

         $result = '';
         $box = range(0, 255);

         $rndkey = array();
         for($i = 0; $i <= 255; $i++) {
             $rndkey[$i] = ord($cryptkey[$i % $key_length]);
         }

         for($j = $i = 0; $i < 256; $i++) {
             $j = ($j + $box[$i] + $rndkey[$i]) % 256;
             $tmp = $box[$i];
             $box[$i] = $box[$j];
             $box[$j] = $tmp;
         }

         for($a = $j = $i = 0; $i < $string_length; $i++) {
             $a = ($a + 1) % 256;
             $j = ($j + $box[$a]) % 256;
             $tmp = $box[$a];
             $box[$a] = $box[$j];
             $box[$j] = $tmp;
             $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
         }

         if($operation == 'DECODE') {
             if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
                 return substr($result, 26);
             } else {
                 return '';
             }
         } else {
             $string = $keyc.str_replace('=', '', base64_encode($result));

             //加密时将 +号转为-号，/号转成_号, 以便于在url中传输
             return str_replace(array('+','/'),array('-','_'),$string);
         }

     }
	
}
