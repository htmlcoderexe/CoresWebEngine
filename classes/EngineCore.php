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
    static $Layout;
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
        self::$Layout=$layout;
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
     */
    static function AddSideBar($header,$content,$headlink=null)
    {
        // TODO: bring this to the layout standard
        // additional template file? called sidecar? lol
        $box=new TemplateProcessor("sidebarbox");
        $box->tokens['header']=$header;
        $box->tokens['content']=$content;
        if($headlink!=null)
        {
            $box->tokens['headlink']=$headlink;
        }
        self::$SideBar[]=$box->process(true);
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
        if(self::$Rendered==="")
        {
            // apparently
            $tpl = new TemplateProcessor(self::$Layout);
            if(self::$DEBUG)
            {
                self::AddSideBar("Debug info", self::$DebugInfo);
            }
            $tpl->tokens['title'] = self::$PageTitle;
            $tpl->tokens['content'] = self::$MainContent;
            // TODO: something more dignified than this
            $tpl->tokens['sidebar'] = implode("\r\n\n",self::$SideBar);
            self::$Rendered=$tpl->process(true);
        }     
        return self::$Rendered;
        
    }
    /**
     * Write anything to the #DEBUG channel
     * @param string $content Whatever they wish to scream about.
     */
    public static function Write2Debug($content)
    {
        self::$DebugInfo.=$content;
    }
    /**
     * Take a nice dump right under the developer's nose
     * (basically var_dump redirected to $DebugInfo)
     * @param idk $whatever something smelly
     *///                   3dump5me
    public static function Dump2Debug($whatever)
    {
        self::WriteDebug(self::VarDumpString($whatever));
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
    public static function POST($var,$default="")
    {
        // dear netbeans I promise it'll be fine
        return isset($_POST[$var])?$_POST[$var]:$default;
    }
    /**
     * Get (haha!!) a specific GET (get it? GET IT?) variable,
     * with an optional default
     * @param string $var GET variable name
     * @param string $default default value. Defaults (is this still funny?)
     * to ""
     * @return string the value we deserve
     */
    public static function GET($var,$default="")
    {
        // sssh it's like using a condom okay?
        return isset($_GET[$var])?$_GET[$var]:$default;
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
     * Send the user back to the previous page
     * Original comments kept, code slightly updated
     */
    static function FromWhenceYouCame()
    {
            //you shall remain
            $r=HTTPHeaders::GetReferer();
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
}
