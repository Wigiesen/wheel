<?php
/**
 * 中文化PHP编程
 * @author [Wigiesen] <[<wigiesen.cn@gmail.com>]>
 */
class cnPHP
{
    private static $reserved = ['abstract','and','array',' as ','break','case','catch','class','clone','const','continue','declare','default','die','do','echo','else','elseif','empty','enddeclare','endfor','endforeach','endif','endswitch','endwhile','eval','exit','extends','final','finally','for','foreach','function','global','goto','if','implements','include','include_once','instanceof','insteadof','interface','isset','list','namespace','new','or','print','private','protected','public','require','require_once','return','static','switch','throw','trait','try','unset','xor','yield', 'this'];
    private static $cn_reserved = [
        '抽象','和','数组','作为','跳出循环','条件','拦截异常','类','克隆','常量','继续循环','声明','默认','终止程序','执行','输出','其他','其他如果','为空','结束声明','结束循环','结束遍历','结束判断','结束选择器','结束循环体','执行代码','退出','继承','不可改变','最终异常','循环','遍历','函数','全局','转移','如果','约束接口','引入文件','引入文件一次','被实例化','替代实例化','接口','存在','数组赋值','命名空间','实例化类','或','打印输出','私有的','受保护的','公开的','加载文件','加载文件一次','返回','静态的','选择器','抛出','复用','尝试','删除变量','异或','生成器','本对象'
    ];
    public static function convertCode($file)
    {
        $file = file_get_contents($file);
        $file = str_replace(self::$reserved, self::$cn_reserved, $file);
        echo $file;
    }
    public static function evalCode($file)
    {
        $file = file_get_contents($file);
        $file = str_replace(self::$cn_reserved, self::$reserved, $file);
        eval($file);
    }
}

cnPHP::evalCode('./test.php');
//exaplme
$test = new 测试();
$test->设置姓名('王五');
$test->设置年龄('20');
$test->获取年龄姓名();