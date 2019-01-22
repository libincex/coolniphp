<?php
/*//=================================
//
//	Action控制器基类 抽象类 [更新时间: 2015-2-2]
//
//===================================*/

abstract class Action
{
	//挂钩点 - 前置事件
    protected function _before()
	{}
	
	//挂钩点 - 后置事件
    protected function _after($result=NULL)
	{
		//cli模式,数据返回
		CLN_IS_CLI && isset($result) && print_r($result);
	}
	
	//外部传入数据相关
	//=========================
	//取得外部传入的数据
	final protected function data($name='',$default=NULL)
	{
		return Router::data($name,$default);
	}
	
	//缓存
	//=========================
	//页面缓存(设置缓存时间,必须开启,并且配置了有效的缓存 (可用于http
	//参数: $time 缓存时间(单位秒) , 0 表示不缓存
	//private static $pageCahceKey = '';
	//private static $pageCahceTime = 0;
	final protected function pageCahce($time=0)
	{
		if(!CLN_IS_CLI){
			$time = (int)$time;
			if($time>1){
				//保存页面缓存时间
				$this->pageCahceTime = $time;
				//生成当前缓存页面的key
				$data = ksort($this->data());
				$this->pageCahceKey = 'PageCahce_'.md5(pURL('',$data));
				//获取缓存的页面内容
				$html = Cache($this->pageCahceKey);
				if(!empty($html)){
					//当前是否使用页面缓存的标记
					$this->usePageCache = true;
					//输出缓存的页面
					echo $html;
					exit;
				}
			}
		}
	}

    //安全
    //========================
    //针对IP进行当前路由访问的限制(一般应用场景: 用户触发的短信码下发，邮件下发等，需要进行限制，防止被攻击)
    //参数说明: 间隔限制(每$stime秒可以允许访问一次),并且 时间段总量限制(时间$time秒内，最多可以访问$maxNum次), 0表示不限制
    //返回：bool 是否可通过验证(如果返回false，则表示受到限制不能让程序继续往下执行，需要进行禁止相应的业务逻辑处理)
    final protected function IpPass($stime=0,$time=0,$maxNum=0)
    {
        $stime = (int)$stime;
        $time = (int)$time;
        $maxNum = (int)$maxNum;

        //获取数据
        $key = 'IpPass_'.ip().CLN_URI;
        $data = Cache($key);
        if(empty($data)){ //初始化
            $data = array(
                'startTime'=>time(), //第一次访问的时间
                'lastTime'=>0, //最后一次访问的时间
                'num'=>0, //总次数
            );
        }

        //1.间隔限制
        if($stime>0){
            if(time()-$data['lastTime']<$stime){
                return false;
            }
        }

        //2.时间段总量限制
        if($time>0 && $maxNum>0){
            if(time()-$data['startTime']<$time){
                if($data['num']>=$maxNum){
                    return false;
                }
            }else{
                $data['startTime'] = time(); //第一次访问的时间
                $data['num'] = 0; //总次数
            }
        }

        //保存
        $data['lastTime'] = time();
        $data['num']++;
        Cache($key,$data);

        return true;
    }
	
	//示图相关
	//=========================
	//模板数据
	final protected function value($values)
	{
		View::setValue($values);
	}
	//设置模板文件与数据
	final protected function display($tplFile='',$values=array())
	{
		View::setValue($values); //设置值
		empty($tplFile) && $tplFile = CLN_URI.'.php'; //如果模板参数为空,则设置默认模板

		return View::tpl($tplFile);
	}

	//魔术方法
	//=============================
	
	//方法重载,用来处理 访问框架的 http 404错误
	final function __call($name,$arguments)
    {
		Errors::stop('Not Found Function: '.CLN_MODULE.'/'.CLN_CONTROLLER."/{$name}");
    }

	//构造方法
	final function __construct()
	{
		//挂钩点 - 前置事件
		$this->_before();
	}

	//析构方法
	final function __destruct()
	{
		//填充js与css (仅http模式才有效)
		if(!CLN_IS_CLI){
			//输出页面
			$html = View::_show();
			//缓存页面
			if(empty($this->usePageCache) //未输出缓存
				&& !empty($this->pageCahceTime) //缓存时间有效
				&& !empty($this->pageCahceKey) //缓存key有效
				&& !empty($html)){ //内容不为空
				Cache($this->pageCahceKey,$html,$this->pageCahceTime);
			}
		}
		
		//挂钩点 - 后置事件
		$this->_after($this->__Result);
	}
	
}

?>