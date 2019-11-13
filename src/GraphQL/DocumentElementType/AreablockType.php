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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\DataHubBundle\GraphQL\DocumentElementType;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

class AreablockType extends ObjectType
{
    protected static $instance;

    public static function getInstance()
    {
        if (!self::$instance) {
            $config =
                [
                    'name' => "document_tagAreablock",
                    'fields' => [
                        '__tagName' => [
                            'type' => Type::string(),
                            'resolve' => static function ($value = null, $args = [], $context, ResolveInfo $resolveInfo = null) {
                                if ($value) {
                                    return $value->getName();
                                }
                            }
                        ],
                        '__tagType' => [
                            'type' => Type::string(),
                            'resolve' => static function ($value = null, $args = [], $context, ResolveInfo $resolveInfo = null) {
                                if ($value instanceof \Pimcore\Model\Document\Tag\Areablock) {
                                    return $value->getType();
                                }
                            }
                        ],
                        'data' => [
                            'type' => Type::listOf(Type::int()),
                            'resolve' => static function ($value = null, $args = [], $context, ResolveInfo $resolveInfo = null) {
                                if ($value instanceof \Pimcore\Model\Document\Tag\Areablock) {
                                    $indices = $value->getData();
                                    $result = [];
                                    foreach($indices as $index) {
                                        if (!$index['hidden']) {
                                            $result[] = (int) $index['key'];
                                        }
                                    }
                                    return $result;
                                }
                            }
                        ]
                    ],
                ];
            self::$instance = new static($config);
        }

        return self::$instance;
    }
}
