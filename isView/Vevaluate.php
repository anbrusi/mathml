<?php

namespace isView;

class Vevaluate extends VviewBase {

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        $html .= \isLib\Lhtml::currentFile();
        $html .= \isLib\Lhtml::fieldset('Original expression', $_POST['input']);
        $html .= \isLib\Lhtml::fieldset('Parse tree', $_POST['parseTree']);
        $html .= \isLib\Lhtml::fieldset('Evaluation', $_POST['evaluation']);
        if (!empty($_POST['vars'])) {
            $html .= \isLib\Lhtml::fieldset('Variables', \isLib\Lhtml::varTable($_POST['vars']));
            $html .= \isLib\Lhtml::actionBar(['update' => 'Update variables', 'delete' => 'Delete stored variables']);
        }
        $html .= '</div>';
        return $html;
    }
}