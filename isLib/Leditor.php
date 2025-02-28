<?php

namespace isLib;

class Leditor {

    public const ED_TP_FORMULA_ONLY = 1;
    public const ED_TP_FORMULA_AND_IMG = 2;

    private static function formula_only(): string {
        return <<<'EOD'
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
    }

    private static function formula_and_img(): string {
        return <<<'EOD'
        isCkeditor.ClassicEditor
            .create( document.querySelector( '#editorid' ), {
                toolbar: [
                    'heading',
                    'bold',
                    'italic',
                    '|',
                    'MathType',
                    '|',
                    'uploadImage',
                    '|',
                    'sourceEditing'
                ],
                mathTypeParameters: {
                    serviceProviderProperties: {
                        URI: '/mathml/ckeditor_5_2/wiris/integration',
                        server: 'php'
                    }
                },
                simpleUpload: {
                    uploadUrl: './mmlUpload.php'
                },
                image: {
                    toolbar: [
                        'imageStyle:inline',
                        'imageStyle:wrapText',
                        'imageStyle:breakText',
                        '|',
                        'toggleImageCaption',
                        'imageTextAlternative'
                    ]
                },
            } )
            .then( editor => {
                console.log('editor ready', editor); 
            } )
            .catch( error => {
                console.error( error );
            });
        EOD;
    }

    private static function ckeditorScript(int $type, string $id):string {
        $txt = '';
        switch ($type) {
            case self::ED_TP_FORMULA_ONLY:
                $editor = self::formula_only();
                break;
            case self::ED_TP_FORMULA_AND_IMG:
                $editor = self::formula_and_img();
                break;
            default:
                throw new \Exception('Leditor missing editor type');
        }
        $editor = str_replace('editorid', $id, $editor);
        if ($_SERVER['SERVER_NAME'] != 'myeclipse') {
            // misas implementation does not require a subdirectory 'mathml'
            $editor = str_replace('/mathml/ckeditor_5_2', '/ckeditor_5_2', $editor);
        }
        $txt .= $editor;
        return $txt;
    }


    public static function editor(int $type, string $name, string $content):string {
        $html = '';
        $html .= '<div class="ckeditor">';
        $html .= '<textarea id="'.$name.'" name="'.$name.'">';
        $html .= $content;
        $html .= '</textarea>';
        $html .= '<script>';
        $html .= self::ckeditorScript($type, $name);
        $html .= '</script>';
        $html .= '</div>';
        return $html;
    }

}