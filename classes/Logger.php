<?php
// TODO update this for PDO
define("EVENT_LOG_TABLE","eventlog");
class Logger
{
    public const TYPE_ERROR = 1;
    public const TYPE_INFO = 2;
    public const TYPE_WARNING = 4;
    public const TYPE_CRITICAL = 8;
    public static function log($message,$type=0,$heading="")//,$time=0,$table=EVENT_LOG_TABLE)
    {
        $time = time();
        DBHelper::Insert(EVENT_LOG_TABLE,[null,$type,$message,$heading,$time]);
    }

	public static function format($log)
	{
		$fmtstring="<tr>
		<td>%s</td>
		<td>%s</td>
		<td>%s</td>
		<td>%s</td>
</tr>";
$acc1="";
$wh="<table>%s</table>";
		for($i=0;$i<count($log);$i++)
		{
			$acc1.=sprintf($fmtstring,
			$log[$i]['type'],
			$log[$i]['summary'],
			$log[$i]['message'],
			date("j F Y, g:i a",$log[$i]['time']));
		}
		return sprintf($wh,$acc1);
	}
	/*
	public static function fetchdata($query)
	{
		$data=Array();
		$res=mysql_query($query);
		for($i=0;$i<$c;$i++)
		{
			$row=mysql_fetch_assoc($res);
			$data[]=$row;
		}
		return $data;
	}
        //*/
}