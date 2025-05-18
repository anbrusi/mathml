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

    /**
     * Scans $html for mathml and returns an array with the followink keys:
     * 
     * 'html' input $html in which all mathML expressions are wrapped in a div with id="mml-xxx"
     * 'mathml' a numeric array of arrays having a mathML expression in position 0 and the id of the wrapping span in position 1
     *  
     * @param string $html 
     * @return array 
     */
    public static function wrapMathml(string $html):array {
        $result = ['html' => '', 'mathml' => []];
        $mathmlExpressions = self::getMathmlExpressions($html);
        $nr = count($mathmlExpressions);
        $shift = 0;
        $startPos = 0;
        for ($i = 0; $i < $nr; $i++) {
            // Wrap expression $i
            $expression = $mathmlExpressions[$i][0];
            $offset = $mathmlExpressions[$i][1];
            // Copy the text before the expression, i.e. from the start of the unhandled part to the beginning of mathML
            $result['html'] .= substr($html, $startPos, $offset);
            // Insert the span tag
            $span = '<div id="mml-'.$i.'" class="gre">';
            $result['html'] .= $span;
            // Insert mathML
            $result['html'] .= substr($html, $offset, strlen($expression));
            // Close the span
            $result['html'] .= '</div>';
            // Adjust the start position
            $startPos = $offset + strlen($expression);
            // Insert the expression with its id
            $result['mathml'][] = [$expression, 'mml-'.$i];
        }
        // Complete with the text following the last expression
        $result['html'] .= substr($html, $startPos);
        return $result;
    }

    /**
     * Scans $html for the $nr-th (numbered from 0) MathML expression and returns the same HTML 
     * in which the expression, if found, is wrapped with $prefix and $postfix
     * 
     * @param string $html 
     * @param int $nr 
     * @param string $prefix 
     * @param string $postfix 
     * @return string 
     */
    public static function wrapMathmlExpression(string $html, int $nr, string $prefix, string $postfix):string {
        $mathmlExpressions = self::getMathmlExpressions($html);
        if ($nr >= count($mathmlExpressions)) {
            return $html; // Ther is no $nr-th expression. Note that we count from 0!
        }
        $expression = $mathmlExpressions[$nr][0]; // The expression
        $startPos = $mathmlExpressions[$nr][1]; // Index of the first char of the expression
        // Copy HTML from the beginning to the start of the expression
        $result = substr($html, 0, $startPos);
        // Insert the prefix
        $result .= $prefix;
        // Insert the expression
        $result .= $expression;
        // Insert the postfix
        $result .= $postfix;
        // Copy HTML after the expression
        $result .= substr($html, $startPos + strlen($expression));
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