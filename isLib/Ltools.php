<?php

namespace isLib;

class Ltools {

    public static function ownUrl():string {
		if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] != '')) {
			$prefix = 'https://';
		} else {
			$prefix = 'http://';
		}
        return $prefix.$_SERVER['SERVER_NAME'];
    }

    public static function ownRef(string $getParameters):string {
        return self::ownUrl().$_SERVER['SCRIPT_NAME'].$getParameters;
    }
}