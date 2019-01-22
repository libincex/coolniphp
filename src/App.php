<?php
/*//=================================
//
//	应用项目类 [更新时间: 2014-3-14]
//
//===================================*/

//设置内部字符编码为utf8
mb_internal_encoding('UTF-8');

//当前php的运行方式："http"或"cli"
define('CLN_METHOD',(strtolower(PHP_SAPI)==='cli' || defined('STDIN'))?'cli':'http');
define('CLN_IS_CLI',CLN_METHOD=='cli');

//取得入口文件名
define('CLN_ROOT_FILE',basename($_SERVER['SCRIPT_NAME']));

class App
{

	//构造函数
	final function __construct($config=array())
	{
		if(!empty($config)){
            //自动载入model类
            function_exists('__autoload') && spl_autoload_register('__autoload'); //如果__autoload存在,则进行显式注册
            spl_autoload_register('App::autoLoad'); //注册框架的autoLoad

            //初始化配置信息
            Config::init($config);

			$this->init();
		}
	}
	
	//通过配置信息进行初始化操作
	public function init()
	{
        //初始化日志
        Log::init();

        //初始化调试模式
        Debug::init();

        //初始化路由解析
        Router::init();

        //初始化文件存储
        File::init();

        if(CLN_IS_CLI){ //cli模式
            //初始化crontab任务
            Crontab::init();
        }else{ //http模式
            //初始化cookie
            Cookie::init();

            //初始化session
            Session::init();
        }
	}
	
	//运行
	public function play()
	{
        //检测受保护的方法
        if(in_array(CLN_ACTION,array('_before','_after'))) {
			Errors::stop('Not Found Function: ' . CLN_MODULE . '/' . CLN_CONTROLLER . "/" . CLN_ACTION);
        }

		//取得参数
		$className = CLN_CONTROLLER.'Action'; //取得类名
		$cpath = C('controllerPath').'/'.CLN_MODULE."/{$className}.class.php"; //控制器类文件所在路径

        //载入控制器类文件
		$isbeing = include(realpath($cpath));
		if($isbeing===false){
			return Errors::stop("Not Found File {$cpath}"); //控制器文件不存在，则输出错误
		}
		if(!class_exists($className)){
			return Errors::stop('Not Found Class '.CLN_MODULE."/{$className}"); //控制器类名不存在
		}

		//执行程序
		try{
            $actionName = CLN_ACTION; //取得方法名
			$Obj = new $className(); //实例化控制器对象
			$Obj->__Result = $Obj->$actionName(); //执行主体方法,取得执行结果
		}catch(Exception $e){
			return Errors::stop('Caught exception: '.$e->getMessage()); //执行错误处理
		}
		
	}
	
	//自动载入
	private static function autoLoad($class_name)
	{
		if(!class_exists($class_name)){
		
			//打包的发布版本已经包含了框架所有核心文件(发布版本在压缩打包时将自动加入当前日期来做为版本号)
			if(!defined('CoolniPHP_Version')){
				//输入公共函数库
				include_once(dirname(__FILE__)."/Common.function.php");
				
				//第1优先级,载入框架核心类库
				$path = dirname(__FILE__)."/{$class_name}.class.php";
				$isbeing = include($path);
				if($isbeing!==false){
					return;
				}
			}
			
			//第2 其它类库,按顺序从设定的lib路径中查找并载入
			foreach(C('libPath') as $libPath){
				if(empty($libPath)){
					continue;
				}
				$path = $libPath."/{$class_name}.class.php";
				$isbeing = include($path);
				if($isbeing!==false){
					return;
				}
			}
			
		}
	}
	
	//析构方法
	final function __destruct()
	{
		//调试信息
		Debug::sysinfo();
		
		//命令行模式下，最后输出一个换行
		if(CLN_IS_CLI){
			echo "\r\n";
		}
	}
}
?>