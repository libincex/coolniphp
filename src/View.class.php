<?php
/*//=================================
//
//	示图操作类 [更新时间: 2014-3-14]
//
//===================================*/

class View
{
	private static $viewData = array(); //模板数据
	
	//设置模板数据
	//参数: $values['模板中变量名'] = 值;
	public static function setValue($data=array())
	{
		if(empty($data) || !is_array($data)){
			return;
		}
		//处理页面的数据,防止js注入
		self::$viewData = Security::html($data) + self::$viewData;
	}
	

	//载入模板文件
	//参数: $tplFile 模板文件的相对路径 $tplData 传递到子模板中的变量数组(此参数的使用场景：主模板中定义的变量需要在子模板中使用)
	// $isDisplay 是否显示, 默认为true,如果为false则仅返回渲染的模板内容
	public static function tpl($tpl,$tplData=array(),$isDisplay=true)
    {
		$tplFile = C('viewPath').'/'.ltrim(ltrim(trim($tpl),'/'),'\\');
		if($tplFile=='' || !is_file($tplFile)){
			Errors::show('Not Found Template '.$tplFile);
            return;
        }

        !is_array($tplData) && $tplData = array();

        //调试-记录模板处理时间
        $key = 'Template_'.uniqid('', true); //生成一个用于区分每个子模板的标识
        Debug::startTime($key,'模板处理 '.$tpl);

        //获取当前模板渲染之后的内容
        ob_start(); //开启输出缓冲
        extract($tplData + self::$viewData, EXTR_OVERWRITE | EXTR_REFS); //模板阵列变量分解成为独立变量
        include($tplFile); //载入模板
        $html = ob_get_clean();

        //调试-记录模板处理时间
        Debug::endTime($key);

        //输出内容
        if((bool)$isDisplay){
            echo $html;
        }

        return $html;
    }
	
	//载入静态文件 js
	private static $js = array();
	public static function loadJS($jsURL)
	{
		if(is_array($jsURL)){
			self::$js = array_merge(self::$js,$jsURL);
		}else{
			self::$js[] = $jsURL;
		}
	}
	
	//载入静态文件 css		
	private static $css = array();
	public static function loadCSS($cssURL)
	{
		if(is_array($cssURL)){
			self::$css = array_merge(self::$css,$cssURL);
		}else{
			self::$css[] = $cssURL;
		}
	}
	
	//最终渲染并输出页面
	//(先填充 静态文件css 与 js, 再输出页面)
	public static function _show()
	{
		$html = trim(ob_get_clean());
		if(!empty($html)){
			//填充载入css
			self::$css = array_unique(array_map('trim', self::$css));
			$cssHtml = '';
			foreach(self::$css as $css){
				if(!empty($css)){
					strtolower(substr($css,0,4))!='http' && $css = sURL($css);
					$cssHtml.= '<link type="text/css" href="'.$css.'" rel="stylesheet"/>'."\n";
				}
			}
			
			//填充载入js
			self::$js = array_unique(array_map('trim', self::$js));
			$jsHtml = '';
			foreach(self::$js as $js){
				if(!empty($js)){
					strtolower(substr($js,0,4))!='http' && $js = sURL($js);
					$jsHtml.= '<script src="'.$js.'"></script>'."\n";
				}
			}

            if(isAjax()){ //如果当前是ajax请求，则将js与css放入body内
                preg_match_all('/<body[^>]*>/is',$html,$arr);
                $body = trim($arr[0][0]);
                if(empty($body)){
                    $html = $cssHtml.$jsHtml.$html;
                }else{
                    $html = preg_replace('/'.preg_quote($body).'/is', $body."\n".$cssHtml.$jsHtml, $html,1);
                }
            }else {
                !empty($cssHtml) && $html = preg_replace('/<\/head>/i', $cssHtml . '</head>', $html, 1); //载入css
                !empty($jsHtml) && $html = preg_replace('/<\/body>/i', '</body>' . $jsHtml, $html, 1); //载入js
            }
		}
		
		//显示
		echo $html;
		//返回
		return $html;
	}
}
