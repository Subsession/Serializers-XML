<?php
/**
 * PHP Version 7
 *
 * LICENSE:
 * Copyright 2019 Subsession
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without restriction,
 * including without limitation the rights to use, copy, modify, merge,
 * publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
 * CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE
 * OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @category Serializers
 * @package  Subsession\Serializers
 * @author   Cristian Moraru <cristian.moraru@live.com>
 * @license  https://opensource.org/licenses/MIT MIT
 * @version  GIT: &Id&
 * @link     https://github.com/Subsession/Serializers-XML
 */

namespace Subsession\Serializers\Xml;

use Subsession\Exceptions\ArgumentException;
use Subsession\Exceptions\ArgumentNullException;
use Subsession\Exceptions\InvalidOperationException;
use Subsession\Serializers\Abstraction\DecoderInterface;

/**
 * Undocumented class
 *
 * @category Serializers
 * @package  Subsession\Serializers
 * @author   Cristian Moraru <cristian.moraru@live.com>
 * @license  https://opensource.org/licenses/MIT MIT
 * @version  Release: 1.0.0
 * @link     https://github.com/Subsession/Serializers-XML
 */
class XmlDecoder implements DecoderInterface
{
    /**
     * {@inheritdoc}
     */
    public function decode($data, $format, array $context = [])
    {
        if ("" === trim($data)) {
            throw new ArgumentNullException("Invalid XML data, it can not be empty.");
        }

        $internalErrors = libxml_use_internal_errors(true);
        $disableEntities = libxml_disable_entity_loader(true);
        libxml_clear_errors();

        $dom = new \DOMDocument();
        $dom->loadXML($data, $context[self::LOAD_OPTIONS] ?? $this->defaultContext[self::LOAD_OPTIONS]);

        libxml_use_internal_errors($internalErrors);
        libxml_disable_entity_loader($disableEntities);

        if ($error = libxml_get_last_error()) {
            libxml_clear_errors();

            throw new InvalidOperationException($error->message);
        }

        $rootNode = null;
        $decoderIgnoredNodeTypes = $context[self::DECODER_IGNORED_NODE_TYPES] ??
        $this->defaultContext[self::DECODER_IGNORED_NODE_TYPES];

        foreach ($dom->childNodes as $child) {
            if (XML_DOCUMENT_TYPE_NODE === $child->nodeType) {
                throw new ArgumentException("Document types are not allowed.");
            }
            if (!$rootNode && !\in_array($child->nodeType, $decoderIgnoredNodeTypes, true)) {
                $rootNode = $child;
            }
        }

        // todo: throw an exception if the root node name is not correctly configured (bc)

        if ($rootNode->hasChildNodes()) {
            $xpath = new \DOMXPath($dom);
            $data = [];
            foreach ($xpath->query("namespace::*", $dom->documentElement) as $nsNode) {
                $data["@" . $nsNode->nodeName] = $nsNode->nodeValue;
            }

            unset($data["@xmlns:xml"]);

            if (empty($data)) {
                return $this->parseXml($rootNode, $context);
            }

            return array_merge($data, (array) $this->parseXml($rootNode, $context));
        }

        if (!$rootNode->hasAttributes()) {
            return $rootNode->nodeValue;
        }

        $data = [];

        foreach ($rootNode->attributes as $attrKey => $attr) {
            $data["@" . $attrKey] = $attr->nodeValue;
        }

        $data["#"] = $rootNode->nodeValue;

        return $data;
    }
}
