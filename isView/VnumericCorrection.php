<?php

namespace isView;

/**
 * The name of the task is passed by $_POST['task].
 * The student answer is $_POST['answer]
 * 
 * @package isView
 */
class VnumericCorrection extends VviewBase {

    public function render():string {
        $html = '';
        // Question
        $ressource = fopen(\isLib\Lconfig::NUMERIC_QUESTIONS_DIR.$_POST['task'].'.html', 'r');
        $questioncontent = fgets($ressource);
        $html .= \isLib\Lhtml::fieldset('Question', $questioncontent, false);
        // Teacher solution
        $ressource = fopen(\isLib\Lconfig::NUMERIC_SOLUTIONS_DIR.$_POST['task'].'.html', 'r');
        $solutioncontent = fgets($ressource);
        $html .= \isLib\Lhtml::fieldset('Teacher solution', $solutioncontent, false);
        // Student solution 
        $html .= \isLib\Lhtml::fieldset('Student solution', $_POST['answer'], false);
        // buttons
        $html .= '<div class="spacerdiv"></div>';
        $html .= \isLib\Lhtml::actionBar(['esc' => 'Escape', 'repeat' => 'New student answer']);
        $html .= '</div>';
        $html .= \isLib\Lhtml::propagatePost('task');
        $html .= \isLib\Lhtml::propagatePost('answer');
        return $html;
    }
}