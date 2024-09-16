<?php

namespace isView;

class Vevaluator extends VviewBase {

    private function currentFile():string {
        $html = '';
        $html .= '<div>';
        $html .= 'current file: <strong>'.$_POST['currentFile'].'</strong>';
        $html .= '</div>';
        return $html;
    }

    private function expression():string {
        $html = '';
        $html .= '<fieldset>';
        $html .= '<legend>expression</legend>';
        $html .= '<div>';
        $html .= '<pre>';
        $html .= $_POST['expression'];
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

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        if (isset($_POST['currentFile']) && !empty($_POST['currentFile'])) {
            // Display the current file
            $html .= $this->currentFile();
            $html .= '<div class="spacerdiv"></div>';
        }
        if (isset($_POST['expression']) && !empty($_POST['expression'])) {
            $html .= $this->expression();
            $html .= '<div class="spacerdiv"></div>';
        }
        if (isset($_POST['parseTree']) && !empty($_POST['parseTree'])) {
            $html .= $this->parseTree();
            $html .= '<div class="spacerdiv"></div>';
        }
        if (isset($_POST['variables']) && !empty($_POST['variables'])) {
            $html .= $this->variables();
            $html .= '<div class="spacerdiv"></div>';
        }
        if (isset($_POST['evaluation']) && !empty($_POST['evaluation'])) {
            $html .= $this->evaluation();
            $html .= '<div class="spacerdiv"></div>';
        }
        if (isset($_POST['errors']) && !empty($_POST['errors'])) {
            $html .= $this->errors();
            $html .= '<div class="spacerdiv"></div>';
        }
        if (isset($_POST['trace']) && !empty($_POST['trace'])) {
            $html .= $this->trace();
            $html .= '<div class="spacerdiv"></div>';
        }
        if (isset($_POST['variables']) && !empty($_POST['variables'])) {
            $html .= \isLib\Lhtml::actionBar(['update' => 'Update variables', 'delete' => 'Delete stored variables']);
        }
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }
}