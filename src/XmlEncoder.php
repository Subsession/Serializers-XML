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

use Subsession\Serializers\Abstraction\EncoderInterface;

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
class XmlEncoder implements EncoderInterface
{
    const FORMAT = 'xml';

    const AS_COLLECTION = 'as_collection';

    /**
     * An array of ignored XML node types while decoding, each one of the DOM Predefined XML_* constants.
     */
    const DECODER_IGNORED_NODE_TYPES = 'decoder_ignored_node_types';

    /**
     * An array of ignored XML node types while encoding, each one of the DOM Predefined XML_* constants.
     */
    const ENCODER_IGNORED_NODE_TYPES = 'encoder_ignored_node_types';
    const ENCODING = 'xml_encoding';
    const FORMAT_OUTPUT = 'xml_format_output';

    /**
     * A bit field of LIBXML_* constants.
     */
    const LOAD_OPTIONS = 'load_options';
    const REMOVE_EMPTY_TAGS = 'remove_empty_tags';
    const ROOT_NODE_NAME = 'xml_root_node_name';
    const STANDALONE = 'xml_standalone';
    const TYPE_CASE_ATTRIBUTES = 'xml_type_cast_attributes';
    const VERSION = 'xml_version';

    private $defaultContext = [
        self::AS_COLLECTION => false,
        self::DECODER_IGNORED_NODE_TYPES => [XML_PI_NODE, XML_COMMENT_NODE],
        self::ENCODER_IGNORED_NODE_TYPES => [],
        self::LOAD_OPTIONS => LIBXML_NONET | LIBXML_NOBLANKS,
        self::REMOVE_EMPTY_TAGS => false,
        self::ROOT_NODE_NAME => 'response',
        self::TYPE_CASE_ATTRIBUTES => true,
    ];

    /**
     * @var \DOMDocument
     */
    private $dom;
    private $format;
    private $context;

    /**
     * @param array $defaultContext
     */
    public function __construct(
        $defaultContext = []
    ) {
        $this->defaultContext = array_merge($this->defaultContext, $defaultContext);
    }

    /**
     * {@inheritdoc}
     */
    public function encode($data, $format, array $context = [])
    {
        $encoderIgnoredNodeTypes = $context[self::ENCODER_IGNORED_NODE_TYPES] ??
        $this->defaultContext[self::ENCODER_IGNORED_NODE_TYPES];

        $ignorePiNode = \in_array(XML_PI_NODE, $encoderIgnoredNodeTypes, true);

        if ($data instanceof \DOMDocument) {
            return $data->saveXML($ignorePiNode ? $data->documentElement : null);
        }

        $xmlRootNodeName = $context[self::ROOT_NODE_NAME] ?? $this->defaultContext[self::ROOT_NODE_NAME];

        $this->dom = $this->createDomDocument($context);
        $this->format = $format;
        $this->context = $context;

        if (null !== $data && !is_scalar($data)) {
            $root = $this->dom->createElement($xmlRootNodeName);
            $this->dom->appendChild($root);
            $this->buildXml($root, $data, $xmlRootNodeName);
        } else {
            $this->appendNode($this->dom, $data, $xmlRootNodeName);
        }

        return $this->dom->saveXML($ignorePiNode ? $this->dom->documentElement : null);
    }
}
