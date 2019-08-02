<?php
/**
 * Automated lookup of BUG for PHP
 * @author [心语难诉] <[<admin@xinyu19.com>]>
 */
class aloBug
{

	private static $errorInfo = [];

	/**
	 * [__construct - setting for set_error_handler]
	 */
	public function __construct(){
		set_error_handler('aloBug::errorInfo', E_ALL | E_STRICT);
	}

	/**
	 * [errorInfo - print the errors]
	 * @param  [type] $error_no      [description]
	 * @param  [type] $error_message [description]
	 * @param  [type] $error_file    [description]
	 * @param  [type] $error_line    [description]
	 * @return [type]                [description]
	 */
	public static function errorInfo($error_no, $error_message ,$error_file, $error_line){
		self::$errorInfo = [
			'error_on'      => $error_no,
			'error_message' => $error_message,
			'error_file'    => $error_file,
			'error_line'    => $error_line
		];
		echo "<h1>WTF ? ERROR !!</h1><br>\r\n";
		$style = '<style type="text/css">
table.gridtable{font-family:verdana,arial,sans-serif;font-size:11px;color:#333333;border-width:1px;border-color:#666666;border-collapse:collapse;}table.gridtable th{border-width:1px;padding:8px;border-style:solid;border-color:#666666;background-color:#dedede;}table.gridtable td{border-width:1px;padding:8px;border-style:solid;border-color:#666666;background-color:#ffffff;}a{padding-right:10px;}</style>';
		$table = "<table class='gridtable'><tr><td>error_on</td><td>".self::$errorInfo['error_on']."</td></tr><tr><td>error_message</td><td>".self::$errorInfo['error_message']."</td></tr><tr><td>error_file</td><td>".self::$errorInfo['error_file']."</td></tr><tr><td>error_line</td><td>".self::$errorInfo['error_line']."</td></tr>";
		$table .= "<tr><td colspan='2' style='text-align:center;'>Fucking the BUG !</td></tr>";
		$table .= "<tr><td colspan='2' style='text-align:center;'>";
		$table .= "<a href='https://stackoverflow.com/search?q=".self::$errorInfo['error_message']."' target='_blank'>to Stackoverflow!</a>";
		$table .= "<a href='https://www.google.com/search?q=".self::$errorInfo['error_message']."' target='_blank'>to Google!</a>";
		$table .= "<a href='https://search.yahoo.com/search?p=".self::$errorInfo['error_message']."' target='_blank'>to Yahoo!</a>";
		$table .= "<a href='https://www.baidu.com/s?wd=".self::$errorInfo['error_message']."' target='_blank'>to Baidu!</a>";
		$table .= "</td></tr>";
		echo $style.$table;
		echo "</table>";
		die();
	}
}
new aloBug();

//exaplme
$arr = '';
foreach ($arr as $key => $value) {
	echo $value;
}