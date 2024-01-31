<?php

namespace isView;

class VeditFile extends VviewBase {

    private function ckeditorScript():string {

        $txt = '';
        if ($_SERVER['SERVER_NAME'] == 'myeclipse') {
            $editor = <<<'EOD'
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
        } else {
            $editor = <<<'EOD'
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
                            URI: 'https://mathml.misas.ch/ckeditor_5_1/wiris/integration',
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
        $txt .= $editor;
        return $txt;
    }

    private function editor(string $content):string {
        $html = '';
        $html .= '<div class="ckeditor">';
        $html .= '<textarea id="ckeditor" name="n_ckeditor">';
        $html .= $content;
        $html .= '</textarea>';
        $html .= '<script>';
        $html .= $this->ckeditorScript();
        $html .= '</script>';
        $html .= '</div>';
        return $html;
    }

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        if (!isset($_POST['file']) || $_POST['file'] === '') {
            // A new file is requested

            // The new file name
            $html .= '<div>Enter a name for the new file: <input type="text" name="new_file" autofocus="autofocus"/></div>'; // editor
            $html .= '<div class ="spacerdiv"></div>';
            // editor
            if (isset($_POST['previous_content'])) {
                $content = $_POST['previous_content'];
            } else {
                $content = '';
            }
            $html .= $this->editor($content);
        } else {
            // The current file is edited

            // editor
            $ressource = fopen(\isLib\Lconfig::CF_FILES_DIR.$_POST['file'], 'r');
            $content = fgets($ressource);
            $html .= $this->editor($content);
        }
        // propagate the file name
        $html .= \isLib\Lhtml::propagatePost('file');
        // buttons
        $html .= '<div class="spacerdiv"></div>';
        $html .= \isLib\Lhtml::actionBar(['esc' => 'Escape', 'store' => 'Store']);
        $html .= '</div>';
        return $html;
    }
}