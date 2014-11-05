<?php

/*
 * This file is part of the 'octris/php-tmdialog' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\TMDialog;

/**
 * Library reading plist XML files.
 *
 * @copyright   copyright (c) 2014 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Plist
{
    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Main parse method.
     *
     * @param   \DOMNode        $node           DOMNode to parse.
     * @return  array                           Data of parsed node.
     */
    protected function parse(\DOMNode $node)
    {
        $type = $node->nodeName;
        $name = 'parse' . ucfirst($type);

        switch ($type) {
            case 'integer':
            case 'string':
            case 'data':
            case 'date':
                $return = $node->textContent;
                break;
            case 'true':
            case 'false':
                $return = ($type == 'true');
                break;
            default:
                if ($type != '' && method_exists($this, $name)) {
                    $return = $this->{$name}($node);
                } else {
                    $return = null;
                }
        }

        return $return;
    }

    /**
     * Parse a plist dictionary.
     *
     * @param   \DOMNode        $node           DOMNode to parse.
     * @return  array                           Data of parsed node.
     */
    public function parseDict(\DOMNode $node)
    {
        $dict = array();

        // for each child of this node
        for ($child = $node->firstChild; $child != null; $child = $child->nextSibling) {
            if ($child->nodeName == 'key') {
                $key = $child->textContent;

                $vnode = $child->nextSibling;

                // skip text nodes
                while ($vnode->nodeType == XML_TEXT_NODE) {
                    $vnode = $vnode->nextSibling;
                }

                // recursively parse the children
                $value = $this->parse($vnode);

                $dict[$key] = $value;
            }
        }

        return $dict;
    }

    /**
     * Parse a plist array.
     *
     * @param   \DOMNode        $node           DOMNode to parse.
     * @return  array                           Data of parsed node.
     */
    protected function parseArray(\DOMNode $node)
    {
        $array = array();

        for ($child = $node->firstChild; $child != null; $child = $child->nextSibling) {
            if ($child->nodeType == XML_ELEMENT_NODE) {
                $array[] = $this->parse($child);
            }
        }

        return $array;
    }

    /**
     * Process plist XML.
     *
     * @param   string          $xml            Plist XML to process.
     * @return  array                           Data of parsed plist XML.
     */
    public function process($xml)
    {
        $plist = new \DOMDocument();
        $plist->loadXML($xml);

        $root = $plist->documentElement->firstChild;

        while ($root->nodeName == '#text') {
            $root = $root->nextSibling;
        }

        return $this->parse($root);
    }
}
