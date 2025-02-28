<?php

namespace isView;

/**
 * @abstract
 * If we come from  an 'edit' command in CnumericQuestions, $_POST['edit'] is the name of the task, we want to edit.
 * If on the other hand we come from a 'new' command $_POST['edit'] is not set 
 * and the task name is taken from $_POST['new_task'], which is an input of type text displayed in this view
 * 
 * @package isView
 */
class VeditNumericQuestion extends VviewBase {

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        if (!isset($_POST['edit'])) {
            // A new task is requested, so ask for a name

            // The new task name. With the extension 'html' this will be the name of the problem file and of the solution file
            $html .= '<div>Enter a name for the new question: <input type="text" name="new_task" autofocus="autofocus"/></div>'; // editor
            $html .= '<div class ="spacerdiv"></div>';
            // editor
            if (isset($_POST['previous_question'])) {
                $questioncontent = $_POST['previous_question'];
            } else {
                $questioncontent = '';
            }
            if (isset($_POST['previous_solution'])) {
                $solutioncontent = $_POST['previous_solution'];
            } else {
                $solutioncontent = '';
            }
        } else {
            // Edit the task $_POST['edit']
            $ressource = fopen(\isLib\Lconfig::NUMERIC_QUESTIONS_DIR.$_POST['edit'].'.html', 'r');
            $questioncontent = fgets($ressource);
            $ressource = fopen(\isLib\Lconfig::NUMERIC_SOLUTIONS_DIR.$_POST['edit'].'.html', 'r');
            $solutioncontent = fgets($ressource);
            // propagate the task name
            $html .= \isLib\Lhtml::propagatePost('edit');
        }
        $html .= '<h3>Question</h3>';
        $html .= \isLib\Leditor::editor(\isLib\Leditor::ED_TP_FORMULA_AND_IMG, 'question', $questioncontent);
        $html .= '<h3>Teacher solution</h3>';
        $html .= \isLib\Leditor::editor(\isLib\Leditor::ED_TP_FORMULA_ONLY, 'solution', $solutioncontent);
        // buttons
        $html .= '<div class="spacerdiv"></div>';
        $html .= \isLib\Lhtml::actionBar(['esc' => 'Escape', 'store' => 'Store']);
        $html .= '</div>';
        return $html;
    }

}