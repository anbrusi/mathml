<?php

namespace isLib;

class LtreeTrf {

    private array $inputTree = [];

    function __construct(array $inputTree) {
        $this->inputTree = $inputTree;        
    }

    private function isMultNode(array $node):bool {
        if ($node['tk'] == '*' || $node['tk'] == '?' || $node['tk'] == '/') {
            return true;
        }
        return false;
    }

    private function isAddNode(array $node):bool {
        if ($node['tk'] == '+' || $node['tk'] == '-') {
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

    private function isInvariable(array $node):bool {
        return false;
    }

    private function copyNodeHeader(array $node):array {
        // Mandatory values first
        $n = ['tk' => $node['tk'], 'type' => $node['type'], 'restype' => $node['restype']];
        if (isset($node['value'])) {
            $n['value'] = $node['value'];
        }
        return $n;
    }

    private function handleSubtreeOnly(array $node):array {
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
                $n = $this->handleSubtreeOnly($node);
            }
        } else {
            // Although $node is no "mult" node, we must still take care of subnodes. So we cannot just return $node
            $n = $this->handleSubtreeOnly($node);
        }
        return $n;
    }

    public function applyDistLaw():array {
        $result = $this->dist($this->inputTree);
        return $result;
    }
}