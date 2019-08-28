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

use Comertis\Serializers\Abstraction\EncoderInterface;

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
