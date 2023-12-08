<?php

namespace isLib;

class Lhtml {

    /**
     * Displays a row of submit buttons with text $txt setting $_POST[$name]
     * 
     * @param string $name 
     * @param string $txt 
     * @return string 
     */
    private static function actionButton(string $name, string $txt):string {
        return '<button type="submit" name="'.$name.'" class="actionbutton">'.$txt.'</button>';
    }

    public static function actionBar(array $buttons):string {
        $html = '';
        $html = '<div>';
        foreach ($buttons as $key => $value) {
            $html .= self::actionButton($key, $value);
            $html .= '<span class="spacerspan"></span>';
        }
        $html .= '</div>';
        return $html;
    }
}