<?php
require_once("TemplateProcessor.functions.php");
class TemplateProcessor
{
	private $tplprefix="templates/";
        private $layoutsdir="layouts/";
	private $tplpostfix=".tpl";
	private $tplfpostfix=".tpl.php";
	private $tplfuncprefix="TemplateFunction";
	private $builtinfuncprefix="TemplateProcessorBuiltin";
	private $contents;
	private $fname;
	private $cb;
        private $isLayout;
        
        
        private $pointer;
        private $chars;
        private $len;
        
        private $terminator_stack=[];
        private $last_terminator="";
        
        private $varstack=[];
        
        private $finalnodes;
        
	protected $symbols=array('{','}','%');
	
        
	public $tokens;
        
        public const EMPTY_NODE = [["type"=>"literal","stringval"=>""]];
        
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
	
        function dumpState()
        {
            ini_set("var_display_max_depth",-1);
            EngineCore::Dump2Debug($this->varstack);
            EngineCore::Dump2Debug($this->finalnodes);
        }
        
        function dumpStateNice()
        {
            return self::dump_tree_schematic($this->finalnodes,0,true);
        }
        
        static function CountNodeWeights($list)
        {
            $weight=0;
            foreach($list as $node)
            {
                $weight+=$node['weight']??1;
            }
            return $weight;
        }
        
        function push_data($data)
        {
            $this->varstack[]=$data;//$this->wrap_datatype($data);
        }
	
        
        private function process_node($node)
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
                case "var_bound":
                {
                    return $this->process_var_bound($node);
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
                case "row":
                case "list":
                case "table":
                {
                    $this->varstack[]=$node['elements'];
                    return ["type"=>"literal","stringval"=>""];
                }
            }
        }
        
        private function process_nodelist($nodelist)
        {
            $result="";
            foreach($nodelist as $node)
            {
                $output=$this->process_node($node);
                if(isset($output['elements']))
                {
                    $this->varstack[]=$output['elements'];
                }
                elseif(isset($output['stringval']) && is_scalar($output['stringval']))
                {
                    $result.=$output['stringval'];
                }
                else
                {
                    echo "fuck!!!!!";
                    var_dump($output);
                }
                
            }
            return $result;
        }
        
        private function process_var($node)
        {
            $name = $this->process_nodelist($node['varname']);
            if(!isset($this->tokens[$name]))
            {
                EngineCore::Write2Debug("Variable {%$name%} was not defined!!!!!!");
                $value="";
            }
            else
            {
                $value=$this->tokens[$name];
                
            }
            if(!is_scalar($value))
            {
                return $this->wrap_datatype($value);
            }
            return ["type"=>"literal","stringval"=>$value];
            
        }
        
        private function get_bound_var($name,$silent=false)
        {
            if(count($this->varstack)==0)
            {
                $silent or EngineCore::Write2Debug("Unexpected bound var &lt;".$name.">");
                return "";
            }
            $current=end($this->varstack);
            if( $name=="*")
            {
                return $current;
            }
            if(isset($current[$name]))
            {
                return $current[$name];
            }
            $silent or EngineCore::Write2Debug("Bound var &lt;".$name."> missing value");
            return "";
            
        }
        
        private function process_var_bound($node)
        {
            $name = $this->process_nodelist($node['varname']);
            return $this->wrap_datatype($this->get_bound_var($name));
        }
        
        private function process_var_default($node)
        {
            $name = $this->process_nodelist($node['varname']);
            $default = $this->process_nodelist($node['default']);
            if(!isset($this->tokens[$name]) || $this->tokens[$name]==="")
            {
                return ["type"=>"literal","stringval"=>$default];
            }
            return ["type"=>"literal","stringval"=>$this->tokens[$name]];
        }
        
        private function process_template($node)
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
        
        private function process_template_func($node)
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
            return $this->wrap_datatype($output);
        }
        private function process_builtin($node)
        {
            $output="";
            $func_name = $this->process_nodelist($node['builtin_name']);
            
            if($func_name == "foreach")
            {
                return $this->builtin_foreach($node);
            }
            if($func_name == "ifset")
            {
                return $this->builtin_ifset($node);
            }
            if($func_name == "if")
            {
                return $this->builtin_if($node);
            }
            
            
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
                $output=call_user_func($this->builtinfuncprefix."_".$func_name);
            }
            return $this->wrap_datatype($output);
        }
        /**
         * Check if a specific variable is set in the template and process the corresponding branch
         * @param array $node input ifset node
         * @return array The flattened node with either of the results
         */
        private function builtin_ifset($node)
        {
            // get the variable name
            $input = $this->process_nodelist($node['params'][0]);
            $evaluation=false;
            
            // check if it's in the set tokens and not empty
            if(isset($this->tokens[$input]) && $this->tokens[$input])
            {
                $evaluation=true;
            }
            // check if it's a bound variable (starting with : here to distinguish)
            elseif(isset($input[0]) && $input[0]==":" && $this->get_bound_var(substr($input,1)))
            {
                $evaluation=true;
            }
            // else false
            else
            {
                $evaluation=false;
            }
            // run either the truebranch or the falsebranch depending on the result
            $output=$evaluation ? $this->process_nodelist($node['params'][1]??TemplateProcessor::EMPTY_NODE):$this->process_nodelist($node['params'][2]??TemplateProcessor::EMPTY_NODE);
            // return the collapsed node
            return ["type"=>"literal","stringval"=>$output];
        }
        private function builtin_if($node)
        {
            $input = $this->process_nodelist($node['params'][0]);
            //EngineCore::Dump2Debug($input);
            if($input=="true")
            {
                $output = $this->process_nodelist($node['params'][1]);
            }
            else
            {
                $output = $this->process_nodelist($node['params'][2]);
            }
            return ["type"=>"literal","stringval"=>$output];
        }
        
        private function builtin_foreach($node)
        {
            $before=count($this->varstack);
            $input = $this->process_nodelist($node['params'][0]);
            $after=count($this->varstack);
            $output="";
            // keep hold of this
            $start = $this->pointer;
            if($after==0)
            {
                // no vars stacked
                $output="";
            }
            else
            {
                $data=end($this->varstack);
                $typed=$this->wrap_datatype($data);
                //var_dump($data);
                //var_dump($typed);
                switch($typed['type'])
                {
                    case "row":
                    {
                        $output = $this->process_nodelist($node['params'][1]);
                        array_pop($this->varstack);
                        break;
                    }
                    case "list":
                    case "table":
                    {
                        for($i=0;$i<count($data);$i++)
                        {
                            // be kind, rewind
                            $this->pointer=$start;
                            $this->varstack[]=$typed['elements'][$i];
                            $output.= $this->process_nodelist($node['params'][1]);
                            array_pop($this->varstack);
                        }
                        array_pop($this->varstack);
                        break;
                    }
                }
            }
            return ["type"=>"literal","stringval"=>$output];
        }
        
        private function wrap_datatype($output)
        {
            // decide whether to return a literal (single string),
            // a list (array of string), a row or a table
            if(is_array($output))
            {
                // list or table 
                if(isset($output[0]))
                {
                    // likely a table
                    if(is_array($output[0]))
                    {
                        return ["type"=>"table","elements"=>$output];
                    }
                    // maybe a table of objects?
                    elseif(is_object($output[0]))
                    {
                        $newtable = [];
                        foreach($output as $obj)
                        {
                            $newtable[]=(array)$obj;
                        }
                        return ["type"=>"table","elements"=>$newtable];
                    }
                    // definitely a list
                    else
                    {
                         return ["type"=>"list","elements"=>$output];
                    }
                } // no numerical keys, must be a row
                else
                {
                    return ["type"=>"row","elements"=>$output];
                }
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
            $this->finalnodes=$nodes;
            $output= $this->process_nodelist($nodes);
            //echo $output;
            //var_dump($nodes);
            //die();
            
            return $output;
        }
        
        private function peekChar($offset=0)
        {
            return $this->chars[$this->pointer+$offset];
        }
        
        private function canGetChars($amount=1)
        {
            return $this->pointer+$amount < $this->len;
        }
        
        private function getChars($amount=1)
        {
            $output="";
            while($this->pointer+1<$this->len && $amount > 0)
            {
                $this->pointer++;
                $output.=$this->peekChar();
            }
            return "";
        }
        
        
        private function get_nodelist()
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
                        if($this->canGetChars())
                        {
                            $newnodechar=$this->peekChar(1);
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
                                    $current_node['weight']=1;
                                    $current_node['weight']+=self::CountNodeWeights($varname);
                                    if($this->last_terminator=="|")
                                    {
                                        $this->last_terminator="";
                                        $default=$this->get_nodelist();
                                        $current_node['default']=$default;
                                        $current_node['type']="variable_default";
                                        $current_node['weight']+=self::CountNodeWeights($default);
                                    }
                                    $list[]=$current_node;
                                    $current_node=[
                                       "type"=>"literal",
                                        "stringval"=>""
                                    ];
                                    break;
                                }
                                case ":":
                                {
                                    array_push($this->terminator_stack,":");
                                    $list[]=$current_node;
                                    $this->pointer++;
                                    $this->pointer++;
                                    $current_node =[];
                                    $varname=$this->get_nodelist();
                                    $current_node['weight']=1;
                                    $current_node['weight']+=self::CountNodeWeights($varname);
                                    $current_node['type']="var_bound";
                                    $current_node['varname']=$varname;
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
                                    $current_node['weight']=1;
                                    $funcname=$this->get_nodelist();
                                    $current_node['weight']+=self::CountNodeWeights($funcname);
                                    $current_node['type']="builtin";
                                    $current_node['builtin_name']=$funcname;
                                    $current_node['params']=[];
                                    while($this->last_terminator=="|")
                                    {
                                        $this->last_terminator="";
                                        $current_node['params'][]=$this->get_nodelist();     
                                    }                               
                                    $current_node['weight']+=self::CountNodeWeights($current_node['params']);
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
                                    $current_node['weight']=1;
                                    $funcname=$this->get_nodelist();
                                    $current_node['weight']+=self::CountNodeWeights($funcname);
                                    
                                    $current_node['type']="template_func";
                                    $current_node['template_func_name']=$funcname;
                                    $current_node['params']=[];
                                    while($this->last_terminator=="|")
                                    {
                                        $this->last_terminator="";
                                        $current_node['params'][]=$this->get_nodelist();
                                    }
                                    $current_node['weight']+=self::CountNodeWeights($current_node['params']);
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
                                    $current_node['weight']=1;
                                    $funcname=$this->get_nodelist();
                                    $current_node['weight']+=self::CountNodeWeights($funcname);
                                    $current_node['type']="template";
                                    $current_node['template_name']=$funcname;
                                    $current_node['params']=[];
                                    $expect_param_value=false;
                                    $current_param=[];
                                    while($this->last_terminator=="|" || $this->last_terminator=="=")
                                    {
                                        $current_thing=$this->get_nodelist();
                                        if($this->last_terminator == "=")
                                        {
                                            $current_param['name']=$current_thing;
                                            $expect_param_value=true;
                                        }
                                        else
                                        {
                                            $current_param['value']=$current_thing;
                                            $expect_param_value=false;
                                        }
                                        if(!$expect_param_value)
                                        {
                                          $current_node['params'][]=$current_param;
                                          $current_node['weight']+=self::CountNodeWeights($current_param['name']);
                                          $current_node['weight']+=self::CountNodeWeights($current_param['value']);
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
            // eliminate those pesky empty string nodes that go everywhere
            $list_clean=[];
            foreach($list as $node)
            {
                if($node['type']=="literal" && $node['stringval']=="")
                {
                    
                }
                else
                {
                    $list_clean[]=$node;
                }
            }
            $list=$list_clean;
            return $list;
        }    
        
    static function dump_tree_schematic($tree,$colourgen=0,$breaks=false)
    {
        $accumulator=$breaks?"<style>"
                . "body{"
                . "color:#000000;"
                . "background-color:#FFF;"
                . "}"
                . " div{"
                . "min-width:4em;"
                . "overflow:hidden;"
                . "}"
                . ""
                . ".Q{"
                . "color:red;"
                . "font-size:2em;"
                . "}"
                . "</style>":"";
        $weightsmax=0;
        $colours=["red","orange","yellow","green","blue","violet","magenta"];
        $colourgen%=count($colours);
        foreach($tree as $node)
        {
            if(isset($node['stringval'])&&$node['stringval']=="")
            {
                continue;
            }
            $weightsmax+=$node['weight']??1;
        }
        if($weightsmax==0)
        {
            return "<span style=\"font-size:3em;font-weight:bold\">Ø</span>";
        }
        foreach($tree as $node)
        {
            $width=$breaks?100:(($node['weight']??1)/$weightsmax*100);
            if($node['type']=="literal")
            {
                if($node['stringval']=="")
                {
                    continue;
                }
                $accumulator.= "<div style=\"box-sizing:border-box;border:2px solid ".$colours[$colourgen].";float:left;position:relative;width:100%\"><strong>".$node['type']."</strong><br />";
                $preview=(!$breaks && strlen($node['stringval'])>20)?substr($node['stringval'],0,17)."...":$node['stringval'];
                
                $accumulator.= '<em class="Q">[</em><code>'. htmlspecialchars($preview).'</code><em class="Q">]</em>';
                $accumulator.= "</div><br style=\"clear:both\"/>";
                continue;
            }
            $accumulator.= "<div style=\"box-sizing:border-box;border:2px solid ".$colours[$colourgen].";float:left;position:relative;width:100%\"><strong>".$node['type']."</strong><br />";
            
            $omnomnom=[];
            foreach(array_keys($node) as $prop)
            {
                switch($prop)
                {
                    case "weight":
                    case "type":
                    {
                        break;
                    }
                    case "params":
                    {
                        $isKVP=false;
                        foreach($node['params'] as $param)
                        {
                            if(isset($param['name']))
                            {
                               // $accumulator.=self::dump_tree_schematic($param['name']);
                                //$accumulator.=self::dump_tree_schematic($param['value']);
                                $isKVP=true;
                                break;
                            }
                            else
                            {
                                
                                //$accumulator.=self::dump_tree_schematic($param);
                            }
                        }
                        
                        if($isKVP)
                        {
                            foreach($node['params'] as $param)
                            {
                                $omnomnom[]=["paramname", self::CountNodeWeights($param["name"]),self::dump_tree_schematic($param["name"],$colourgen+2)];
                                $omnomnom[]=["paramvalue", self::CountNodeWeights($param["value"]),self::dump_tree_schematic($param["value"],$colourgen+2)];
                            }
                        }
                        else
                        {
                            foreach($node['params'] as $param)
                            {
                                $omnomnom[]=["param", self::CountNodeWeights($param),self::dump_tree_schematic($param,$colourgen+2)];
                            }
                        }
                        
                        
                        //$omnomnom.=self::dump_tree_schematic($omnomnom,$colourgen+1);
                        
                        break;
                    }
                    default:
                    {
                        
                        $omnomnom[]=[$prop, self::CountNodeWeights($node[$prop]),self::dump_tree_schematic($node[$prop],$colourgen+2)];
                    }
                }
                
            }
            $subweightsmax=0;
            foreach($omnomnom as $subnode)
            {
                $subweightsmax+=$subnode[1];
            }
            if($subweightsmax>0)
            {
                foreach($omnomnom as $subnode)
                {
                    $accumulator.= "<div style=\"box-sizing:border-box;border:2px solid ".$colours[($colourgen+1)%count($colours)].";float:left;position:relative;width:".(($subnode[1])/$subweightsmax*100)."%\"><em>".$subnode[0]."</em><br />";
                
                    $accumulator.=$subnode[2];
                    $accumulator.="</div>";
                }
            }
            
            $accumulator.= "</div><br style=\"clear:both\"/>";;
        }
        return $accumulator;
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