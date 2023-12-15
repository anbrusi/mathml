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
}