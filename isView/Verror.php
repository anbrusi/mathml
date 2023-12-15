<?php

namespace isView;

class Verror extends VviewBase {

    /**
     * $_POST['errmess'] is the displayed message
     * $_POST['backview], if set, is the view to which the back button return
     * If $_POST['propagate'] is set and its value is a comma delimited list of names
     * each name is a POST variable that will be propagated as a hidden input variable
     * .
     * @return string 
     */
    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        // Message
        $html .= '<h2>Error</h2>';
        if (!isset($_POST['errmess'])) {
            $_POST['errmess'] = 'No error message found';
        }
        $html .= '<div class="errormessage">'.$_POST['errmess'].'</div>';
        // action bar
        if (isset($_POST['backview'])) {
            $html .= '<div class="spacerdiv"></div>';
            $html .= \isLib\Lhtml::actionBar(['back' => 'ok']);
        }
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