<?php

spl_autoload_register(function ($class) {
    
    $mapping = [
        'Models'=>'models',
        'ViewModels'=>'viewmodels',
        'Common'=>'lib',
        'Cores'=>'cores'
    ];
    if(file_exists(CLASS_DIR. $class . '.php'))
    {
        require_once CLASS_DIR. $class . '.php';
        return;
    }
    
    $path = explode(string: $class, separator: "\\");
    if($path[0]=="")
    {
        array_shift($path);
    }
    $type = array_shift($path);
    $prefix = "";
    if(isset($mapping[$type]))
    {
        $prefix = $mapping[$type];
    }
    $fullpath = $prefix.DIRECTORY_SEPARATOR.implode(array: $path, separator: DIRECTORY_SEPARATOR).".php";
    if(file_exists($fullpath))
    {
        require_once($fullpath);
    }
    else
    {
        
    }
});
