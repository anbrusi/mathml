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

    /**
     * Scans $html for mathML and returns a numeric array of arrays, 
     * having the mathML expression in position 0 and the offset of the expression in $html in position 1
     * 
     * @param string $html 
     * @return array 
     */
    public static function getMathmlExpressions(string $html):array {
        $result = [];
        $nr = preg_match_all('/<math.*?<\/math>/', $html, $matches, PREG_OFFSET_CAPTURE);
        if ($nr > 0) {
            foreach ($matches[0] as $match) {
                $mathml = $match[0];
                $offset = $match[1];
                $result[] = [$mathml, $offset];
            }
        }
        return $result;
    }

    public static function getExpression(string $filePath):string {
        $ressource = fopen($filePath, 'r');
        $expression = fgets($ressource);
        return $expression;
    }

    /**
     * Returns the variables stored in $file in the CF_VARS_DIR or false upon error
     * Keys are the names of the variables, values their numeric value
     * 
     * @param string $file 
     * @return array 
     */
    public static function getVars(string $file):array|false {
        $ressource = fopen(\isLib\Lconfig::CF_VARS_DIR.$file, 'r');
        if ($ressource === false) {
            return false;
        }
        $json = fgets($ressource);
        return json_decode($json, true);
    }

    public static function isMathMlExpression(string $expression):bool {
        $mathMlItems = self::extractMathML($expression);
        return count($mathMlItems) > 0;
    }

    public static function isMathMlFile(string $filePath):bool {
        $ressource = fopen($filePath, 'r');
        $expression = fgets($ressource);
        return self::isMathMlExpression($expression);
    }

    public static function storeVariables(string $file):bool {
        $vars = [];
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'var_') === 0) {
                $varname = substr($key, 4);
                $vars[$varname] = $value;
            }
        }
        $json = json_encode($vars);
        $ressource = fopen(\isLib\Lconfig::CF_VARS_DIR.$file, 'w');
        if (fputs($ressource, $json) === false) {
            return false;
        }
        return true;
    }

    public static function deleteVariables(string $file):bool {
        return unlink(\isLib\Lconfig::CF_VARS_DIR.$file);
    }

    /**
     * Content produced by CKEditor5 needs special CSS provided by an own CSS file.
     * To take effect this CSS must ce inside a container of class "ck-content"
     * 
     * @param string $html 
     * @return string 
     */
    public static function wrapContent5(string $html):string {
        return '<div class="ck-content">'.$html.'</div><div class="clearboth"></div>';
    }

    /**
     * Returns all src attributes of all img tags in $html
     * 
     * @param string $html 
     * @return array 
     */
    public static function getImgSrc(string $html):array {
        preg_match_all('/<img.*?src="([^"]*)/', $html, $matches);
        return $matches[1];
    }
}