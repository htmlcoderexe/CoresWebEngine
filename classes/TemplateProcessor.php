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
        
        
        private $pointer;
        private $chars;
        private $charslen;
        private $stacklevel;
        private $parsetree=[];
        private $currentnode;
        private $terminator_stack=[];
        private $last_terminator="";
        
        private $mode;
        private $output;
        
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
	
	
        
        function process_node($node)
        {
            $nodetype=$node['type'];
            switch($nodetype)
            {
                case "literal":
                {
                    return $node;
                }
                case "variable":
                {
                    return $this->process_var($node);
                }
                case "variable_default":
                {
                    return $this->process_var_default($node);
                }
                case "template":
                {
                    return $this->process_template($node);
                }
                case "template_func":
                {
                    return $this->process_template_func($node);
                }
                case "builtin":
                {
                    return $this->process_builtin($node);
                }
            }
        }
        
        function process_nodelist($nodelist)
        {
            $result="";
            foreach($nodelist as $node)
            {
                $output=$this->process_node($node);
                $result.=$output['stringval'];
            }
            return $result;
        }
        
        function process_var($node)
        {
            $name = $this->process_nodelist($node['varname']);
            return ["type"=>"literal","stringval"=>$this->tokens[$name]];
            
        }
        
        function process_var_default($node)
        {
            $name = $this->process_nodelist($node['varname']);
            $default = $this->process_nodelist($node['default']);
            if(!isset($this->tokens[$name]) || $this->tokens[$name]==="")
            {
                return ["type"=>"literal","stringval"=>$default];
            }
            return ["type"=>"literal","stringval"=>$this->tokens[$name]];
        }
        
        function process_template($node)
        {
            $template = $this->process_nodelist($node['template_name']);
            $params =[];
            foreach($node['params'] as $param)
            {
                $paramname = $this->process_nodelist($param['name']);
                $paramvalue = $this->process_nodelist($param['value']);
                $params[$paramname]=$paramvalue;
            }
            $t = new TemplateProcessor($template);
            $t->tokens=$params;
            return ["type"=>"literal","stringval"=>$t->do_new_parser()];
        }
        
        function process_template_func($node)
        {
            $output="";
            $func_name = $this->process_nodelist($node['template_func_name']);
            
            $params =[];
            foreach($node['params'] as $param)
            {
                $params[]=$this->process_nodelist($param);
            }
            $ffname=($this->isLayout?$this->layoutsdir:$this->tplprefix).$this->fname.$this->tplfpostfix;
		if(!file_exists($ffname)) //if it ain't there, what's the point anyway
                {
                    EngineCore::Write2Debug("couldn't load \"$ffname\"<br />");
                     return ["type"=>"literal","stringval"=>""];
                }
                require_once($ffname);
            if(count($params)>0)
            {
                $output=call_user_func_array($this->tplfuncprefix."_".basename($this->fname)."_".$func_name,$params);
            }
            else
            {
                $output=call_user_func($this->tplfuncprefix."_".basename($this->fname)."_".$func_name);
            }
            return ["type"=>"literal","stringval"=>$output];
        }
        function process_builtin($node)
        {
            $output="";
            $func_name = $this->process_nodelist($node['builtin_name']);
            
            $params =[];
            foreach($node['params'] as $param)
            {
                $params[]=$this->process_nodelist($param);
            }
            
            if(count($params)>0)
            {
                $output=call_user_func_array($this->builtinfuncprefix."_".$func_name,$params);
            }
            else
            {
                $output=call_user_func($this->builtintplfuncprefix."_".$func_name);
            }
            return ["type"=>"literal","stringval"=>$output];
        }
        
        
        function do_new_parser()
        {
            
            ini_set("xdebug.var_display_max_depth",-1);
            $output="";
            $this->chars=preg_split('//u', $this->cb, -1, PREG_SPLIT_NO_EMPTY);
            $this->pointer=0;
            $this->len = count($this->chars);
            
            
            $nodes = $this->get_nodelist();
            $output= $this->process_nodelist($nodes);
            //echo $output;
            //var_dump($nodes);
            //die();
            
            return $output;
        }
        
        function peekChar($offset=0)
        {
            return $this->chars[$this->pointer+$offset];
        }
        
        function canGetChars($amount=1)
        {
            return $this->pointer+$amount < $this->len;
        }
        
        function getChars($amount=1)
        {
            $output="";
            while($this->pointer+1<$this->len && $amount > 0)
            {
                $this->pointer++;
                $output.=$this->peekChar();
            }
            return "";
        }
        
        
        function get_nodelist()
        {
            $list=[];
            $current_node=[
                "type"=>"literal",
                "stringval"=>""
            ];
            
            while($this->pointer<$this->len)
            {
                $current = $this->chars[$this->pointer];
                switch($current)
                {
                    // end current nodelist, but do not go up a level yet
                    case "|":
                    {   // only if not top level obviously
                        if(count($this->terminator_stack)>0)
                        {          
                            $list[]=$current_node;
                            $this->pointer++;
                            $this->last_terminator="|";
                            return $list;
                        }
                        // else output literal "|"
                        else
                        {
                            $this->pointer++;
                            $current_node['stringval'].=$current;
                            break;
                        }
                    }
                    // end current nodelist, but do not go up a level yet
                    case "=":
                    {   // only if not top level obviously, and only if reading template args
                        if(count($this->terminator_stack)>0 && end($this->terminator_stack)=="}")
                        {          
                            $list[]=$current_node;
                            $this->pointer++;
                            $this->last_terminator="=";
                            return $list;
                        }
                        // else output literal "="
                        else
                        {
                            $this->pointer++;
                            $current_node['stringval'].=$current;
                            break;
                        }
                    }
                    
                    // these try to start shit
                    case "{":
                    {
                        //echo "saw {";
                        // check if there's more
                        if($this->canGetChars())
                        {
                            $newnodechar=$this->peekChar(1);
                            //echo $newnodechar;
                            switch($newnodechar)
                            {
                                case "%":
                                {
                                    array_push($this->terminator_stack,"%");
                                    $list[]=$current_node;
                                    $this->pointer++;
                                    $this->pointer++;
                                    $current_node =[];
                                    $varname=$this->get_nodelist();
                                    // check if it was var or vardefault
                                    $current_node['type']="variable";
                                    $current_node['varname']=$varname;
                                    if($this->last_terminator=="|")
                                    {
                                        $this->last_terminator="";
                                        $default=$this->get_nodelist();
                                        $current_node['default']=$default;
                                        $current_node['type']="variable_default";
                                    }
                                    $list[]=$current_node;
                                    $current_node=[
                                       "type"=>"literal",
                                        "stringval"=>""
                                    ];
                                    break;
                                }
                                case "#":
                                {
                                    array_push($this->terminator_stack,"#");
                                    $list[]=$current_node;
                                    $this->pointer++;
                                    $this->pointer++;
                                    $current_node =[];
                                    $funcname=$this->get_nodelist();
                                    // check if it was var or vardefault
                                    $current_node['type']="builtin";
                                    $current_node['builtin_name']=$funcname;
                                    $current_node['params']=[];
                                    while($this->last_terminator=="|")
                                    {
                                        $this->last_terminator="";
                                        $current_node['params'][]=$this->get_nodelist();
                                    }
                                    $list[]=$current_node;
                                    $current_node=[
                                       "type"=>"literal",
                                        "stringval"=>""
                                    ];
                                    break;
                                }
                                case "$":
                                {
                                    array_push($this->terminator_stack,"$");
                                    $list[]=$current_node;
                                    $this->pointer++;
                                    $this->pointer++;
                                    $current_node =[];
                                    $funcname=$this->get_nodelist();
                                    // check if it was var or vardefault
                                    $current_node['type']="template_func";
                                    $current_node['template_func_name']=$funcname;
                                    $current_node['params']=[];
                                    while($this->last_terminator=="|")
                                    {
                                        $this->last_terminator="";
                                        $current_node['params'][]=$this->get_nodelist();
                                    }
                                    $list[]=$current_node;
                                    $current_node=[
                                       "type"=>"literal",
                                        "stringval"=>""
                                    ];
                                    break;
                                }
                                case "{":
                                {
                                    // template ends with }}
                                    array_push($this->terminator_stack,"}");
                                    $list[]=$current_node;
                                    $this->pointer++;
                                    $this->pointer++;
                                    $current_node =[];
                                    $funcname=$this->get_nodelist();
                                    // check if it was var or vardefault
                                    $current_node['type']="template";
                                    $current_node['template_name']=$funcname;
                                    $current_node['params']=[];
                                    $expect_param_value=false;
                                    $current_param=[];
                                    while($this->last_terminator=="|" || $this->last_terminator=="=")
                                    {
                                        $current_thing=$this->get_nodelist();
                                        //$current_node['params'][]=$this->get_nodelist();
                                        if($this->last_terminator == "=")
                                        {
                                        //$this->last_terminator="";
                                            $current_param['name']=$current_thing;
                                            $expect_param_value=true;
                                        }
                                        else
                                        {
                                        //$this->last_terminator="";
                                            $current_param['value']=$current_thing;
                                            $expect_param_value=false;
                                        }
                                        if(!$expect_param_value)
                                        {
                                          $current_node['params'][]=$current_param;
                                        }
                                    }
                                    $list[]=$current_node;
                                    $current_node=[
                                       "type"=>"literal",
                                        "stringval"=>""
                                    ];
                                    break;
                                }
                                //
                                default:
                                {
                                    $this->pointer++;
                                    $current_node['stringval'].=$current;
                                    break;
                                }
                            }
                            
                        }
                        // else emit a literal "{"
                        else
                        {
                            $this->pointer++;
                            $current_node['stringval'].=$current;
                        }
                        break;
                    }
                    // an "\" is an escape
                    case "\\":
                    {
                        // check if next char exists
                        if($this->canGetChars())
                        {
                            // increment pointer, output next char as literal
                            $this->pointer++;
                            $current_node['stringval'].=$this->chars[$this->pointer];
                            $this->pointer++;
                        }
                        else
                        {
                            // output a literal "\";
                            $this->pointer++;
                            $current_node['stringval'].=$current;
                        }
                        break;
                    }
                    // check if a token ends, else just output
                    default:
                    {
                        $current_terminator=end($this->terminator_stack);
                        
                        // check if there's a } next and the current char matches the currently opened token
                        if($current_terminator == $current && $this->canGetChars() && $this->peekChar(1) == "}")
                        {
                            //echo " saw ";
                           // echo $current;
                           // echo "}";
                            // end current node and put into list
                            $list[]=$current_node;
                            // remove from stack
                            array_pop($this->terminator_stack);
                            // return the nodelist
                            $this->pointer++;
                            $this->pointer++;
                            $this->last_terminator=$current_terminator;
                           // echo "returning list";
                            //var_dump($list);
                            return $list;
                        }
                        else
                        {
                            $this->pointer++;
                            $current_node['stringval'].=$current;
                        }
                        break;
                    }
                }
                
            }
            $list[]=$current_node;
            return $list;
        }    
        
        
    function process($silence = false, $autoreset = true)
    {
        if($autoreset)
        {
            $this->reset();
        }
        if(!$silence)
        {
            if(EngineCore::$DEBUG)
            {
                echo "\r\n<!-- " . $this->tplprefix . $this->fname . $this->tplpostfix . "-->\r\n";
            }
            echo $this->do_new_parser();
            echo "\r\n<!-- end of " . $this->tplprefix . $this->fname . $this->tplpostfix . "-->\r\n";
        }
        else
        {
            if(EngineCore::$DEBUG)
            {
                return "\r\n<!-- " . $this->tplprefix . $this->fname . $this->tplpostfix . "-->\r\n" . $this->do_new_parser() . "\r\n<!-- end of " . $this->tplprefix . $this->fname . $this->tplpostfix . "-->\r\n";
            }
            return $this->do_new_parser();
        }
    }
}