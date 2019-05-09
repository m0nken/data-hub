<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\DataHubBundle\GraphQL\FieldHelper;

use GraphQL\Language\AST\ArgumentNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Type\Definition\ResolveInfo;

abstract class AbstractFieldHelper
{
    /**
     * @param $container
     * @param $astName
     *
     * @return bool
     */
    public function skipField($container, $astName)
    {
        return false;
    }

    /**
     * @param FieldNode $ast
     * @param array $data
     * @param $container
     * @param $args
     * @param ResolveInfo|null $resolveInfo
     */
    public function doExtractData(FieldNode $ast, &$data = [], $container, $args, $context, $resolveInfo = null)
    {
        $astName = $ast->name->value;

        // sometimes we just want to expand relations just to throw them away afterwards because not requested
        if ($this->skipField($container, $astName)) {
            return;
        }

        // example for http://webonyx.github.io/graphql-php/error-handling/
//         throw new MySafeException("fieldhelper", "TBD customized error message");

        $getter = 'get'.ucfirst($astName);
        $arguments = $this->getArguments($ast);
        $languageArgument = isset($arguments['language']) ? $arguments['language'] : null;

        $realName = $astName;

        if (method_exists($container, $getter)) {
            if ($languageArgument) {
                if ($ast->alias) {
                    // defer it
                    $data[$realName] = function ($source, $args, $context, ResolveInfo $info) use (
                        $container,
                        $getter
                    ) {
                        return $container->$getter($args['language']);
                    };
                } else {
                    $data[$realName] = $container->$getter($languageArgument);
                }
            } else {
                $data[$realName] = $container->$getter();
            }
        }
    }


    /**
     * @param FieldNode $ast
     *
     * @return array
     */
    public function getArguments(FieldNode $ast)
    {
        $result = [];
        /** @var $nodeList NodeList */
        $nodeList = $ast->arguments;
        /** @var $iterator \Iterator */
        $count = $nodeList->count();
        for ($i = 0; $i < $count; $i++) {
            /** @var $argumentNode ArgumentNode */
            $argumentNode = $nodeList[$i];
            $value = $argumentNode->value->value;
            $result[$argumentNode->name->value] = $value;
        }

        return $result;
    }

    /**
     * @param array $data
     * @param $container
     * @param $args
     * @param $context array
     * @param ResolveInfo|null $resolveInfo
     *
     * @return array
     */
    public function extractData(&$data = [], $container, $args, $context, ResolveInfo $resolveInfo = null)
    {
        $resolveInfo = (array)$resolveInfo;
        $fieldAstList = (array)$resolveInfo['fieldNodes'];

        foreach ($fieldAstList as $astNode) {
            if ($astNode instanceof FieldNode) {
                /** @var $selectionSet SelectionSetNode */
                $selectionSet = $astNode->selectionSet;
                $selections = $selectionSet->selections;
                if ($selections) {
                    foreach ($selections as $selectionNode) {
                        if ($selectionNode instanceof FieldNode) {
                            $this->doExtractData($selectionNode, $data, $container, $args, $context, $resolveInfo);
                        } else {
                            if ($selectionNode instanceof InlineFragmentNode) {
                                /** @var $selectionSetNode SelectionSetNode */
                                $inlineSelectionSetNode = $selectionNode->selectionSet;
                                /** @var $inlineSelections NodeList[] */
                                $inlineSelections = $inlineSelectionSetNode->selections;
                                $count = $inlineSelections->count();
                                for ($i = 0; $i < $count; $i++) {
                                    $inlineNode = $inlineSelections[$i];
                                    if ($inlineNode instanceof FieldNode) {
                                        $this->doExtractData($inlineNode, $data, $container, $args, $resolveInfo);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $data;
    }
}
