<?php

namespace isLib;

use __PHP_Incomplete_Class;

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

    public static function getFileArray(string $directory):array {
        $files = [];
        $content = scandir($directory);
        if ($content === false) {
            throw new \Exception('VadminFormulas: error retriewing files');
        }
        foreach ($content as $file) {
            if ($file != '.' && $file != '..') {
                $files[] = $file;
            }
        }
        return $files;
    }

    public static function propagatePost(string $name):string {
        $html = '';
        if (isset($_POST[$name])) {
            $html .= '<input type="hidden" name="'.$name.'" value="'.$_POST[$name].'" />';
        }
        return $html;
    }

    /**
     * Returns a HTML table for an array of variables with key name of variable and value value of varaible
     * Values are displayd as text inputs with name 'var_' followed by the name of the variable-
     * So values can be changed and stored- 
     * 
     * @param array $vars 
     * @return string 
     */
    public static function varTable(array $vars):string {
        $html = '';
        $html .= '<table class="filetable">';
        $html .= '<tr><th>name</th><th>value</th></tr>';
        foreach ($vars as $name => $value) {
            $html .= '<tr>';
            $html .= '<td>'.$name.'</td>';
            $html .= '<td>';
            $value = $vars[$name];
            $html .= '<input type="text" name="var_'.$name.'" value="'.$value.'" />';
            $html .= '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        return $html;
    }
}