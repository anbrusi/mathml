<?php

namespace isView;

class VeditFile extends VviewBase {

    private function ckeditor(string $content):string {
        $html = '';
        $html .= 'jup';
        return $html;
    }

    private function ckeditorScript():string {

        $txt = '';

        $txt .= <<<'EOD'
        ClassicEditor
            .create( document.querySelector( '#ckeditor' ), {
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
                        URI: 'https://myeclipse/mathml/ckeditor_5_1/wiris/integration',
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

        return $txt;
    }

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        $html .= '<div class="ckeditor">';
        $html .= '<textarea id="ckeditor">';
        $html .= $this->ckeditor('');
        $html .= '</textarea>';
        $html .= '<script>';
        $html .= $this->ckeditorScript();
        $html .= '</script>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }
}