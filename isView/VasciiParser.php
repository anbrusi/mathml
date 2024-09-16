<?php

namespace isView;

class VasciiParser extends VviewBase {

    private function currentFile():string {
        $html = '';
        $html .= '<div>';
        $html .= 'current file: <strong>'.$_POST['currentFile'].'</strong>';
        $html .= '</div>';
        return $html;
    }

    private function asciiExpression():string {
        $html = '';
        $html .= '<fieldset>';
        $html .= '<legend>ASCII math exprssion</legend>';
        $html .= '<div>';
        $html .= '<pre>';
        $html .= $_POST['expression'];
        $html .= '</pre>';
        $html .= '</div>';
        $html .= '</fieldset>';
        return $html;
    }

    private function errors():string {
        $html = '';
        $html .= '<fieldset>';
        $html .= '<legend>Errors</legend>';
        $html .= '<div>';
        $html .= '<pre>';
        $html .= $_POST['errors'];
        $html .= '</pre>';
        $html .= '</div>';
        $html .= '</fieldset>';
        return $html;
    }
    private function trace():string {
        $html = '';
        $html .= '<fieldset>';
        $html .= '<legend>Trace</legend>';
        $html .= '<div>';
        $html .= '<pre>';
        $html .= $_POST['trace'];
        $html .= '</pre>';
        $html .= '</div>';
        $html .= '</fieldset>';
        return $html;
    }
    private function parseTree():string {
        $html = '';
        $html .= '<fieldset>';
        $html .= '<legend>Parse tree</legend>';
        $html .= '<div>';
        $html .= '<pre>';
        $html .= $_POST['parseTree'];
        $html .= '</pre>';
        $html .= '</div>';
        $html .= '</fieldset>';
        return $html;
    }
    private function variables($variables):string {
        $html = '';
        $html .= '<fieldset>';
        $html .= '<legend>Variables</legend>';
        $html .= '<div>';
        $html .= '<pre>';
        foreach ($variables as $variable) {
            $html .= $variable."\r\n";
        }
        $html .= '</pre>';
        $html .= '</div>';
        $html .= '</fieldset>';
        return $html;
    }

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        if (isset($_POST['currentFile']) && !empty($_POST['currentFile'])) {
            // Display the current file
            $html .= $this->currentFile();
            $html .= '<div class="spacerdiv"></div>';
        }
        $html .= $this->asciiExpression();
        $html .= '<div class="spacerdiv"></div>';
        if (!empty($_POST['errors'])) {
            $html .= $this->errors();
            $html .= '<div class="spacerdiv"></div>';
            $html .= $this->trace();
            $html .= '<div class="spacerdiv"></div>';
        }
        $html .= $this->parseTree();
        if (isset($_POST['variables']) && !empty($_POST['variables'])) {
            $html .= '<div class="spacerdiv"></div>';
            $html .= $this->variables($_POST['variables']);
        }
        $html .= '</div>';
        return $html;
    }
}