<?php

namespace isView;

class VeditTask extends VviewBase {

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        if (!isset($_POST['task']) || $_POST['task'] === '') {
            // A new task is requested

            // The new task name. With the extension 'html' this will be the name of the problem file and of the solution file
            $html .= '<div>Enter a name for the new task: <input type="text" name="new_task" autofocus="autofocus"/></div>'; // editor
            $html .= '<div class ="spacerdiv"></div>';
            // editor
            if (isset($_POST['previous_content'])) {
                $problemcontent = $_POST['previous_problemcontent'];
                $solutioncontent = $_POST['previous_solutioncontent'];
            } else {
                $problemcontent = '';
                $solutioncontent = '';
            }
        } else {
            // The current task $_POST['task'] is edited
            $ressource = fopen(\isLib\Lconfig::CF_PROBLEMS_DIR.$_POST['task'].'.html', 'r');
            $problemcontent = fgets($ressource);
            $ressource = fopen(\isLib\Lconfig::CF_SOLUTIONS_DIR.$_POST['task'].'.html', 'r');
            $solutioncontent = fgets($ressource);
        }
        $html .= '<h3>Problem</h3>';
        $html .= \isLib\Leditor::editor('problem', $problemcontent);
        $html .= '<h3>Solution</h3>';
        $html .= \isLib\Leditor::editor('solution', $solutioncontent);
        // propagate the task name
        $html .= \isLib\Lhtml::propagatePost('task');
        // buttons
        $html .= '<div class="spacerdiv"></div>';
        $html .= \isLib\Lhtml::actionBar(['esc' => 'Escape', 'store' => 'Store']);
        $html .= '</div>';
        return $html;
    }
}