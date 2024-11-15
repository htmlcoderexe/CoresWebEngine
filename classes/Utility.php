<?php

class Utility
{
    const RANDOM_CHR_UPPER =0;
    const RANDOM_CHR_LOWER =1;
    const RANDOM_CHR_MIX =2;
    
    const PREFIXES_METRIC = ["","k","M","G","T","P","E"];
    const PREFIXES_BINARY = ["","Ki","Mi","Gi","Ti","Pi","Ei"];
    
    const MULTIPLIER_METRIC = 1000;
    const MULTIPLIER_BINARY = 1024;
    
    /**
     * Gets a random single character
     * Uses PHP's CSPRNG
     * @param int $mode Mode of selection:
     * 0 = UPPERCASE ONLY
     * 1 = lowercase only
     * 2 = Both Uppercase And LowerCase
     * @return string
     */
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
    /**
     * Gets a string of random characters
     * Uses PHP's CSPRNG
     * @param int $len the lenght of the desired string
     * @param int $mode Mode of selection:
     * 0 = UPPERCASE ONLY
     * 1 = lowercase only
     * 2 = Both Uppercase And LowerCase
     * @return string
     */
    public static function CreateRandomString($len,$mode)
    {
        $s="";
        for($i=0;$i<$len;$i++)
        {
                $s.=self::CreateRandomChar($mode);
        }
        return $s;
    }
    /**
     * Converts a Unix timestamp to human-readable datetime.
     * @param int $unix the Unix timestamp to be converted
     * @return string the datetime corresponding to the given timestamp.
     */
    static function Unix2Human($unix)
    {
        return date("d/m/y-H:i",($unix));
    }
    /**
     * Converts a date and time into a Unix timestamp
     * Shorthand for the corresponding DateTime function usage
     * defaults to "d/m/y-H:i" which corresponds to DDMMYY-HH:MM
     * @param string $hread date string to convert, in the desired format
     * defaulting to DDMMYY-HH:MM if $format is not specified
     * @param $format optional date() format string, defaults to "d/m/y-H:i"
     * which corresponds to "DDMMYY-HH:MM"
     * @return int The Unix timestamp corresponding to the given date
     */
    static function Human2Unix($hread,$format="d/m/y-H:i")
    {
        $r= DateTime::createFromFormat($format,$hread);
        return $r->getTimestamp();
    }
    /**
     * Divides a large number into smaller units for nice display.
     * Default values correspond to abbreviated metric prefixes.
     * @param int $size The number to format
     * @param string[] $prefixes unit prefixes to use (metric prefixes)
     * @param int $multiplier The multiplier between each prefix (1000)
     * @param int $decimals Number of decimals to use (2)
     * @param char $separator Character to use as a decimal point (.)
     * @return type
     */
    public static function FormatUnit($size,$prefixes = self::PREFIXES_METRIC,$multiplier = self::MULTIPLIER_METRIC,$decimals = 2, $separator =".")
    {
        $current_prefix=0;
        while($size>$multiplier && $current_prefix < count($prefixes)-1)
        {
            $size/=$multiplier;
            $current_prefix++;
        }
        return number_format($size,$decimals,$separator," ")." ".$prefixes[$current_prefix];
    }
    
}
