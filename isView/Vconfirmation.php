<?php

namespace isView;

class Vconfirmation extends VviewBase {

    /**
     * If $_POST['propagate'] is set and its value is a comma delimited list of names
     * each name is a POST variable that will be propagated as a hidden input variable
     * .
     * @return string 
     */
    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        $html .= $_POST['message'];
        $html .= '<div class="spacerdiv"></div>';
        $html .= \isLib\Lhtml::actionBar(['yes' => 'yes', 'no' => 'no']);
        $html .= '</div>';
        // Propagation
        if (isset($_POST['propagate'])) {
            $names = explode(',', $_POST['propagate']);
            foreach ($names as $name) {
                $html .= \isLib\Lhtml::propagatePost(trim($name));
            }
        }
        return $html;
    }
}