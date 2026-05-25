<?php
class MiniTest
{
    public $tests = [];
    public $results = [];
    public $passed = 0;
    public $passedbygroup = [];
    public function Run()
    {
        $this->SetUp();
        $this->RunTests();
        $this->TearDown();
    }
    public function SetUp()
    {
        
    }
    public function TearDown()
    {
        
    }
    public function RunTests()
    {
        foreach($this->tests as $test)
        {
            $this->RunTest($test['body'],$test['expect'],$test['title'],$test['group']??'none');
        }
    }
    
    /*
       Test add template atm is:
     
        $this->tests[]=[
            'title'=>"Test Name",
            'group'=>"Test group",
            'body'=>function(){
                // code
            },
            'expect'=>0
        ]; 
     */
    function RunTest(callable $test, $expect, $title,$group)
    {
        $value = $test();
        if(is_object($expect))
        {
            $pass = $expect == $value;
        }
        elseif(is_array($expect))
        {
            $a= json_encode($expect);
            $b= json_encode($value);
            $pass = $a===$b;
        }
        else
        {
            $pass = $expect === $value;
        }
        if(!array_key_exists($group, $this->results))
        {
            $this->results[$group] = [];
            $this->passedbygroup[$group] = 0;
        }
        $this->results[$group][]=[
            'title'=>$title,
            'pass'=>$pass,
            'expect'=>$expect,
            'result'=>$value
        ];
        if($pass)
        {
            $this->passed++;
            $this->passedbygroup[$group]++;
        }
    }
    public function PrintResults()
    {
        $testcount = count($this->tests);
        $gcount = count($this->results);
        $passcount = $this->passed;
        $all_pass = ($testcount===$passcount);
        $totalcolour = $all_pass?'green':'red';
        echo("<h1 style=\"color: $totalcolour\">$passcount/$testcount tests passed in $gcount groups. </h1>");
        foreach($this->results as $group=>$results)
        {
            $gtestcount = count($results);
            $gpasscount = $this->passedbygroup[$group];
            $gall_pass = ($gtestcount===$gpasscount);
            $groupcolour = $gall_pass?'green':'red';
            echo("<details>");
            echo("<summary>");
            echo("<h2 style=\"color: $groupcolour\">$gpasscount/$gtestcount tests passed in \"$group\". </h2>");
            echo("</summary>");
            
            foreach($results as $testresult)
            {
                $pass=$testresult['pass'];
                $title= $testresult['title'];
                $passcolour = $pass?'green':'red';
                echo("<hr />");
                echo("<h3>$title</h3>");
                echo("<h4 style=\"color: $passcolour\">".($pass?"Passed":"Failed")."</h4>");
                echo("<hr />");
                echo("<h3> EXPECTED</h3><hr />");
                var_dump($testresult['expect']);
                echo("<h3 style=\"color: $passcolour\"> GOT</h3><hr />");
                var_dump($testresult['result']);
                echo("<hr />");

            }
            
            echo("</details>");
        }
    }
}
