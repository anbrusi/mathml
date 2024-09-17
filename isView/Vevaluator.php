<?php

namespace isView;

class Vevaluator extends VviewBase {

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        $html .= \isLib\Lhtml::currentFile();
        $html .= \isLib\Lhtml::fieldset('Expression', $_POST['expression']);
        $html .= \isLib\Lhtml::fieldset('Parse tree', $_POST['parseTree']);
        $html .= \isLib\Lhtml::fieldset('Variables', $_POST['variables']);
        $html .= \isLib\Lhtml::fieldset('Evaluation', $_POST['evaluation']);
        $html .= \isLib\Lhtml::fieldset('Errors', $_POST['errors']);
        $html .= \isLib\Lhtml::fieldset('Trace', $_POST['trace']);
        $html .= \isLib\Lhtml::fieldset('LateX code', $_POST['latex']);
        if (isset($_POST['variables']) && !empty($_POST['variables'])) {
            $html .= \isLib\Lhtml::actionBar(['update' => 'Update variables', 'delete' => 'Delete stored variables']);
        }
        $html .= '</div>';
        return $html;
    }
}