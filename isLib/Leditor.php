<?php

namespace isLib;

class Leditor {

    private static function ckeditorScript(string $id):string {
        $txt = '';
        $editor = <<<'EOD'
        isCkeditor.ClassicEditor
            .create( document.querySelector( '#editorid' ), {
                toolbar: [
                    'heading',
                    'bold',
                    'italic',
                    '|',
                    'MathType',
                    '|',
                    'sourceEditing'
                ],
                mathTypeParameters: {
                    serviceProviderProperties: {
                        URI: '/mathml/ckeditor_5_2/wiris/integration',
                        server: 'php'
                    }
                }
            } )
            .then( editor => {
                console.log('editor ready', editor); 
            } )
            .catch( error => {
                console.error( error );
            });
        EOD;
        $editor = str_replace('editorid', $id, $editor);
        $txt .= $editor;
        return $txt;
    }


    public static function editor(string $name, string $content):string {
        $html = '';
        $html .= '<div class="ckeditor">';
        $html .= '<textarea id="'.$name.'" name="'.$name.'">';
        $html .= $content;
        $html .= '</textarea>';
        $html .= '<script>';
        $html .= self::ckeditorScript($name);
        $html .= '</script>';
        $html .= '</div>';
        return $html;
    }

}