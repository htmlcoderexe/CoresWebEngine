<?php
require_once("TemplateProcessor.functions.php");
class TemplateProcessor
{
	private $tplprefix="templates/";
        private $layoutsdir="layouts/";
	private $tplpostfix=".tpl";
	private $tplfpostfix=".f";
	private $tplfuncprefix="tpl";
	private $builtinfuncprefix="TemplateProcessorBuiltin";
	public $tokens;
	private $matches;
	private $matches_def;
	private $subtemplates;
	private $functioncalls;
	private $bfunctioncalls;
	private $contents;
	private $fname;
	private $cb;
        private $isLayout;
	protected $symbols=array('{','}','%');
	function __construct($file,$useLayout=false)
	{
            $this->isLayout=$useLayout;
		$this->tokens=Array();
		if(strpos($file,",")!==false)
		{
			$args=explode(",",$file);
			$file=array_shift($args);
			for($i=0;$i<count($args);$i++)
			{
				$kvp=explode("=",$args[$i],2);
				$this->tokens[$kvp[0]]=$kvp[1];
				//var_dump($this->tokens);
			}
			
		}
		
		$this->contents=file_get_contents(($this->isLayout?$this->layoutsdir:$this->tplprefix).$file.$this->tplpostfix) or die("File not found: $file");
		$this->cb=$this->contents;
                $this->fname=$file;
		//var_dump($this);
	}
	

	
	function dump()
	{
		echo $this->contents;
	}
	
	function reset()
	{
		$this->contents=$this->cb;
	}
	
	function process_variables_with_defaults()
	{
		preg_match_all("/(*UTF8){%[\w]{1,}[|][^%}]{0,}%}/u",$this->contents,$this->matches_def);
		
		$this->matches_def=$this->matches_def[0];
		for($i=0;$i<count($this->matches_def);$i++)
		{
			$key=substr($this->matches_def[$i],2,-2);
			list($key,$default)=explode("|",$key);
			if(!isset($this->tokens[$key])) 
			{ 
				$this->tokens[$key]=$default;
			}
			$this->contents=str_replace($this->matches_def[$i],$this->tokens[$key],$this->contents);
		}
	}
	
	 function process_variables()
	{
		preg_match_all("/(*UTF8){%[^\s|]{1,}%}/u",$this->contents,$this->matches);
		//preg_match_all("/(*UTF8){%[\w]{1,}[|][\w \d <>\"'=:;\\-\\[\\]\\/()]{1,}%}/",$this->contents,$this->matches_def);
		
		$this->matches=$this->matches[0];
		for($i=0;$i<count($this->matches);$i++)
		{
			$key=substr($this->matches[$i],2,-2);
			if(!isset($this->tokens[$key])) 
			{ 
				$this->tokens[$key]="Warning! Obligatory  parameter <strong>\"".$key."\"</strong> not specified.";
			}
			$this->contents=str_replace($this->matches[$i],$this->tokens[$key],$this->contents);
		}	
	}
	
	 function process_subtemplates()
	{
		$mc=1;
		while($mc!=0)
		{
		$mc=preg_match_all('/(*UTF8){{[^\{\}]{2,}}}/u',$this->contents,$this->subtemplates);
		$this->subtemplates=$this->subtemplates[0];
		for($i=0;$i<count($this->subtemplates);$i++)
		{
			$key=substr($this->subtemplates[$i],2,-2);
			$key=explode("|",$key);
			$subtpl=array_shift($key);
			$subtemplate=new TemplateProcessor($subtpl);
			for($j=0;$j<count($key);$j++)
			{
				$kvp=explode("=",$key[$j],2);
				$subtemplate->tokens[$kvp[0]]=$kvp[1];
				
			}
			$render=$subtemplate->process(true);
			$this->contents=str_replace($this->subtemplates[$i],$render,$this->contents);
		}
	}
	}
	
	 function process_template_functions()
	{
		$ffname=($this->isLayout?$this->layoutsdir:$this->tplprefix).$this->fname.$this->tplfpostfix;
		if(!file_exists($ffname)) //if it ain't there, what's the point anyway
                {
                    EngineCore::Write2Debug("couldn't load \"$ffname\"<br />");
                    return;
                }
                require_once($ffname);
		$this->fname=basename($this->fname);
		//echo "required $ffname";
		preg_match_all('/(*UTF8){\$[^\$}]{2,}\$}/u',$this->contents,$this->functioncalls);
		$this->functioncalls=$this->functioncalls[0];
		for($i=0;$i<count($this->functioncalls);$i++)
		{
			$key=substr($this->functioncalls[$i],2,-2); //shed tokens
			$key=explode("|",$key); //split
			$render="";
			$func=array_shift($key);
			if(count($key)>=1)
			{
			$render=call_user_func_array($this->tplfuncprefix."_".$this->fname."_".$func,$key);
			}
			else
			{
				$render=call_user_func($this->tplfuncprefix."_".$this->fname."_".$func);
			}
			$this->contents=str_replace($this->functioncalls[$i],$render,$this->contents);
		}
	}
	 function process_builtin_functions()
	{
		$mc=1;
		while($mc!=0)
		{
			$mc=preg_match_all('/(*UTF8){#[^\#}]{2,}\#}/u',$this->contents,$this->bfunctioncalls);
			$this->bfunctioncalls=$this->bfunctioncalls[0];
			for($i=0;$i<count($this->bfunctioncalls);$i++)
			{
				$key=substr($this->bfunctioncalls[$i],2,-2); //shed tokens
				$key=explode("|",$key); //split
				$render="";
				$func=array_shift($key);
				if(count($key)>=1)
				{
				$render=call_user_func_array($this->builtinfuncprefix."_".$func,$key);
				}
				else
				{
					$render=call_user_func($this->builtintplfuncprefix."_".$func);
				}
				$this->contents=str_replace($this->bfunctioncalls[$i],$render,$this->contents);
			}
		}
	}
	
	function process($silence=false,$autoreset=true)
	{
		global $_DEBUG;
		if($autoreset)
			$this->reset();
		//*/
		$this->process_variables_with_defaults();
		//$this->process_variables();
		$this->process_subtemplates();
		$this->process_builtin_functions();
		$this->process_variables_with_defaults();
		$this->process_template_functions();
		$this->process_subtemplates();
		if(!$silence)
		{
			if($_DEBUG)
				echo "\r\n<!-- ". $this->tplprefix.$this->fname.$this->tplpostfix."-->\r\n";
			$this->dump();
			echo "\r\n<!-- end of ". $this->tplprefix.$this->fname.$this->tplpostfix."-->\r\n";
		}
		else
		{
			if($_DEBUG)
			return "\r\n<!-- ". $this->tplprefix.$this->fname.$this->tplpostfix."-->\r\n".$this->contents."\r\n<!-- end of ". $this->tplprefix.$this->fname.$this->tplpostfix."-->\r\n";
		return $this->contents;
		}
	}
}