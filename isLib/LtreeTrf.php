<?php

namespace isLib;

use Exception;
use RecursiveArrayIterator;

class LtreeTrf {

    private \isLib\Levaluator $evaluator;

    /**
     * Part of $this->normalize, used for debugging
     * 
     * @var array
     */
    private array $summands = [];
    /**
     * Part of $this->dst used for debuggin
     * @var array
     */
    private array $trfSequence = [];

    function __construct(string $trigUnit) {
        $this->evaluator = new \isLib\Levaluator([], $trigUnit);
    }

    /**
     * Debug function
     * 
     * @return array 
     */
    public function getSummands():array {
        return $this->summands;
    }

    /**
     * Debug function
     * 
     * @return array 
     */
    public function getTrfSequence():array {
        return $this->trfSequence;
    }

    private function isMultNode(array $node):bool {
        if ($node['tk'] == '*' || $node['tk'] == '?') {
            return true;
        }
        return false;
    }

    private function isAddNode(array $node):bool {
        if ($node['tk'] == '+' || $node['tk'] == '-' && !isset($node['u'])) {
            return true;
        }
        return false;
    }

    private function isTerminal(array $node):bool {
        if ($node['type'] == 'number' || $node['type'] == 'mathconst' || $node['type'] == 'variable') {
            return true;
        }
        return false;
    }

    private function isUnaryMinus(array $node):bool {
        return $node['tk'] == '-' && isset($node['u']);
    }

    private function isNumeric($node):bool {
        return ($node['type'] == 'number' || $node['type'] == 'mathconst');
    }

    private function copyNodeHeader(array $node):array {
        // Mandatory values first
        $n = ['tk' => $node['tk'], 'type' => $node['type'], 'restype' => $node['restype']];
        if (isset($node['value'])) {
            $n['value'] = $node['value'];
        }
        return $n;
    }

    /**
     * Converts a PHP float to a string accepted by LasciiLexer
     * The task is not trivial, if we want to be very general and take care of exponential parts
     * 
     * @param float $float 
     * @return string 
     */
    private function floatToStr(float $float):string {
        return "{$float}";
    }

    /**
     * Returns a float from a numeric value accepted by the parser
     * 
     * @param string $str 
     * @return float 
     */
    private function strToFloat(string $str):float {
        return floatval($str);
    }

    /**
     * Returns a mathematically equivalent parse tree to parse tree $node without chains of multiple unary minus as start node
     * NOTE reduceUMC does not work recursively. The result can still contain chains of multiple unary minus but not at the beginning
     * 
     * @param array $node 
     * @return array 
     */
    private function reduceUMC(array $node):array {
        $n = $node;
        $lastNode = $n;
        $even = true;
        while ($this->isUnaryMinus($n)) {
            $lastNode = $n;
            $n = $n['u'];
            $even = !$even;
        }
        if ($even) {
            return $n;
        } else {
            return $lastNode;
        }
    }

    /**
     * $summands is an array of arrays, whose elements have a descriptor string as value for key 1
     * These string have either '+' or '-' as first character.
     * changeDesc changes '+' to '-' and '-' to '+' in the descriptor string of each element of $summands and leaves other parts unchanged
     * The original array with changed descriptors is returned
     * 
     * @param array $summands 
     * @return array 
     * @throws isMathException 
     */
    private function changeDescSign(array $summands):array {
        for ($i = 0; $i < count($summands); $i++) {
            if ($summands[$i][1][0] == '+') {
                $summands[$i][1][0] = '-';
            } elseif ($summands[$i][1][0] == '-') {
                $summands[$i][1][0] = '+';
            } else {
                // Illegal sign in summand
                \isLib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 8);
            }
        }
        return $summands;
    }

    /**
     * $node is a parse tree
     * $summands is an array of summands as described in $this->getSummands
     * recAppSummand appends to summands all directly reachable summands of the left and right subtree in case of a summation operator
     * In case of a unary minus the summands of its subtree are appended with inverted sign
     * If $processor is null subtrees of topmost addition nodes in $node are left unchanged,
     * else they are processed by $processor. 
     * $processor is a function with one parameter, accepting a parse tree as argument and returning a parse tree
     * EX.: 4*3+8*(9+7) as $node with $summands = [] and $processor = null returns [4*3, '+'] and [8*(9+7), '+']
     * The sum 9+7 is not handled. If used in a recursive method handling all sums, only top level summands are handled,
     * because the recursin stops at top level. 
     * If all sums at all levels should be handled, the subtrees of the single summands must be processed by $processor
     * 
     * @param array $node 
     * @param array $summands 
     * @param callable|null $processor 
     * @return array 
     * @throws isMathException 
     */
    private function recAppSummands(array $node, array $summands, callable|null $processor):array {
        if ($node['tk'] == '+') {
            return array_merge($summands, $this->recAppSummands($node['l'], $summands, $processor), $this->recAppSummands($node['r'], $summands, $processor));
        } elseif ($node['tk'] == '-') {
            if ($this->isUnaryMinus($node)) {
                return array_merge($summands, $this->changeDescSign($this->recAppSummands($node['u'], $summands, $processor)));
            } else {
                return array_merge($summands, $this->recAppSummands($node['l'], $summands, $processor), 
                                   $this->changeDescSign($this->recAppSummands($node['r'], $summands, $processor)));
            }
        } else {
            if ($processor == null) {
                $n = $node;
            } else {
                $n = $processor($node);
            }
            $summands[] = [$n, '+'];
            return $summands;
        }
    }

    /**
     * $node is a parse tree
     * getSummands returns an array of directly reachable summands in $node
     * A summand is directly reachable if it can be reached in a descent through the tree $node, 
     * which passes only through summation or unary minus nodes. 
     * A summand is itself an array, with a parse tree in position 0 and a descriptor string in position 1
     * The descriptor is a sign, either '+' or '-'
     * The sign determines if the value of the subtree has to be added or subtracted to get the value
     * of the sum of all summands in such a way, that it is the same as the evaluation of $node
     * If getDirectSummands is used in a recursive method to handle sums, the subtrees of the returned summands
     * must be processed by $processor.
     * 
     * @param array $node 
     * @return array 
     */
    private function getDirectSummands(array $node, callable $processor = null):array {
        return $this->recAppSummands($node, [], $processor);
    }

    private function dstMult(array $node, bool $chgSign):array {
        $isSumL = $this->isAddNode($node['l']);
        $isSumR = $this->isAddNode($node['r']);
        if ($isSumL && $isSumR) {
            // Both factors are sums
            $summandsl = $this->getDirectSummands($node['l']);
            $summandsr = $this->getDirectSummands($node['r']);
            $psummands = [];
            foreach ($summandsl as $sl) {
                foreach ($summandsr as $sr) {
                    $p = ['tk' => '*', 'type' => 'matop', 'restype' => 'float'];
                    $p['r'] = $sr[0];
                    $p['l'] = $sl[0];
                    if ($sl[1] == $sr[1]) {
                        $psummands[] = [$p, '+'];
                    } else {
                        $psummands[] = [$p, '-'];
                    }
                }
            }
        } elseif ($isSumL) {
            // Only the left factor is a sum
            $summandsl = $this->getDirectSummands($node['l']);
            $psl = [];
            // Multiply right subtree by each of $summandsl
            foreach ($summandsl as $sl) {
                $p = ['tk' => '*', 'type' => 'matop', 'restype' => 'float'];
                $p['l'] = $sl[0];
                $p['r'] = $this->distribute($node['r'], $chgSign);
                $psl[] = [$p, $sl[1]];
            }
            $psummands = $psl;
        } elseif ($isSumR) {
            // Only the right factor is a sum
            $summandsr = $this->getDirectSummands($node['r']);
            $psr = [];
            // Multiply left subtree by each of $summandsr
            foreach ($summandsr as $sr) {
                $p = ['tk' => '*', 'type' => 'matop', 'restype' => 'float'];
                $p['l'] = $this->distribute($node['l'], $chgSign);
                $p['r'] = $sr[0];
                $psr[] = [$p, $sr[1]];
            }
            $psummands = $psr;
        } else {
            // Neither factor is a sum
            if ($chgSign) {
                $n = ['tk' => '-', 'type' => 'matop', 'restype' => 'float'];
                $n['u'] = $node;
                return $n;
            } else {
                return $node;
            }
        }


        // Build the summation tree from $psummands
        $nr = count($psummands);
        if ($chgSign) {
            for ($i = 0; $i < $nr; $i++) {
                if ($psummands[$i][1] == '+') {
                    $psummands[$i][1] = '-';
                } else {
                    $psummands[$i][1] = '+';
                }
            }
        }
        if ($nr < 2) {
            \isLib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 1);
        }

        $sign = $psummands[0][1];
        if ($sign == '-') {
            // We need a unary minus for the deepest summand (the first in conventional notation)
            $l = ['tk' => '-', 'type' => 'matop', 'restype' => 'float', 'u' => $psummands[0][0]];
        } else {
            $l = $psummands[0][0];
        }
        $r = $psummands[1][0];
        $n = ['tk' => $psummands[1][1], 'type' => 'matop', 'restype' => 'float', 'l' => $l, 'r' => $r];

        // Add leading summands at the top of the chain
        for ($i = 2; $i < $nr; $i++) {
            $n = ['tk' => $psummands[$i][1], 'type' => 'matop', 'restype' => 'float', 'l' => $n, 'r' => $psummands[$i][0]];
        }

        // Add the intermediate result to $this->trfSequence for debuggin purposes
        $LlateX = new \isLib\Llatex($n);
        $this->trfSequence[]= $LlateX->getLatex();

        return $n;
    }

    /**
     * Returns a parse tree, which is mathematically equivalent to the parse tree $node,
     * without sums as factors within a product
     * 
     * @param array $node 
     * @return array 
     * @throws isMathException 
     * @throws Exception 
     */
    public function distribute(array $node):array {
        if ($this->isTerminal($node)) {
            return $node;
        } elseif ($this->isMultNode($node)) {
            if ($this->isUnaryMinus($node['l']) || $this->isUnaryMinus($node['r'])) {
                $nl = $this->reduceUMC($node['l']);
                $nr = $this->reduceUMC($node['r']);
                $chgSign = false;
                if ($this->isUnaryMinus($nl)) {
                    $nl = $nl['u'];
                    $chgSign = !$chgSign;
                }
                if ($this->isUnaryMinus($nr)) {
                    $nr = $nr['u'];
                    $chgSign = !$chgSign;
                }
                $n = ['tk' => '*', 'type' => 'matop', 'restype' => 'float'];
                $n['l'] = $this->distribute($nl);
                $n['r'] = $this->distribute($nr);          
                return $this->dstMult($n, $chgSign); 
            } else {
                $n = ['tk' => '*', 'type' => 'matop', 'restype' => 'float'];
                $n['l'] = $this->distribute($node['l']);
                $n['r'] = $this->distribute($node['r']);          
                return $this->dstMult($n, false); 
            }               
        } elseif ($this->isAddNode($node)) {
            // '+', '-' not unary     
            $n = ['tk' => $node['tk'], 'type' => 'matop', 'restype' => 'float'];
            $n['l'] = $this->distribute($node['l'], $node['tk']);
            $n['r'] = $this->distribute($node['r'], $node['tk']);
            return $n;
        } elseif ($this->isUnaryMinus($node)) {
            // Unary '-' not parent of an addition node. These have been hndled before together with multiplication nodes
            $trialN = $this->distribute($node['u']);            
            if ($this->isUnaryMinus($trialN)) {
                // Double unary
                return $this->distribute($trialN['u']);
            } else {
                $n = ['tk' => '-', 'type' => 'matop', 'restype' => 'float'];
                $n['u'] = $trialN;
            }
            return $n;
        } elseif ($node['type'] == 'function') {
            $n = ['tk' => $node['tk'], 'type' => 'function', 'restype' => 'float'];
            $n['u'] = $this->distribute($node['u']);
            return $n;
        } elseif ($node['tk'] == '/' || $node['tk'] == '^') {
            // Quotient or Power. Handle numerator and denominator e.g. base and exponentseparately
            $n = ['tk' => $node['tk'], 'type' => 'matop', 'restype' => 'float'];
            $n['l'] = $this->distribute($node['l']);
            $n['r'] = $this->distribute($node['r']);
            return $n;
        } else {
            // Unhandled node in dst
            \isLib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 6);
        }
    }

    /**
     * Returns a parse tree for a numeric node, yelding a value $value.
     * If $value >= 0, it is just one terminal numeric node,
     * if $value < 0 it is a unary minus, whose child is a numeric node, yelding as value the absolut value of $value
     * 
     * @param float $val 
     * @return array 
     */
    private function numNode(float $val):array {
        if ($val < 0) {
            $un = ['tk' => $this->floatToStr(-$val), 'type' => 'number', 'restype' => 'float', 'value' => -$val];
            return ['tk' => '-', 'type' => 'matop', 'restype' => 'float', 'u' => $un];
        } else {
            return ['tk' => $this->floatToStr($val), 'type' => 'number', 'restype' => 'float', 'value' => $val];
        }
    }

    private function addEval(array $node):array {
        $l = $this->selectEval($node['l']);
        $r = $this->selectEval($node['r']);
        if ($this->isNumeric($l) && $this->isNumeric($r)) {
            if ($node['tk'] == '+') {
                $sum = $l['value'] + $r['value'];
                return ['tk' => $this->floatToStr(abs($sum)), 'type' => 'number', 'restype' => 'float', 'value' => $sum];
            } else {
                $difference = $l['value'] - $r['value'];
                return ['tk' => $this->floatToStr(abs($difference)), 'type' => 'number', 'restype' => 'float', 'value' => $difference];
            }
        } elseif ($this->isNumeric($l)) {
            return ['tk' => $node['tk'], 'type' => 'matop', 'restype' => 'float', 'l' => $this->numNode($l['value']), 'r' => $r];
        } elseif ($this->isNumeric($r)) {
            return ['tk' => $node['tk'], 'type' => 'matop', 'restype' => 'float', 'l' => $l, 'r' => $this->numNode($r['value'])];
        } else {
            return ['tk' => $node['tk'], 'type' => 'matop', 'restype' => 'float', 'l' => $l, 'r' => $r];
        }
    }

    private function multEval(array $node):array {
        $l = $this->selectEval($node['l']);
        $r = $this->selectEval($node['r']);
        if ($this->isNumeric($l) && $this->isNumeric($r)) {
            $product = $l['value'] * $r['value'];
            return ['tk' => $this->floatToStr(abs($product)), 'type' => 'number', 'restype' => 'float', 'value' => $product];
        } elseif ($this->isNumeric($l)) {
            return ['tk' => '*', 'type' => 'matop', 'restype' => 'float', 'l' => $this->numNode($l['value']), 'r' => $r];
        } elseif ($this->isNumeric($r)) {
            return ['tk' => '*', 'type' => 'matop', 'restype' => 'float', 'l' => $l, 'r' => $this->numNode($r['value'])];
        } else {
            return ['tk' => '*', 'type' => 'matop', 'restype' => 'float', 'l' => $l, 'r' => $r];
        }
    }

    private function unminEval(array $node):array {
        $subnode = $this->selectEval($node['u']);
        if ($this->isNumeric($subnode)) {
            $subnode['value'] = -$subnode['value'];
            return $subnode;
        } else {
            return ['tk' => '-', 'type' => 'matop', 'restype' => 'float', 'u' => $subnode];
        }
    }

    private function fncEval(array $node):array {
        $u = $this->selectEval($node['u']);
        if ($this->isNumeric($u)) {
            $value = $this->evaluator->evaluateFunction($node);
            $n = ['tk' => $this->floatToStr(abs($value)),'type' => 'number', 'restype' => 'float', 'value' => $value];
        } else {
            $n = ['tk' => $node['tk'], 'type' => 'function', 'restype' => 'float', 'u' => $u];
        }
        return $n;
    }

    private function powerEval(array $node):array {
        $base = $this->selectEval($node['l']);
        $exponent = $this->selectEval($node['r']);
        if ($this->isNumeric($base) && $this->isNumeric($exponent)) {
            $power = pow($base['value'], $exponent['value']);
            if (is_nan($power)) {
                // Illegal numeric power
                \islib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 11);
            }
            return ['tk' => $this->floatToStr(abs($power)), 'type' => 'number', 'restype' => 'float', 'value' => $power];
        } elseif ($this->isNumeric($base)) {
            return ['tk' => '^', 'type' => 'matop', 'restype' => 'float', 'l' => $this->numNode($base['value']), 'r' => $exponent];
        } elseif ($this->isNumeric($exponent)) {
            return ['tk' => '^', 'type' => 'matop', 'restype' => 'float', 'l' => $base, 'r' => $this->numNode($exponent['value'])];
        } else {
            return ['tk' => '^', 'type' => 'matop', 'restype' => 'float', 'l' => $base, 'r' => $exponent];
        }
    }

    private function divEval(array $node):array {
        $dividend = $this->selectEval($node['l']);
        $divisor = $this->selectEval($node['r']);
        if ($this->isNumeric($dividend) && $this->isNumeric($divisor)) {
            $quotient = $dividend['value'] / $divisor['value'];
            if (is_nan($quotient)) {
                // Illegal numeric quotient
                \islib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 12);
            }
            return ['tk' => $this->floatToStr(abs($quotient)), 'type' => 'number', 'restype' => 'float', 'value' => $quotient];
        } elseif ($this->isNumeric($dividend)) {
            return ['tk' => '/', 'type' => 'matop', 'restype' => 'float', 'l' => $this->numNode($dividend['value']), 'r' => $divisor];
        } elseif ($this->isNumeric($divisor)) {
            return ['tk' => '/', 'type' => 'matop', 'restype' => 'float', 'l' => $dividend, 'r' => $this->numNode($divisor['value'])];
        } else {
            return ['tk' => '/', 'type' => 'matop', 'restype' => 'float', 'l' => $dividend, 'r' => $divisor];
        }
    }

    private function selectEval(array $node):array {
        if ($this->isTerminal($node)) {
            return $node;
        } elseif ($this->isAddNode($node)) {
            return $this->addEval($node);
        } elseif ($this->isMultNode($node)) {
            return $this->multEval($node);
        } elseif ($node['type'] == 'function' && isset($node['u'])) {
            return $this->fncEval($node); 
        } elseif ($node['tk'] == '-' && isset($node['u'])) {
            // Unary minus
            return $this->unminEval($node);
        } elseif ($node['tk'] == '^') {
            return $this->powerEval($node);
        } elseif ($node['tk'] == '/') {
            return $this->divEval($node);
        } else {
            // Unhandled node in selectEval
            \isLib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 10);
        }
    }

    /**
     * Partial evaluation introduces negative numbers in parse trees
     * Unary minus applied to a number node is removed and the value of the number node change the sign
     * If partEvaluate is just a number node, it can be a negative one, which is not a legal parse tree
     * Therefore it is changed to a unary minus node having as child the number node with absolute value
     * 
     * @param array $node 
     * @return array 
     * @throws isMathException 
     */
    public function partEvaluate(array $node):array {
        $n = $this->selectEval($node);
        if ($this->isNumeric($n)) {
            return $this->numNode($n['value']);
        } else {
            return $n;
        }
    }

    /**
     * If $a lexicografically preceeds $b returns -1 else +1 or 0 in case they are identical
     * 
     * @param mixed $a 
     * @param mixed $b 
     * @return int 
     */
    private function strCmp($a, $b) {
        $i = 0;
        $la = strlen($a);
        $lb = strlen($b);
        while ($i < $la && $i < $lb) {
            if (ord($a[$i]) < ord($b[$i])) {
                return -1;
            } elseif (ord($a[$i]) > ord($b[$i])) {
                return 1;
            }
            $i++;
        }
        // If we get here common positions are equal, so we prefer the shorter
        if ($la < $lb) {
            return -1;
        } elseif ($la > $lb) {
            return 1;
        }
        // $a and $b are identical
        return 0;
    }

    private function tkCmp($a, $b) {
        return $this->strCmp($a[0]['tk'], $b[0]['tk']);
    }

    private function valCmp($a, $b) {
        $vala = $this->strToFloat($a[0]['value']);
        $valb = $this->strToFloat($b[0]['value']);
        if ($vala < $valb) {
            return -1;
        } elseif ($vala > $valb) {
            return 1;
        }
        return 0;
    }

    /**
     * $a and $b are arrays with the payload in position 0 and an order annotation in position 1
     * The order annotation itself is an array with a float or null in position 1 (secondary order) and a string in position 1 (primary order)
     * 
     * @param mixed $a 
     * @param mixed $b 
     * @return void 
     */
    private function prodCmp($a, $b) {
        $stra = $a[1][1];
        $strb = $b[1][1];
        $primary = $this->strCmp($stra,$strb);
        if ($primary != 0) {
            return $primary;
        }
        $vala = $a[1][0];
        $valb = $b[1][0];
        if ($vala < $valb) {
            return -1;
        } elseif ($vala > $valb) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * Returns the array $elements sorted by type as first criterion and value or name as second criterion
     * The single elements are arrays, having the nodes to be sorted in position 0. Position 1 is used by commAss functions 
     * 
     * @param array $elements 
     * @return array 
     */
    private function sortMult(array $elements):array {
        $variables = [];
        $functions = [];
        $mathconstants =[];
        $numbers = [];
        $quotients = [];
        $powers = [];
        $additions = [];
        foreach ($elements as $element) {
            switch ($element[0]['type']) {
                case 'variable':
                    $variables[] = $element;
                    break;
                case 'function':
                    $functions[] = $element;
                    break;
                case 'mathconst':
                    $mathconstants[] = $element;
                    break;
                case 'number':
                    $numbers[] = $element;
                    break;
                case 'matop':
                    if ($element[0]['tk'] == '/') {
                        $quotients[] = $element;
                    } elseif ($element[0]['tk'] == '^') {
                        $powers[] = $element;
                    } elseif ($this->isAddNode($element[0])) {
                        $additions[] = $element;
                    } elseif ($this->isMultNode($element[0])) {
                        // Unexpected mult node
                        \isLib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 4);
                    }
            }
        }
        usort($variables, [$this, 'tkCmp']);
        usort($functions, [$this, 'tkCmp']);
        usort($mathconstants, [$this, 'tkCmp']);
        usort($numbers, [$this, 'valCmp']);
        return array_merge($numbers, $mathconstants, $additions, $functions, $quotients, $powers, $variables);
    }

    private function recAppFactors(array $node, array $factors, bool &$even):array {
        if ($this->isMultNode($node)) {
            $lf = $this->recAppFactors($node['l'], $factors, $even);
            $rf = $this->recAppFactors($node['r'], $factors, $even);
            $merger = array_merge($factors, $lf, $rf);
            return $merger;
        } elseif ($this->isUnaryMinus($node)) {
            $uf = $this->recAppFactors($node['u'], $factors, $even);
            $even = $even == true ? false : true;
            $merger = array_merge($factors, $uf);
            return $merger;
        } else {
            $factors[] = [$node, ''];
            return $factors;
        }
    }

    private function getDirectFactors(array $node, bool &$even):array {
        return $this->recAppFactors($node, [], $even);
    }

    /**
     * Returns a parse tree mathematically equivalent to the parse tree $node, in which all products are ordered
     * The first node in $node is a product
     * 
     * @param array $node 
     * @return array 
     */
    private function ordProd(array $node):array {
        $even = true;
        $factors = $this->getDirectFactors($node, $even);
        $factors = $this->sortMult($factors);

        $nr = count($factors);

        // Build the consolidatet tree
        // ===========================

        // The first two factors constitute the end of the product chain
        if ($nr < 2) {
            // Factor array below 2
            \isLib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 2);
        }
        $l = $factors[0][0];
        $r = $factors[1][0];
        $n = ['tk' => '*', 'type' => 'matop', 'restype' => 'float', 'l' => $l, 'r' => $r];

        // Add leading factors at the top of the chain
        for ($i = 2; $i < $nr; $i++) {
            $n = ['tk' => '*', 'type' => 'matop', 'restype' => 'float', 'l' => $n, 'r' => $factors[$i][0]];
        }
        if (!$even) {
            $n = ['tk' => '-', 'type' => 'matop', 'restype' => 'float', 'u' => $n];
        }

        // Add the intermediate result to $this->trfSequence for debuggin purposes
        $LlateX = new \isLib\Llatex($n);
        $this->trfSequence[]= $LlateX->getLatex();
        return $n;
    }

    /**
     * Returns a parse tree mathematically equivalent to the parse tree $node, with ordered products
     * 
     * @param array $node 
     * @return array 
     */
    public function ordProducts(array $node):array {
        if ($this->isTerminal($node)) {
            return $node;
        } elseif ($this->isMultNode($node)) {
            return $this->ordProd($node);
        } elseif ($this->isAddNode($node)) {
            $n = ['tk' => $node['tk'], 'type' => 'matop', 'restype' => 'float'];
            $n['l'] = $this->ordProducts($node['l']);
            $n['r'] = $this->ordProducts($node['r']);
            return $n;
        } elseif ($this->isUnaryMinus($node)) {
            $n = ['tk' => '-', 'type' => 'matop', 'restype' => 'float'];
            $n['u'] = $this->ordProducts($node['u']);
            return $n;
        } elseif ($node['type'] == 'function') {
            $n = ['tk' => $node['tk'], 'type' => 'function', 'restype' => 'float'];
            $n['u'] = $this->ordProducts($node['u']);
            return $n;
        } elseif ($node['tk'] == '/' || $node['tk'] == '^') {
            // Quotient or Power. Handle numerator and denominator e.g. base and exponentseparately
            $n = ['tk' => $node['tk'], 'type' => 'matop', 'restype' => 'float'];
            $n['l'] = $this->ordProducts($node['l']);
            $n['r'] = $this->ordProducts($node['r']);
            return $n;
        } else {
            // Unhandled node in product ordering
            \isLib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 3);
        }
    }

    /**
     * $node is a parse tree, whose first node is not a multiplication
     * dcmpFactor returns an array which in position 0 has the computable part of $node or null
     * and in position 1 the name of the variable, if $node is a variable, or the empty string
     * 
     * @param array $node 
     * @return array 
     */
    private function decmpFactor(array $node):array {
        if ($node['type'] == 'variable') {
            // variable in chain, append it
            $result = [null, $node['tk']];
        } elseif ($this->isNumeric($node) || $node['type'] == 'function' && isset($node['u']) || $node['tk'] == '^' || $node['tk'] == '/') {
            $Levaluator = new \isLib\Levaluator([], \isLib\Lconfig::CF_TRIG_UNIT);
            try {
                $numValue = $Levaluator->evaluate($node);
                $result = [$numValue, ''];
            } catch (\Exception $ex) {
                // This happens if a function cannot be evaluated, because the argument contains a vriable
                $result = [null, ''];
            } 
        } else {
            // Do not handle
            $result = [null, ''];
        }
        return $result;
    }

    /**
     * Collects varaiables and the computable part of the parse tree $node, which is a chain of multiplications.
     * by traversing the tree.
     * 
     * @param array $node 
     * @param float|null &$numValue 
     * @param string &$strValue 
     * @return void 
     */
    private function decmp(array $node, float|null &$numValue, string &$strValue):void{
        if ($this->isMultNode($node['l'])) {
            // The chain continues
            $this->decmp($node['l'], $numValue, $strValue);
            $svr = $this->decmpFactor($node['r']);
            if ($svr[0] !== null) {
                if ($numValue === null) {
                    $numValue = $svr[0];
                } else {
                    $numValue *= $svr[0];
                }
            }
            if (!empty($svr[1])) {
                $strValue .= '_'.$svr[1];
            }
        } else {
            // We are at the end of the multiplication chain
            $svl = $this->decmpFactor($node['l']);
            $svr = $this->decmpFactor($node['r']);
            if ($svl[0] !== null && $svr[0] !== null) {
                $numValue = $svl[0]*$svr[0];
            } elseif ($svl[0] !== null) {
                $numValue = $svl[0];
            } elseif ($svr[0] !== null) {
                $numValue = $svr[0];
            } 
            if (!empty($svl[1])) {
                $strValue = '_'.$svl[1];
            }
            if (!empty($svr[1])) {
                $strValue .= '_'.$svr[1];
            }
        }
    }

    /**
     * $product is a parse tree, which is decomposed to an array with a float in position 0 and a string in position 1
     * The float is the fully computed numeric part of $product, the string the variable part
     * $product is an already ordered product
     * Factors in $product, which are neiher variables nor computable, such as functions with variable arguments are skipped
     * Ex.; 5 * a * sin(a + 30) * 8 yield [40, a] 
     * 
     * @param array $product 
     * @return array 
     */
    private function decompose(array $product):array {
        $numValue = null;
        $strValue = '';
        $this->decmp($product, $numValue, $strValue);
        return [$numValue, $strValue];
    }

    /**
     * $products are summands, which themselves are products. Ex.: 2*3*b + 7*9*a - 9*10
     * 
     * To impose an order on products, they are split in a fully computed numeic part and a product of variables
     * The primary order is the lexicografic order of the variable part, the secondary order the numeric order of the computed part
     * Each product is an array with a parse tree in position 0 and a sign in position 1
     * Factors, that are neither variables nor computable, such as functions with variable arguments are neglected
     * 
     * @param array $products 
     * @return array 
     */
    private function sortProducts(array $products):array {
        $split = [];
        $annotated = [];
        $nr = count($products);
        for ($i = 0; $i < $nr; $i++) {
            $split[$i] = $this->decompose($products[$i][0]);
            // Add specialized product annotation
            $annotated[$i] = [$products[$i], $split[$i]];
        }
        usort($annotated, [$this, 'prodCmp']);
        // Strip additional annotation
        $sorted = [];
        for ($i = 0; $i < $nr; $i++) {
            $sorted[$i] = $annotated[$i][0];
        }
        return $sorted;
    }

    /**
     * Returns the array $elements sorted by type as first criterion and value or name as second criterion
     * The single elements are arrays, having the nodes to be sorted in position 0. Position 1 is used by commAss functions 
     * 
     * @param array $elements 
     * @return array 
     */
    private function sortAdd(array $elements):array {
        $variables = [];
        $functions = [];
        $mathconstants =[];
        $numbers = [];
        $products = [];
        $quotients = [];
        $powers = [];
        foreach ($elements as $element) {
            switch ($element[0]['type']) {
                case 'variable':
                    $variables[] = $element;
                    break;
                case 'function':
                    $functions[] = $element;
                    break;
                case 'mathconst':
                    $mathconstants[] = $element;
                    break;
                case 'number':
                    $numbers[] = $element;
                    break;
                case 'matop':
                    if ($this->isMultNode($element[0])) {
                        $products[] = $element;
                    } elseif ($this->isUnaryMinus($element[0]) && $this->isMultNode($element[0]['u'])) {
                        // negated product
                        $products[] = [$element[0]['u'], '-'];
                    } elseif ($element[0]['tk'] == '/') {
                        $quotioents[] = $element;
                    } elseif ($element[0]['tk'] == '^') {
                        $powers[] = $element;
                    } elseif ($this->isAddNode($element[0])) {
                        // Unexpected add node
                        \isLib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 5);
                    } else {
                        // Unhandled node in sum ordering
                        \isLib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 7);
                    }
                    break;
                default:
                    // Unhandled node in sum ordering
                    \isLib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 7);
            }
        }
        usort($variables, [$this, 'tkCmp']);
        usort($functions, [$this, 'tkCmp']);
        usort($mathconstants, [$this, 'tkCmp']);
        usort($numbers, [$this, 'valCmp']);
        $products = $this->sortProducts($products);
        return array_merge($numbers, $mathconstants, $products, $functions, $quotients, $powers, $variables);
    }

    private function ordSumChain(array $node):array {
        $summands = $this->getDirectSummands($node, [$this, 'ordSums']);
        $nr = count($summands);

        // For debugging display
        $this->summands = $summands;

        // Order the summands
        $summands = $this->sortAdd($summands);

        $nr = count($summands);

        // Build the consolidatet tree
        // ===========================

        // The first two summands constitute the end of the addition chain
        if ($nr < 2) {
            // Summand array below 2
            \isLib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 1);
        }
        $sign = $summands[0][1];
        if ($sign == '-') {
            // We need a unary minus for the deepest summand (the first in conventional notation)
            $l = ['tk' => '-', 'type' => 'matop', 'restype' => 'float', 'u' => $summands[0][0]];
        } else {
            $l = $summands[0][0];
        }
        $r = $summands[1][0];
        $n = ['tk' => $summands[1][1], 'type' => 'matop', 'restype' => 'float', 'l' => $l, 'r' => $r];

        // Add leading summands at the top of the chain
        for ($i = 2; $i < $nr; $i++) {
            $n = ['tk' => $summands[$i][1], 'type' => 'matop', 'restype' => 'float', 'l' => $n, 'r' => $summands[$i][0]];
        }
        return $n;
    }

    public function ordSums(array $node):array {
        if ($this->isTerminal($node)) {
            return $node;
        } elseif ($this->isMultNode($node)) {
            $n = ['tk' => '*', 'type' => 'matop', 'restype' => 'float'];
            $n['l'] = $this->ordSums($node['l']);
            $n['r'] = $this->ordSums($node['r']);
            return $n;
        } elseif ($this->isAddNode($node)) {
            return $this->ordSumChain($node);
        } elseif ($this->isUnaryMinus($node)) {            
            $n = ['tk' => '-', 'type' => 'matop', 'restype' => 'float'];
            $n['u'] = $this->ordSums($node['u']);
            return $n;
        } elseif ($node['type'] == 'function') {
            $n = ['tk' => $node['tk'], 'type' => 'function', 'restype' => 'float'];
            $n['u'] = $this->ordSums($node['u']);
            return $n;
        } elseif ($node['tk'] == '/' || $node['tk'] == '^') {
            // Quotient or Power. Handle numerator and denominator e.g. base and exponentseparately
            $n = ['tk' => $node['tk'], 'type' => 'matop', 'restype' => 'float'];
            $n['l'] = $this->ordSums($node['l']);
            $n['r'] = $this->ordSums($node['r']);
            return $n;
        } else {
            // Unhandled node in sum ordering
            \isLib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 7);
        }
    }

    public function expand(array $node):array {
        $Llatex = new \isLib\Llatex([]);
        $distributed = $this->distribute($node);
        $multOrdered = $this->ordProducts($distributed);
        $addOrdered = $this->ordSums($multOrdered);
        $partEvaluated = $this->partEvaluate($addOrdered);

        $this->trfSequence = [];
        $this->trfSequence[] = $Llatex->nodeToLatex($distributed);
        $this->trfSequence[] = $Llatex->nodeToLatex($multOrdered);
        $this->trfSequence[] = $Llatex->nodeToLatex($addOrdered);
        return $partEvaluated;
    }
}