<?php

namespace isView;

class Vfilter extends VviewBase {

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        // Problem
        $html .= \isLib\Lhtml::fieldset('Problem', $_POST['problemcontent']);
        // Solution
        $html .= \isLib\Lhtml::fieldset('Solution', $_POST['solutioncontent']);
        // Filtered solution
        $html .= \isLib\Lhtml::fieldset('ASCII content', $_POST['asciicontent']);
        // propagate the task name
        $html .= \isLib\Lhtml::propagatePost('task');
        $html .= '</div>';
        return $html;
    }
}