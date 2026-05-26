<?php

function ModuleAction_main_api_getdata($params)
{
    $user=User::GetCurrentUser();
    if(!$user->HasPermission("super"))
    {
        EngineCore::FromWhenceYouCame();
        die;
    }
    $url = EngineCore::Post("url", "");
    $response = [
        'status'=>"OK",
        'data'=>""
    ];
    if($url=="")
    {
        $response['status'] = "Error";
        $response['error'] = "No URL specified";
    }
    elseif(!str_starts_with(haystack:$url, needle: "https://"))
    {
        $response['status'] = "Error";
        $response['error'] = "That's not a URL";
    }
    else
    {
        $context = stream_context_create( [
            'http'=>[
                'timeout' => 2.0
            ]
        ]);
        $data = file_get_contents($url, false, $context);
        if($data ===false)
        {
            $response['status'] = "Error";
            $response['error'] = "No data at the end of the tunnel";
        }
        else
        {
            $response['data'] = strlen($data);
            $response['data'] = $data;
        }
    }
    if(ob_get_contents())
    {
        ob_clean();
    }
    EngineCore::EmitJSON($response);
}