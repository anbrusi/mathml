<?php

namespace isView;

/**
 * Propagates the id in Tnumquestions of the faulty question
 * 
 * @package isView
 */
class VillegalSolution extends VviewBase {

    public function render():string {
        $html = '';
        $html .= '<h3>The solution, which you entered, is not admissible.</h3>';
        $html .= '<div class="spacerdiv"></div>';
        if (isset($_POST['annotatedSolution']) ) {
            $html .= \isLib\Lhtml::fieldset('Illegal solution', $_POST['annotatedSolution'], false);
        }
        $html .= '<div class="spacerdiv"></div>';
        $html .= \isLib\Lhtml::actionBar(['esc' => 'Escape', 'correct' => 'Correct']);
        $html .= \isLib\Lhtml::propagatePost('questionid');
        return $html;
    }
}