<?php

namespace isView;

class VmathmlEvaluator extends VviewBase {

    private string $currentFile = '';

    function __construct(string $name) {
        parent::__construct($name);
        if (\isLib\LinstanceStore::available('currentFile')) {
            $this->currentFile = \isLib\LinstanceStore::get('currentFile');
        } else {
            $this->currentFile = 'No file has been set to current';
        }
    } 

    private function currentFile():string {
        $html = '';
        $html .= '<div>';
        $html .= 'current file: <strong>'.$this->currentFile.'</strong>';
        $html .= '</div>';
        return $html;
    }

    private function mathmlExpression():string {
        $html = '';
        $html .= '<fieldset>';
        $html .= '<legend>MathML exprssion</legend>';
        $html .= '<div>';
        $html .= '<pre>';
        $html .= $_POST['expression'];
        $html .= '</pre>';
        $html .= '</div>';
        $html .= '</fieldset>';
        return $html;
    }

    private function asciiConversion():string {
        $html = '';
        $html .= '<fieldset>';
        $html .= '<legend>Conversion to ASCII</legend>';
        $html .= '<div>';
        $html .= '<pre>';
        $html .= $_POST['conversion'];
        $html .= '</pre>';
        $html .= '</div>';
        $html .= '</fieldset>';
        return $html;
    }

    private function variables():string {
        $html = '';
        $html .= '<fieldset>';
        $html .= '<legend>Variables</legend>';
        $html .= '<div>';
        $html .= '<pre>';
        $html .= $_POST['variables'];
        $html .= '</pre>';
        $html .= '</div>';
        $html .= '</fieldset>';
        return $html;
    }
    
    private function evaluation():string {
        $html = '';
        $html .= '<fieldset>';
        $html .= '<legend>Evaluation result</legend>';
        $html .= '<div>';
        $html .= '<pre>';
        $html .= $_POST['evaluation'];
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

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        // Display the current file
        $html .= $this->currentFile();
        $html .= '<div class="spacerdiv"></div>';
        $html .= $this->mathmlExpression();
        $html .= '<div class="spacerdiv"></div>';
        if ($_POST['conversion'] !== false) {
            $html .= $this->asciiConversion();
            $html .= '<div class="spacerdiv"></div>';
        }
        if ($_POST['variables'] !== false) {
            $html .= $this->variables();
            $html .= '<div class="spacerdiv"></div>';
        }
        $html .= $this->evaluation();
        $html .= '<div class="spacerdiv"></div>';
        $html .= $this->errors();
        $html .= '<div class="spacerdiv"></div>';
        if ($_POST['variables'] !== false) {
            $html .= \isLib\Lhtml::actionBar(['update' => 'Update variables', 'delete' => 'Delete stored variables']);
        }
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

}