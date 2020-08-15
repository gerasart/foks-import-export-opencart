<?php
/**
 * Created by PhpStorm.
 * User: gerasart
 * Date: 7/18/2019
 * Time: 10:28 AM
 */

namespace Foks\Helpers;

class Helpers {
    
    /**
     * @param $data
     * @param string $name
     */
    public static function LogData( $data, $name = 'logs' ) {
        $dir = FOKS_PATH . "/logs/{$name}.txt";
        file_put_contents( $dir, json_encode( $data ) . "\n", FILE_APPEND );
    }
    
    
    /**
     * @param $var
     */
    public static function debug( $var ) {
        echo "<pre>";
        var_dump( $var );
        echo "</pre>";
    }
    
    
    /**
     * @param $title
     * @param bool $url
     * @return false|string
     */
    public static function translit( $title, $url = false)
    {
        
        $gost = array(
            "Є"=>"EH","І"=>"I","і"=>"i","№"=>"#","є"=>"eh",
            "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G","Д"=>"D",
            "Е"=>"E","Ё"=>"JO","Ж"=>"ZH",
            "З"=>"Z","И"=>"I","Й"=>"JJ","К"=>"K","Л"=>"L",
            "М"=>"M","Н"=>"N","О"=>"O","П"=>"P","Р"=>"R",
            "С"=>"S","Т"=>"T","У"=>"U","Ф"=>"F","Х"=>"KH",
            "Ц"=>"C","Ч"=>"CH","Ш"=>"SH","Щ"=>"SHH","Ъ"=>"'",
            "Ы"=>"Y","Ь"=>"","Э"=>"EH","Ю"=>"YU","Я"=>"YA",
            "а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d",
            "е"=>"e","ё"=>"jo","ж"=>"zh",
            "з"=>"z","и"=>"i","й"=>"jj","к"=>"k","л"=>"l",
            "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
            "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"kh",
            "ц"=>"c","ч"=>"ch","ш"=>"sh","щ"=>"shh","ъ"=>"",
            "ы"=>"y","ь"=>"","э"=>"eh","ю"=>"yu","я"=>"ya",
            "—"=>"-","«"=>"","»"=>"","…"=>""
        );
        
        $iso = array(
            "Є"=>"YE","І"=>"I","Ѓ"=>"G","і"=>"i","№"=>"#","є"=>"ye","ѓ"=>"g",
            "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G","Д"=>"D",
            "Е"=>"E","Ё"=>"YO","Ж"=>"ZH",
            "З"=>"Z","И"=>"I","Й"=>"J","К"=>"K","Л"=>"L",
            "М"=>"M","Н"=>"N","О"=>"O","П"=>"P","Р"=>"R",
            "С"=>"S","Т"=>"T","У"=>"U","Ф"=>"F","Х"=>"X",
            "Ц"=>"C","Ч"=>"CH","Ш"=>"SH","Щ"=>"SHH","Ъ"=>"'",
            "Ы"=>"Y","Ь"=>"","Э"=>"E","Ю"=>"YU","Я"=>"YA",
            "а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d",
            "е"=>"e","ё"=>"yo","ж"=>"zh",
            "з"=>"z","и"=>"i","й"=>"j","к"=>"k","л"=>"l",
            "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
            "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"x",
            "ц"=>"c","ч"=>"ch","ш"=>"sh","щ"=>"shh","ъ"=>"",
            "ы"=>"y","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya",
            "—"=>"-","«"=>"","»"=>"","…"=>""
        );
        
        if (!$url) {
            return strtr( $title, $gost );
        } else {
            $title = mb_strtolower($title);
            $title = strtr($title, $gost);
            $title = mb_ereg_replace('[^-0-9a-z]', '-', $title);
            $title = mb_ereg_replace('[-]+', '-', $title);
            $title = trim($title, '-');
    
            return $title;
        }
    }

}
