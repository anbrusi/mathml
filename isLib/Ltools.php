<?php

namespace isLib;

class Ltools {

    /**
     * Returns an URL of Document Root e.g. "https://maeclipse/mathml/
     * 
     * @return string 
     */
    public static function ownUrl():string {
		if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] != '')) {
			$prefix = 'https://';
		} else {
			$prefix = 'http://';
		}
        return $prefix.$_SERVER['SERVER_NAME'];
    }

    public static function extractMathML(string $txt):array {
        $items = [];
        $r = preg_match_all('/<math.*?<\/math>/', $txt, $matches);
        if ($r !== false) {
            $items = $matches[0];
        }
        return $items;
    }

    public static function getExpression(string $file):string {
        $ressource = fopen(\isLib\Lconfig::CF_FILES_DIR.$file, 'r');
        $expression = fgets($ressource);
        $expression = str_replace('<p>', '', $expression);
        $expression = str_replace('</p>', "\r\n", $expression);
        $expression = html_entity_decode($expression);
        return $expression;
    }

    public static function getVars(string $file):array {
        $ressource = fopen(\isLib\Lconfig::CF_VARS_DIR.$file, 'r');
        if ($ressource === false) {
            return [];
        }
        $json = fgets($ressource);
        return json_decode($json, true);
    }

    public static function isMathMlExpression(string $expression):bool {
        $mathMlItems = self::extractMathML($expression);
        return count($mathMlItems) > 0;
    }

    public static function isMathMlFile(string $file):bool {
        $ressource = fopen(\isLib\Lconfig::CF_FILES_DIR.$file, 'r');
        $expression = fgets($ressource);
        return self::isMathMlExpression($expression);
    }
}