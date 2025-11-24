<?php

/**
 * Main class to service all page-related needs
 *
 * @author admin
 */
class EngineCore
{
    // should be globally accessible

    /**
     * Determines whether debug mode is on
     * @var bool
     */
    public static $DEBUG;

    /**
     * Raw mode outputs directly instead of buffering
     * @var bool
     */
    public static $RawMode;

    /**
     * Current logged-in user
     * @var User
     */
    public static $CurrentUser;
    // not going to instance any of this, everyone shits in the same bucket

    /**
     * Layout template selected
     * @var string
     */
    static $Layout = "default";

    /**
     * Page title (usually goes in the title tag)
     * @var string
     */
    static $PageTitle;

    /**
     * Sidebar items go here 
     * @var string[]
     */
    static $SideBar;

    /**
     * Page's main content, whatever the database dragged in
     * @var string
     */
    static $MainContent;

    /**
     * A separate text field that can be written to for debugging purposes,
     * not intended to be displayed to the end user in production.
     * @var string
     */
    static $DebugInfo;

    /**
     * If the page got rendered, caches it here okay?
     * @var string
     */
    static $Rendered = "";
    
    static $PerformanceTimer = 0;
    
    public static $get_vars = [];

    //------------------------------+
    // Page operation and rendering |
    //------------------------------+
    //

    /**
     * Set the engine to use the specified layout.
     * @param string $layout
     */
    public static function UseLayout($layout)
    {
        self::$Layout = $layout;
    }

    /**
     * Set the page title to a new value.
     * @param string $title New title.
     */
    public static function SetPageTitle($title)
    {
        self::$PageTitle = $title;
    }

    /**
     * Replace the current page's contents.
     * @param string $content Replacement contents.
     */
    public static function SetPageContent($content)
    {
        self::$MainContent = $content;
    }

    /**
     * Append content to the page.
     * @param string $content Content to be appended.
     */
    public static function AddPageContent($content)
    {
        self::$MainContent .= $content; //the dot makes all the difference
    }

    /**
     * Adds a new block to the sidebar
     * @param string $header block header 
     * @param string $content block contents
     * @param string $headlink header link
     * @param bool $unshift If true, puts the new block at the beginning
     */
    static function AddSideBar($header, $content, $headlink = "", $unshift = false)
    {
        $sidecar = [
            "header" => $header,
            "content" => $content,
            "headlink" => $headlink
        ];
        $unshift ? array_unshift(self::$SideBar, $sidecar) : self::$SideBar[] = $sidecar;
    }

    /**
     * Shortcut to quickly insert a specific template into the page
     * the template name string is parsed for params so this doesn't
     * only work for parameterless templates which is really cool
     * @param string $template template name
     * Supports inline params sent like this:
     * "template,foo=bar,mimsy=borogrove"
     */
    public static function AppendTemplate($template)
    {
        self::AddPageContent((new TemplateProcessor($template))->process(true));
    }

    /**
     * Enables raw mode, which only outputs the main page content without 
     * any surrounding layouts - useful if outputting something other than
     * pages, especially binary data.
     */
    public static function RawModeOn()
    {
        self::$RawMode = true;
    }

    /**
     * Check if raw mode is enabled.
     * @return bool
     */
    public static function RawMode()
    {
        return self::$RawMode;
    }

    /**
     * Process all the layouts and templates and return the final result,
     * ready for the user, cache it in case users dare ask again
     * @return string The results of our hard work
     */
    public static function RenderPage()
    {
        // if raw output is selected, just output the contents without layouts
        if(self::$RawMode)
        {
            return self::$MainContent;
        }
        // do we really have to?
        if(self::$Rendered === "")
        {
            // apparently
            $tpl = self::LayoutComponent("layout");
            // assign basics
            $tpl->tokens['title'] = self::$PageTitle;
            $tpl->tokens['content'] = self::$MainContent;
            // sidebar
            $renderedSideBar = "";
            // add a debug box
            if(self::$DEBUG)
            {
                self::AddSideBar("Debug info", self::$DebugInfo, "", true);
            }
            // load sidecar template
            $box = self::LayoutComponent("sidecar");
            foreach(self::$SideBar as $sidecar)
            {
                $box->tokens = $sidecar;
                $renderedSideBar .= $box->process(true);
            }
            $tpl->tokens['sidebar'] = $renderedSideBar;
            // menu
            $menu = self::LayoutComponent("menu");
            $tpl->tokens['menu'] = $menu->process(true);
            self::$Rendered = $tpl->process(true);
        }
        return self::$Rendered;
    }
    
    public static function LayoutComponent($name)
    {
        return new TemplateProcessor(self::$Layout . DIRECTORY_SEPARATOR . $name , true);
    }
    /**
     * Write an error to the user session to be retrieved later
     * @param string $message Error message to write
     * @param String $channel Type of the error
     */
    public static function WriteUserError($message,$channel="generic")
    {
        if(!isset($_SESSION['cores_user_errors']))
        {
            $_SESSION['cores_user_errors'] = [];
        }
        $_SESSION['cores_user_errors'][]=[$channel,$message];
    }
    /**
     * Fetch errors written, either all or just a specific channel
     * @param string $channel Error channel to retrieve, or empty to fetch all
     * @param bool $peek Do not erase the fetched errors
     * @return array List of string messages.
     */
    public static function GetUserErrors($channel="",$peek=false)
    {
        $errors=[];
        if(!isset($_SESSION['cores_user_errors']))
        {
            return [];
        }
        $listlength=count($_SESSION['cores_user_errors']);
        for($i=0;$i< $listlength;$i++)
        {
            $error=$_SESSION['cores_user_errors'][$i];
            if($channel=="" || $error[0]==$channel)
            {
                $errors[]=$error[1];
            }
            if(!$peek)
            {
                unset($_SESSION['cores_user_errors'][$i]);
            }
        }
        // reset numbering
        $_SESSION['cores_user_errors']=array_values($_SESSION['cores_user_errors']);
        return $errors;
    }
    
    public static function CheckPermission($permission,$user=null)
    {
        if(!$user)
        {
            $user=User::GetCurrentUser();
        }
        return $user->HasPermission($permission);
    }
    
    public static function RequirePermission($permission,$user=null)
    {
        if(!self::CheckPermission($permission,$user))
        {
            self::WriteUserError("Not authorised to use this","permission");
            self::GTFO("/main/unauthorised");
            die;
        }
    }
    
    /**
     * Write anything to the #DEBUG channel
     * @param string $content Whatever they wish to scream about.
     */
    public static function Write2Debug($content,$linebreak=true)
    {
        self::$DebugInfo .= $content.($linebreak?"<br />":"");
    }

    /**
     * Take a nice dump right under the developer's nose
     * (basically var_dump redirected to $DebugInfo)
     * @param idk $whatever something smelly
     *///                   3dump5me
    public static function Dump2Debug($whatever)
    {
        self::Write2Debug(self::VarDumpString($whatever));
    }

    //-----------------------------+
    // operation utility functions |
    //-----------------------------+
    //
    // the first 2 are mostly to be used as shortcuts, also to confine netbeans'
    // complaining about me rawdogging the superglobals to just like 3 places

    /**
     * Get a specific POST variable, with an optional default
     * @param string $var POST variable name
     * @param string $default default value. Defaults (haha!) to ""
     * @return string maybe not the value we need
     */
    public static function POST($var, $default = "")
    {
        // dear netbeans I promise it'll be fine
        return isset($_POST[$var]) ? $_POST[$var] : $default;
    }

    /**
     * Get (haha!!) a specific GET (get it? GET IT?) variable,
     * with an optional default
     * @param string $var GET variable name
     * @param string $default default value. Defaults (is this still funny?)
     * to ""
     * @return string the value we deserve
     */
    public static function GET($var, $default = "")
    {
        // sssh it's like using a condom okay?
        if(isset($_GET[$var]))
        {
            return $_GET[$var];
        }
        // see that's why wrapping it was a good idea
        if(!self::$get_vars)
        {
            parse_str(urldecode($_SERVER['QUERY_STRING']),self::$get_vars);
        }
        if(isset(self::$get_vars[$var]))
        {
            return self::$get_vars[$var];
        }
        return  $default;
    }

    /**
     * Gently and politely send the user and their browser on their merry way
     * of your own choosing
     * @param string $url Wherever you want the user to GTFO to
     */
    static function GTFO($url)
    {
        if(!headers_sent())
        {
            HTTPHeaders::Location($url);
        }
        echo "<a href=\"$url\">Go to $url</a>";
    }
    /**
     * Gets the user's time offset - only works from second page load on
     * @return int the time offset, falling back to 0 (UTC) if not found
     */
    static function GetTimeOffset()
    {
        return isset($_COOKIE['timeoffset'])?intval($_COOKIE['timeoffset']):0;
       
    }
    
    /**
     * Send the user back to the previous page
     * Original comments kept, code slightly updated
     */
    static function FromWhenceYouCame()
    {
        //you shall remain
        $r = HTTPHeaders::GetReferer();
        //until you are
        if(!headers_sent())
        {
            //complete again!
            HTTPHeaders::Location($r);
        }
        //NOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO
        echo "<a href=\"$r\">The redirect seems to fail. Go there yourself?</a>";
    }

    /**
     * Retrieves a system setting.
     * @param string $setting setting name
     * @return string value of the setting
     */
    static function GetSetting($setting)
    {       
        return DBHelper::RunScalar("SELECT setting_value FROM system_settings WHERE setting_name=?", [$setting], 0);
    }
    
    static function SetSetting($setting,$value)
    {
        $prev_value = EngineCore::GetSetting($setting);
        if($prev_value === false)
        {
            DBHelper::Insert("system_settings", [$setting, $value]);
        }
        else
        {
            DBHelper::Update("system_settings",["setting_value"=>$value],["setting_name"=>$setting]);
        }
        
    }
    
    static function GetAllSettings()
    {
        return DBHelper::RunTable(DBHelper::Select("system_settings",["setting_name","setting_value"][]),[]);
    }
    
    static function GetMenuLink($id)
    {
        return DBHelper::RunRow(DBHelper::Select("menulinks",["id","link","text"],["id"=>$id]),[$id]);
    }
    
    static function GetMenuLinks()
    {
        return DBHelper::RunTable(DBHelper::Select("menulinks",["id","link","text"],[]),[]);
    }
    
    static function SetMenuLink($id,$link,$text)
    {
        return DBHelper::Update("menulinks",["link"=>$link,"text"=>$text],["id"=>$id]);
    }
    static function AddMenuLink($link,$text)
    {
        return DBHelper::Insert("menulinks",[null,$link,$text]);
    }
    static function SetMenuLinkHref($id,$link)
    {
        return DBHelper::Update("menulinks",["link"=>$link],["id"=>$id]);
    }
    static function SetMenuLinkText($id,$text)
    {
        return DBHelper::Update("menulinks",["text"=>$text],["id"=>$id]);
    }
    static function DeleteMenuLink($id)
    {
        return DBHelper::Delete("menulinks",["id"=>$id]);
    }
    

    //---------------------------------------------+
    // More random crap and ostensibly useful code |
    //---------------------------------------------+
    //

    /**
     * Yep, another condom function, this one's when you gotta take a var_dump
     * but gotta keep it from going all over your page
     * so maybe more like a diaper really
     * @param whocares $whatever
     * @return string Strings of whatever it was you were about to dump
     * perhaps it is poop?
     */
    public static function VarDumpString($whatever)
    {
        ob_start();
        var_dump($whatever);
        return ob_get_clean();
    }
    
    static function StartLap()
    {
        self::$PerformanceTimer = hrtime(true);
    }
    
    static function Lap2Debug($message="")
    {
        if($message=="")
        {
            $message=__LINE__;
        }
        $now = hrtime(true);
        $diff = ($now-self::$PerformanceTimer)/1000000;
        self::Write2Debug("<strong>".$diff."</strong> ".$message);
        self::$PerformanceTimer = hrtime(true);
    }
}
