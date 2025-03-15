<?php

namespace isLib;

class LtreeTrf {

    private array $inputTree = [];

    /**
     * Part of $this->normalize, used for debugging
     * 
     * @var array
     */
    private array $summands = [];

    public function getSummands():array {
        return $this->summands;
    }

    function __construct(array $inputTree) {
        $this->inputTree = $inputTree;        
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

    private function isCommTerminal(array $node):bool {
        // return $this->isTerminal($node) || $this->isUnaryMinus($node) && $this->isTerminal($node['u']) || $node['type'] == 'function' || $node['tk'] == '+'; 
        return $node['tk'] != '*';
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

    private function handleDistSubtree(array $node):array {
        $n = $this->copyNodeHeader($node);
        if (isset($node['u'])) {
            // unary node
            $n['u'] = $this->dist($node['u']);
        } elseif ($this->isTerminal($node)) {
            // Do nothing, terminal nodes have no link
        } else {
            // binary node
            $n['l'] = $this->dist($node['l']);
            $n['r'] = $this->dist($node['r']);
        }
        return $n;
    }

    /**
     * We call a tree "handled" if it has no multiplication nodes with "add" subnodes 
     * and no division nodes with left "add" subnodes.
     * 
     * $this->dist returns a "handled" node, which is mathematically equivalent to $node.
     * The strategy is to use recursion to enshure that the subtrees of $node are "handled".
     * 
     * The assumption is, that the subtrees of   
     * @param array $node 
     * @return array 
     */
    private function dist(array $node):array {
        if ($this->isTerminal($node)) {
            return $node;
        }
        if ($this->isMultNode($node)) {
            $l = $this->dist($node['l']);
            $r = $this->dist($node['r']);
            $leftAdd = $this->isAddNode($l);
            $rightAdd = ($this->isAddNode($r) && $node['tk'] != '/'); // No distribution of divisor
            if ($leftAdd && !$rightAdd) {  // EX: (10-2)?4 $node['tk'] = '?', $l['tk] = '-', $l['l'] = 10, $node['r'] = 4               
                // $leftProd = 10*4
                $leftProd = ['tk' => $node['tk'], 'type' => $node['type'], 'restype' => $node['restype']];
                $leftProd['l'] = $l['l'];
                $leftProd['r'] = $node['r'];
                // $rightProd = 2*4
                $rightProd = ['tk' => $node['tk'], 'type' => $node['type'], 'restype' => $node['restype']];
                $rightProd['l'] = $l['r'];
                $rightProd['r'] = $node['r'];
                // $n = 10*4 - 2*4
                $n = ['tk' => $l['tk'], 'type' => $l['type'], 'restype' => $l['restype']];
                $n['l'] = $leftProd;
                $n['r'] = $rightProd;
            } elseif (!$leftAdd && $rightAdd) { // Ex: // 3*(4 + 5) $node['tk'] = '*', $r['tk] = '+', $r['l'] = 4, $node['l'] = 3              
                // $leftProd = 3*4
                $leftProd = ['tk' => $node['tk'], 'type' => $node['type'], 'restype' => $node['restype']];
                $leftProd['l'] = $node['l'];
                $leftProd['r'] = $r['l'];
                // $rightProd = 3*5
                $rightProd = ['tk' => $node['tk'], 'type' => $node['type'], 'restype' => $node['restype']];
                $rightProd['l'] = $node['l'];
                $rightProd['r'] = $r['r'];
                // $n = 10*4 - 2*4
                $n = ['tk' => $r['tk'], 'type' => $r['type'], 'restype' => $r['restype']];
                $n['l'] = $leftProd;
                $n['r'] = $rightProd;
            } elseif ($leftAdd && $rightAdd) { // Ex: // (2+3)*(7-5) = 2*(7-5) + 3*(7-5) = 2*7 - 2*5 + 3*7 - 3*5
                $p1 = ['tk' => $node['tk'], 'type' => $node['type'], 'restype' => $node['restype']]; // 2*7
                $p1['l'] = $l['l'];
                $p1['r'] = $r['l'];
                $p2 = ['tk' => $node['tk'], 'type' => $node['type'], 'restype' => $node['restype']]; // 2*5
                $p2['l'] = $l['l'];
                $p2['r'] = $r['r'];
                $p3 = ['tk' => $node['tk'], 'type' => $node['type'], 'restype' => $node['restype']]; // 3*7
                $p3['l'] = $l['r'];
                $p3['r'] = $r['l'];
                $p4 = ['tk' => $node['tk'], 'type' => $node['type'], 'restype' => $node['restype']]; // 3*5
                $p4['l'] = $l['r'];
                $p4['r'] = $r['r'];
                $addopl = $l['tk'];
                $addopr = $r['tk'];
                // Build left associative "add"
                // First two products
                $n1 = ['tk' => $addopr, 'type' => $r['type'], 'restype' => $r['restype']]; // 2*7 - 2*5
                $n1['l'] = $p1;
                $n1['r'] = $p2;
                // Result and third product. Addop is the first one 
                $n2 = ['tk' => $addopl, 'type' => $r['type'], 'restype' => $r['restype']]; // (2*7 - 2*5) + 3*7
                $n2['l'] = $n1;
                $n2['r'] = $p3;
                // Result and fourth product
                if ($addopl == $addopr) {
                    $addop = '+';
                } else {
                    $addop = '-';
                }
                $n = ['tk' => $addop, 'type' => $r['type'], 'restype' => $r['restype']]; // ((2*7 - 2*5) + 3*7) -3*5
                $n['l'] = $n2;
                $n['r'] = $p4;
            } else {
                $n = $this->handleDistSubtree($node);
            }
        } else {
            // Although $node is no "mult" node, we must still take care of subnodes. So we cannot just return $node
            $n = $this->handleDistSubtree($node);
        }
        return $n;
    }

    public function applyDistLaw():array {
        $result = $this->dist($this->inputTree);
        return $result;
    }

    private function handleEvalSubtree(array $node):array {
        $n = $this->copyNodeHeader($node);
        if (isset($node['u'])) {
            // unary node
            $n['u'] = $this->eval($node['u']);
        } elseif ($this->isTerminal($node)) {
            // Do nothing, terminal nodes have no link
        } else {
            // binary node
            $n['l'] = $this->eval($node['l']);
            $n['r'] = $this->eval($node['r']);
        }
        return $n;
    }


    /**
     * Partialy evaluates $node by replacing subtrees without variables by their evaluated numeric value
     * 
     * @param array $node 
     * @return array 
     */
    private function eval(array $node):array {
        if ($this->isNumeric($node)) {
            return $node;
        }
        if ($node['tk'] == '*' || $node['tk'] == '?') {
            $l = $this->eval($node['l']);
            $r = $this->eval($node['r']);
            if ($this->isNumeric($l) && $this->isNumeric($r)) {
                // Replace the subtree by a number
                $leftval = $this->strToFloat($l['value']);
                $rightval = $this->strToFloat($r['value']);
                $product = $this->floatToStr($leftval * $rightval);
                $n = ['tk' => $product, 'type' => 'number', 'restype' => 'float', 'value' => $product];
            } else {
                $n = $this->handleEvalSubtree($node);
            }
        } elseif ($node['tk'] == '+' || $node['tk'] == '-' && !isset($node['u'])) {
            $l = $this->eval($node['l']);
            $r = $this->eval($node['r']);
            if ($this->isNumeric($l) && $this->isNumeric($r)) {
                // Replace the subtree by a number
                $leftval = $this->strToFloat($l['value']);
                $rightval = $this->strToFloat($r['value']);
                if ($node['tk'] == '+') {
                    $sum = $this->floatToStr($leftval + $rightval);
                } else {
                    $sum = $this->floatToStr($leftval - $rightval);
                }
                $n = ['tk' => $sum, 'type' => 'number', 'restype' => 'float', 'value' => $sum];
            } else {
                $n = $this->handleEvalSubtree($node);
            }
        } else {
            $n = $this->handleEvalSubtree($node);
        }
        return $n;
    }

    public function partEvaluate(array $node):array {
        return $this->eval($node);
    }

    /**
     * On entry $node is a tree beginning with a mult operator
     * The returned tree is a copy of $node, except for a chain of mult nodes, where the right subnode is terminal
     * or both subnaodes are terminal. In the latter case the whole tree ends.
     * Within the chain of terminal factors, the factors are commuted
     * 
     * @param array $node 
     * @return array 
     */
    private function chain(array $node, bool $read,array &$productChain, int &$chainOrdinal, bool &$chainEvenMinus):array {
        if ($this->isMultNode($node) && $this->isCommTerminal($node['r']) && !$this->isCommTerminal($node['l'])) {
            $n = ['tk' => $node['tk'], 'type' => $node['type'], 'restype' => $node['restype']];
            $n['l'] = $this->chain($node['l'], $read, $productChain, $chainOrdinal, $chainEvenMinus);
            if ($read) {
                if ($this->isUnaryMinus($node['r'])) {
                    $hn = $node['r']['u'];
                    $chainEvenMinus = !$chainEvenMinus;
                } elseif ($node['r']['type'] == 'function' || $node['r']['tk'] == '+') {
                    $hn = $this->comm($node['r']);
                } else {
                    $hn = $node['r'];
                }
                $n['r'] = $hn;
                $productChain[$chainOrdinal] = $hn;
            } else {
                $n['r'] = $productChain[$chainOrdinal];
            }
            $chainOrdinal ++;
        } elseif ($this->isMultNode($node) && $this->isCommTerminal($node['l']) && $this->isCommTerminal($node['l'])) {
            $n = ['tk' => $node['tk'], 'type' => $node['type'], 'restype' => $node['restype']];
            if ($read) {
                if ($this->isUnaryMinus($node['l'])) {
                    $hnl = $node['l']['u'];
                    $chainEvenMinus = !$chainEvenMinus;
                } elseif ($node['l']['type'] == 'function') {
                    $hnl = $this->comm($node['l']); 
                } else {
                    $hnl = $node['l'];
                }
                $n['l'] = $hnl;
                if ($this->isUnaryMinus($node['r'])) {
                    $hnr = $node['r']['u'];
                    $chainEvenMinus = !$chainEvenMinus;
                } elseif ($node['r']['type'] == 'function') {
                    $hnr = $this->comm($node['r']); 
                } else {
                    $hnr = $node['r'];
                }
                $n['r'] = $hnr;
                $productChain[$chainOrdinal] = $hnl;
                $chainOrdinal++;
                $productChain[$chainOrdinal] = $hnr;
            } else {
                $n['l'] = $productChain[$chainOrdinal];
                $chainOrdinal++;
                $n['r'] = $productChain[$chainOrdinal];
            }
            $chainOrdinal++;
        } else {
            return $n = $node;
        }
        return $n;
    }

    private function cmpFactors($a, $b) {
        if ($a['type'] == 'variable') {
            if ($b['type'] == 'variable') {
                // both variables
                if (ord($a['tk']) < ord($b['tk'])) {
                    return -1;
                } elseif(ord($a['tk']) > ord($b['tk'])) {
                    return 1;
                } else {
                    return 0;
                }
            } else {
                // $a is variable, $b is number
                return 1;
            }
        } else {
            if ($b['type'] == 'variable') {
                // $a is number, $b is variable
                return -1;
            } elseif ($this->isNumeric($a) && $this->isNumeric($b)) {
                // both are numbers
                $aval = $this->strToFloat($a['value']);
                $bval = $this->strToFloat($b['value']);
                if ($aval < $bval) {
                    return -1;
                } elseif ($aval > $bval) {
                    return 1;
                } else {
                    return 0;
                }
            } else {
                return 0;
            }
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

    private function cmpAdd($a, $b) {
        $an = $a[0];
        $bn = $b[0];
        if ($an['type'] == 'variable') {
            // $an is a variable
            if ($bn['type'] == 'variable') {
                // $an and $bn are both variables
                if (ord($an['tk']) < ord($bn['tk'])) {
                    return -1;
                } else {
                    return +1;
                }
            } else {
                // $an is a variable, but $bn is not
                return 1;
            }
        } else {
            // $an is not a variable
            if ($bn['type'] == 'variable') {
                // $bn is a variable, but $an is not
                return -1;
            } else {
                // Neither $an nor $b are variables
                if ($an['type'] == 'function') {
                    // $an is a function
                    if ($bn['type'] == 'function') {
                        // $an and $bn are both functions
                        return $this->strCmp($an['tk'], $bn['tk']);
                    } else {
                        // $an is a function, but $bn is not
                        return 1;
                    }
                } else {
                    // $an is not a function
                    if ($bn['type'] == 'function') {
                        // $bn is a function, but $an is not
                        return -1;
                    } else {
                        // Neither $an nor $bn are functions
                        if ($this->isNumeric($an)) {
                            // $an is numeric
                            if ($this->isNumeric($bn)) {
                                // $an and $bn are both numeric
                                $aval = $this->strToFloat($an['value']);
                                $bval = $this->strToFloat($bn['value']);
                                if ($aval < $bval) {
                                    return -1;
                                } else {
                                    return 1;
                                }
                            } else {
                                // $an is numeric and $bn is not. $bn is neither a variable nor a function nor a numeric value
                                return 0;
                            }
                        } else {
                            // $an is not numeric
                            if ($this->isNumeric($bn)) {
                                // $an is neither a variable nor a function nor a numeric value
                                return 0;
                            } else {
                                return 0;
                            }
                        }
                    }
                }
                return 0;
            }
        }
    }

    private function multChain(array $node, bool $read, array &$multiplicationChain, int &$chainOrdinal):array {
        $n = ['tk' => $node['tk'], 'type' => $node['type'], 'restype' => $node['restype']];
        if ($this->isMultNode($node['l'])) {
            $n['l'] = $this->multChain($node['l'], $read, $multiplicationChain, $chainOrdinal);
            $hnr = $this->comm($node['r']);
            if ($read) {
                $n['r'] = $hnr;
                $multiplicationChain[$chainOrdinal] = $hnr;
            } else {
                $n['r'] = $multiplicationChain[$chainOrdinal];
            }
            $chainOrdinal++;
        } else {
            $hnl = $this->comm($node['l']);
            $hnr = $this->comm($node['r']);
            if ($read) {
                $n['l'] = $hnl;
                $multiplicationChain[$chainOrdinal] = $hnl;
                $chainOrdinal++;
                $n['r'] = $hnl;
                $multiplicationChain[$chainOrdinal] = $hnr;
            } else {
                $n['l'] = $multiplicationChain[$chainOrdinal];
                $chainOrdinal++;
                $n['r'] = $multiplicationChain[$chainOrdinal];
            }
            $chainOrdinal++;
        }
        return $n;
    }

    private function commMult(array $node):array {
        $multiplicationChain = [];
        $multiplicationOrdinal = 0;
        $extract = $this->multChain($node, true, $multiplicationChain, $multiplicationOrdinal);
        usort($multiplicationChain, [$this, 'cmpFactors']);
        $multiplicationOrdinal = 0;
        return $this->multChain($node, false, $multiplicationChain, $multiplicationOrdinal);
    }

    /**
     * If $read is true, addChain traverses a subtree of summands bottom up (left to right in traditional notation), 
     * registering the summands and the 'tk' of the node preceeding the summand
     * in position 0 and 1 of the elements of $additionChain.
     * 
     * Ex.: 3*2-i generates the tree
     *      3
     *    +
     *      2
     *  -
     *    1     
     * This produces the following $additionChain = [ [3, 'snt'], [2, '+'], [1, '-'] ]
     * Custom sort $this->cmpAdd reorders $additionChain to [ [1, '-'], [2, '+'], [3, 'snt']]
     * The add subtree is rebuilt bottom up, using the reordered chain
     * 
     * @param array $node 
     * @param bool $read 
     * @param array &$additionChain 
     * @param int &$chainOrdinal 
     * @return array 
     */
    private function addChain(array $node, bool $read, array &$additionChain, int &$chainOrdinal): array {
        $n = ['tk' => $node['tk'], 'type' => $node['type'], 'restype' => $node['restype']];
        if ($this->isAddNode($node['l'])) {
            // The add chain continues
            $n['l'] = $this->addChain($node['l'], $read, $additionChain, $chainOrdinal);
            $hnr = $this->comm($node['r']);
            if ($read) {
                $n['r'] = $hnr;
                $additionChain[$chainOrdinal] = [$hnr, $node['tk']];
            } else {
                $n['r'] = $additionChain[$chainOrdinal][0];
                // if we append the sentinel, there was no chain predecesso, so use '+'
                if ($additionChain[$chainOrdinal][1] == 'snt') {
                    $n['tk'] = '+';
                } else {
                    $n['tk'] = $additionChain[$chainOrdinal][1];
                }                
            }
            $chainOrdinal++;
        } else {
            // The add chain stops, both subnodes are addends
            $hnl = $this->comm($node['l']);
            $hnr = $this->comm($node['r']);
            if ($read) {
                $n['l'] = $hnl;
                $additionChain[$chainOrdinal] = [$hnl, 'snt'];                
                $chainOrdinal++;
                $n['r'] = $hnr;
                $additionChain[$chainOrdinal] = [$hnr, $node['tk']];
            } else {
                // We rebuild the top
                $nl = $additionChain[$chainOrdinal][0];
                $opl = $additionChain[$chainOrdinal][1];
                if ($opl == '-') {
                    // we must prepend a unary minus to the left subchain, because in the original the subtree was subtracted
                    $un = ['tk' => '-', 'type' => 'matop', 'restype' => 'float', 'u' => $nl];
                    $n['l'] = $un;
                } else {
                    // The top needs no special handling
                    $n['l'] = $nl;
                }
                $chainOrdinal++;
                $n['r'] = $additionChain[$chainOrdinal][0];
                $n['tk'] = $additionChain[$chainOrdinal][1];
            }
            $chainOrdinal++;
        }
        return $n;
    }


    private function commAdd(array $node): array {
        $additionChain = [];
        $additionOrdinal = 0;
        $extract = $this->addChain($node, true, $additionChain, $additionOrdinal);
        usort($additionChain, [$this, 'cmpAdd']);
        $additionOrdinal = 0;
        return $this->addChain($node, false, $additionChain, $additionOrdinal);
    }

    /**
     * Returns $node with commuted multiplication and addition chains 
     * 
     * @param array $node 
     * @return array 
     */
    private function comm(array $node):array {
        if ($this->isTerminal($node)) {
            // Variable, number, mathconst
            $n = $node;
        } elseif ($this->isMultNode($node)) {
            $n = $this->commMult($node);
        } elseif ($this->isAddNode($node)) {
            $n = $this->commAdd($node);
        } else {
            // Commute subnodes
            $n = ['tk' => $node['tk'], 'type' => $node['type'], 'restype' => $node['restype']];
            if (isset($node['l'])) {
                $n['l'] = $this->comm($node['l']);
            }
            if (isset($node['r'])) {
                $n['r'] = $this->comm($node['r']);
            }
            if (isset($node['u'])) {
                $n['u'] = $this->comm($node['u']);
            }
        }
        return $n;
    }

    public function commuteVariables(array $node):array {
        return $this->comm($node);
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
     * Returns the array $elements sorted by type as first criterion and value or name as second criterion
     * The single elements are arrays, having the nodes to be sorted in position 0. Position 1 is used by commAss functions 
     * 
     * @param array $elements 
     * @return array 
     */
    private function caoSort(array $elements):array {
        $variables = [];
        $functions = [];
        $mathconstants =[];
        $numbers = [];
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
            }
        }
        usort($variables, [$this, 'tkCmp']);
        usort($functions, [$this, 'tkCmp']);
        usort($mathconstants, [$this, 'tkCmp']);
        usort($numbers, [$this, 'valCmp']);
        // return array_merge($variables, $functions, $mathconstants, $numbers);
        return array_merge($numbers, $mathconstants, $functions, $variables);
    }

    private function changeDescSign(array $summands):array {
        foreach($summands as $key => $summand) {
            if ($summand[1][0] == '+') {
                $new = '-';
            } else {
                $new = '+';
            }
            $summands[$key][1][0] = $new;
        }
        return $summands;
    }

    private function recAppendSummands(array $node, array $collection):array {
        if ($this->isNumeric($node) || $node['type'] == 'variable') {
            // Terminal
            $collection[] = [$node, '+'];
        } elseif ($node['tk'] == '+') {
            // Sum: append summands of left and of right subtree
            $collection = array_merge($collection, $this->recAppendSummands($node['l'], $collection), $this->recAppendSummands($node['r'], $collection));
        } elseif ($node['tk'] == '-') {
            // Difference or unary minus
            if (isset($node['u'])) {
                // Negated subtree: remove negation, but change signs of subtree summands
                $negated = $this->changeDescSign($this->recAppendSummands($node['u'], $collection));
                $collection = array_merge($collection, $negated);
            } else {
                // Difference: append summands of left subtree and sign inverted summands of right subtree
                // NOTE: It is essential that both left and right node be merged in the same array_merge, 
                // because the second factor ($collection) must be tehe same in both recAppendSummands.
                // If we want to split the merger in two mergers, we must first register the old $collection, lest we merge the left tree twice
                $collection = array_merge($collection, $this->recAppendSummands($node['l'], $collection),
                                          $this->changeDescSign($this->recAppendSummands($node['r'], $collection)));
            }
        } elseif ($node['type'] == 'function') {
            $func = [$node, '+'];
            $collection[] = $func;
        }
        return $collection;
    }

    /**
     * $node is a parse tree. 
     * collectSummands returns an array of the summands in $node by bottom up traversation.
     * A summand is an array of a terminal summand in position 0 and a descriptor in position 1.
     * A terminal summand is a parse tree with a start node that is one of 'number', 'mathconst', 'variable' or a function summand.
     * In case of 'number', 'mathconst', 'variable' the terminal command is a parse tree. The descriptor is the sign i.e. either '+' or '-'
     * In case of a function summand the terminal command is the array of summands of the argument. 
     * The descriptor is the name of the function preceeded by a '+' or a '-'.
     * Unary minus in $node are handled, by changin the sign in descriptors
     * 
     * @param array $node 
     * @return array 
     */
    private function collectSummands(array $node):array {
        return $this->recAppendSummands($node, []);
    }

    /**
     * $node is a parse tree starting with an 'add' node i.e. '+' or '-' (binary)
     * caoAdd returns a parse tree, with all additions consolidated to a left associative ordered addition tree.
     * 
     * 
     * @param array $node 
     * @return array 
     */
    private function caoAdd(array $node):array {
        $summands = $this->collectSummands($node);
        $nr = count($summands);

        // For debugging display
        $this->summands = $summands;

        // Handle arguments of summands, which are one parameter functions
        for ($i = 2; $i < $nr; $i++) {
            if ($summands[$i][0]['type'] == 'function' && isset($summands[$i][0]['u'])) {
                // Replace argument
                $handledArg = $this->commAssOrd($summands[$i][0]['u']);
                $summands[$i][0]['u'] = $handledArg;
            }
        }

        // Order the summands
        // usort($summands, [$this, 'cmpAdd']);
        $summands = $this->caoSort($summands);

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

    /**
     * Transforms the parse tree $node into a mathematically aequivalent tree 
     * using the commutative and associative property of addition and multiplication.
     * In the result sums and products are left associative and ordered.
     * 
     * @param array $node 
     * @return array 
     */
    public function commAssOrd(array $node):array {
        if ($this->isTerminal($node)) {
            // Variable, number, mathconst
            return $node;
        } elseif ($this->isMultNode($node)) {
            // '*', '?'
            return $node;
        } elseif ($this->isAddNode($node)) {
            // '+', '-' not unary     
            return $this->caoAdd($node);
        } else {
            // Descend
            $n = ['tk' => $node['tk'], 'type' => $node['type'], 'restype' => $node['restype']];
            if (isset($node['l'])) {
                $n['l'] = $this->commAssOrd($node['l']);
            }
            if (isset($node['r'])) {
                $n['r'] = $this->commAssOrd($node['r']);
            }
            if (isset($node['u'])) {
                $n['u'] = $this->commAssOrd($node['u']);
            }
            return $n;
        }
    }
}