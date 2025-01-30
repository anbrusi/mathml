<?php

namespace isView;
/**
 * A task consists of a problem and a solution. 
 * Problems are stored in Lconfig::CF_PROBLEMS_DIR, solutions in Lconfig::CF_SOLUTIONS_DIR
 * The files have equal names and a '.html' extension. This is also the name of the task
 * 
 * @package isView
 */
class VadminTasks extends VviewBase {

    private string $currentTask = '';

    function __construct(string $name) {
        parent::__construct($name);
        if (\isLib\LinstanceStore::available('currentTask')) {
            $this->currentTask = \isLib\LinstanceStore::get('currentTask');
        } else {
            $this->currentTask = 'No task has been set to current';
        }
    } 

    private function currentTask():string {
        $html = '';
        $html .= '<div>';
        $html .= 'current file: <strong>'.$this->currentTask.'</strong>';
        $html .= '</div>';
        return $html;
    }

    private function availableTasks():string {
        $html = '';
        $html .= '<table class="filetable">';
        // header
        $html .= '<tr>';
        $html .= '<th>!</th>';
        $html .= '<th>?</th>';
        $html .= '<th>x</th>';
        $html .= '<th>task</th>';
        $html .= '<th>problem</th>';
        $html .= '</tr>';
        // files
        $files = \isLib\Lhtml::getFileArray(\isLib\Lconfig::CF_PROBLEMS_DIR);
        foreach ($files as $file) {
            $task = substr($file, 0, strrpos($file, '.'));
            $html .= '<tr>';
            // Radio choosing the current file
            $html .= '<td>';
            if ($task == $this->currentTask) {
                $checked = 'checked="checked"';
            } else {
                $checked = '';
            }
            $html .= '<input type="radio" name="available_tasks" value="'.$task.'" '.$checked.' onClick="
                const mainform = document.getElementById(\'mainform\');
                console.log(\'submit\', this.value);
                mainform.submit();
            "/>';

            $html .= '</td>';
            // Edit button
            $html .= '<td><button type="submit" name="edit" class="linkbutton" value="'.$task.'">';
            $html .= '<img src="isImg/isPencilGrey.png" class="linkimage" />';
            $html .= '</button></td>';
            // Delete button
            $html .= '<td><button type="submit" name="delete" class="linkbutton" value="'.$task.'">';
            $html .= '<img src="isImg/isDestroyGrey.png" class="linkimage" />';
            $html .= '</button></td>';
            // File name
            $task = substr($file, 0, strrpos($file, '.'));
            $html .= '<td>'.$task.'</td>';
            // Task
            $ressource = fopen(\isLib\Lconfig::CF_PROBLEMS_DIR.$file, 'r');
            $problem = fgets($ressource);
            $html .= '<td>'.$problem.'</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        return $html;
    }

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        // Display the current file
        $html .= $this->currentTask();
        $html .= '<div class="spacerdiv"></div>';
        // Display table of available tasks
        $html .= $this->availableTasks();
        $html .= '<div class="spacerdiv"></div>';
        // Display the action buttons
        $html .= \isLib\Lhtml::actionBar(['new' => 'New Task']);
        $html .= '</div>';
        return $html;
    }
}