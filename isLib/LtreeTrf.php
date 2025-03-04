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

    /**
     * We call a tree "handled" if it has no "mult" nodes with "add" subnodes. 
     * dist returns a "handled" node, which is mathematically equivalent to $node.
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
            $rightAdd = $this->isAddNode($r);
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
            } elseif ($leftAdd && $rightAdd) {

            } else {
                $n = ['tk' => $node['tk'], 'type' => $node['type'], 'restype' => $node['restype']];
                $n['l'] = $this->dist($node['l']);
                $n['r'] = $this->dist($node['r']);
            }
        } else {
            // Although $node is no "mult" node, we must still take care of subnodes. So we cannot just return $node
            $n = ['tk' => $node['tk'], 'type' => $node['type'], 'restype' => $node['restype']];
            $n['l'] = $this->dist($node['l']);
            $n['r'] = $this->dist($node['r']);
        }
        return $n;
    }

    public function applyDistLaw():array {
        $result = $this->dist($this->inputTree);
        return $result;
    }
}