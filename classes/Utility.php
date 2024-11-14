<?php

class Utility
{
    const RANDOM_CHR_UPPER =0;
    const RANDOM_CHR_LOWER =1;
    const RANDOM_CHR_MIX =2;
    
	static function POST($var,$default="")
	{
		return isset($_POST[$var])?$_POST[$var]:$default;
	}

	static function GET($var,$default="")
	{
		return isset($_GET[$var])?$_GET[$var]:$default;
	}
	static function SetPageTitle($title)
	{
		global $_PAGE_TITLE;
		$_PAGE_TITLE=$title;
	}
	static function SetPageContent($content)
	{
		global $_PAGE_CONTENT;
		$_PAGE_CONTENT=$content;
		return;
	}
	static function AddPageContent($content)
	{
		global $_PAGE_CONTENT;
		$_PAGE_CONTENT.=$content; //the dot makes all the difference
		return;
	}
	static function RawModeOn()
	{
		global $_PAGE_RAW;
		$_PAGE_RAW=true;
	}
	static function PageSidebarAdd($header,$content,$headlink=null)
	{
		$box=new TemplateProcessor("sidebarbox");
		$box->tokens['header']=$header;
		$box->tokens['content']=$content;
		if($headlink!=null)
		{
			$box->tokens['headlink']=$headlink;
		}
		global $_PAGE_SIDEBAR;
		$_PAGE_SIDEBAR[]=$box->process(true);
	}
	static function debug($info)
	{
		global $_DEBUG_INFO;
		$_DEBUG_INFO.=$info."\r\n<br />";
	}
	static function MessageWarning($short,$long)
	{
		echo "<span class=\"internal_warning\"><strong>$short</strong> $long</span>\r\n";
	}
	static function FromWhenceYouCame()
	{
		//you shall remain
		$r=$_SERVER['HTTP_REFERER'];
		//until you are
		if(!headers_sent())
		{
			//complete again!
			header("Location: $r");
		}
		//NOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO
		echo "<a href=\"$r\">The redirect seems to fail. Go there yourself?</a>";	
	}
	static function GTFO($url)
	{
		if(!headers_sent())
		{
			header("Location: $url");
		}
		echo "<a href=\"$url\">Go to $url</a>";
	}
	static function var_dump_ob($var)
	{
		ob_start();
		var_dump($var);
		return ob_get_clean();
	}
	static function ddump($var)
	{
		Utility::debug(Utility::var_dump_ob($var));
	}
	static function SafeDivide($a,$b)
	{
		if($b==0)
			return 0;
		return $a/$b;
	}
	static function unixtohread($unix)
	{
		return date("d/m/y-H:i",((int)$unix));
	}
	static function hreadtounix($hread)
	{
		$r= DateTime::createFromFormat("d/m/y-H:i",$hread);
	//	var_dump($r);
	//die;
		return $r->getTimestamp();
		return date_create_from_format("d/m/y-H:i",$hread)->getTimestamp();
	}
	
	static function AddTemplate($template)
	{
		$t=new TemplateProcessor($template);
		Utility::AddPageContent($t->process(true));
	}

       
        
        public static function CreateRandomChar($mode)
        {
            $upper = false;
            if ($mode == self::RANDOM_CHR_UPPER)
            {
                $upper = true;
            }
            if($mode == self::RANDOM_CHR_MIX)
            {
                $upper = random_int(0,1) == 1;
            }
            $r=random_int(0,25);
            $c=$r+0x41;
            if(!$upper)
            {
                $c = $c | 0x20;
            }
            return chr($c);
        }
        public static function CreateRandomString($len,$mode)
        {
            $s="";
            for($i=0;$i<$len;$i++)
            {
                    $s.=self::CreateRandomChar($mode);
            }
            return $s;
        }
        public static function hfilesize($size)
        {
            $prefixes=array("","k","M","G","T");
            $pidx=0;
            while($size>1024)
            {
                if($pidx==4)
                {
                    break;
                }
                $size/=1024;
                $pidx++;
            }
            return number_format($size,2,"."," ")." ".$prefixes[$pidx];
        }
}
