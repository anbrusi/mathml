<?php

namespace isLib;

class Lgauss {

    /**
     * $equations is an array of equations
     * Each equation is an array indexed by variable names and by '1'. 
     * The float values of elements indexed by a variable are the coefficient of that variable, 
     * the float values of the elements indexed by '1' are constants
     * makeMatrix returns an array representing a matrix. The elements are arrays with an entry for each column
     * a[$i][$j] is the matrix element in line $i, column $j. 
     * 
     * @param array $equations 
     * @return array 
     */
    public function makeMatrix(array $equations):array {
        $columns = [];
        foreach ($equations as $equation) {
            foreach ($equation as $key => $value) {
                if (!in_array($key, $columns)) {
                    $columns[] = $key;
                }
            }
        }
        if (!in_array('1', $columns)) {
            $columns[] = '1';
        }
        arsort($columns, SORT_STRING);
        // Now the first entry of $columns has index '1' for the constants, the following are the names of variables in alphabetic order
        $nrLines = count($equations);
        $nrColumns = count($columns);
        for ($i = 0; $i < $nrLines; $i++) {
            // Fill the first $nrColumns - 1 columns with the values for variable coefficients
            for ($j = 1; $j < $nrColumns; $j++) {
                if (isset($equations[$i]) && isset($equations[$i][$columns[$j]])) {
                    $a[$i][$j - 1] = $equations[$i][$columns[$j]];
                } else {
                    $a[$i][$j - 1] = 0;
                }
            }
            // Fill the last columns with the values for constants
            if (isset($equations[$i]) && isset($equations[$i][$columns[0]])) {
                $a[$i][$nrColumns - 1] = $equations[$i][$columns[0]];
            } else {
                $a[$i][$nrColumns - 1] = 0;
            }
        }
        return $a;
    }
}