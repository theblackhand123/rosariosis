<?php
namespace plugins\SMS\includes\gateways\includes\primotexto;
require_once 'baseManager.class.php';

class authenticationManager
{


    private static $apiKey;

    public static function setApiKey($apiKey)
    {
        if ($apiKey != "") {
            self::$apiKey = $apiKey;
        } else {
            die ('Veuillez renseigner votre apiKey');
        }
    }

    public static function ensureLogin()
    {
        if (!self::$apiKey) {
            die ('ApiKey ERROR !');
        }
    }


    public static function getApiKey()
    {
        return self::$apiKey;
    }
}

?>
