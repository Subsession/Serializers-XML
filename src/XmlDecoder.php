<?php
/**
 * PHP Version 7
 *
 * LICENSE:
 * Proprietary, see the LICENSE file that was provided with the software.
 *
 * Copyright (c) 2019 - present Comertis <info@comertis.com>
 *
 * @category Serializers
 * @package  Comertis\Serializers
 * @author   Cristian Moraru <cristian@comertis.com>
 * @license  Proprietary
 * @version  GIT: &Id&
 * @link     https://github.com/Comertis/Serializers
 */

namespace Comertis\Serializers\Xml;

use Comertis\Exceptions\ArgumentException;
use Comertis\Exceptions\ArgumentNullException;
use Comertis\Exceptions\InvalidOperationException;
use Comertis\Serializers\Abstraction\DecoderInterface;

/**
 * Undocumented class
 *
 * @category Serializers
 * @package  Comertis\Serializers
 * @author   Cristian Moraru <cristian@comertis.com>
 * @license  Proprietary
 * @version  Release: 1.0.0
 * @link     https://github.com/Comertis/Serializers
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
