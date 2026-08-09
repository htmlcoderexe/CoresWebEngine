<?php
namespace Controllers;

use \Chip;
use \Route;

use Cores\EngineCore;

use Common\HTTPHeaders;

use Models\User\User;


/**
 * Description of UtilityController
 *
 * @author admin
 */
class UtilityController
{
    #[Route('main/api/getdata','super')]
    public static function ProxyFetch()
    {
        $user=User::GetCurrentUser();
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
                    'timeout' => 2.0,
                    'user_agent' => "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0"
                ]
            ]);
            $data = file_get_contents($url, false, $context);
            if($data ===false)
            {
                if (function_exists("http_get_last_response_headers") ) {
                    $headers = http_get_last_response_headers();

                }
                else
                {
                    $headers = $http_response_header;
                }
                $http= $headers[0];
                list($proto, $code, $msg) = explode(string: $http, separator: " ", limit: 3);
                $response['status'] = "Error";
                $response['error'] = "No data at the end of the tunnel: $code $msg";
            }
            else
            {
                $data = str_replace(subject: $data, search: "</title>", replace: "</title><base href=\"$url\">");
                $response['data'] = $data;

            }
        }
        if(ob_get_contents())
        {
            ob_clean();
        }
        EngineCore::EmitJSON($response);
    }
    
    #[Route('main/chip/getcommands')]
    public static function GetChipCommands()
    {
        $user = EngineCore::$CurrentUser;
        $commands = Chip::GetCommands($user->userid);
        EngineCore::RawModeOn();
        HTTPHeaders::ContentType("application/json");
        echo json_encode($commands);
        die();
    }
}
