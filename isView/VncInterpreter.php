<?php

namespace isView;

class VncInterpreter extends VviewBase {

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        $html .= \isLib\Lhtml::fieldset('Results', $_POST['result']);
        $html .= '<h3>Command</h3>';
        $html .= '<input type="text" name="command" value="" class="ncCommand" autofocus="true"/>';
        $html .= '<div class="spacerdiv"></div>';
        $html .= \isLib\Lhtml::actionBar(['execCommand' => 'Submit']);
        $html .= '</div>';
        $html .= \isLib\Lhtml::propagatePost('result');
        return $html;
    }

}