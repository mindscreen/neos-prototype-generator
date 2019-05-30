<?php

namespace Mindscreen\Neos\PrototypeGenerator\Domain\Service;


use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\Neos\Domain\Service\DefaultPrototypeGeneratorInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\ObjectAccess;

/**
 * @Flow\Scope("singleton")
 */
class ComponentPrototypeGenerator implements DefaultPrototypeGeneratorInterface
{

    /**
     * @Flow\InjectConfiguration("componentPatterns")
     * @var array
     */
    protected $prototypePatterns;

    /**
     * @Flow\InjectConfiguration("superType")
     * @var string
     */
    protected $prototypeSuperType;

    /**
     * @var int
     */
    protected $indentationLevel = 0;

    /**
     * Generate a Fusion prototype definition for a given node type
     *
     * @param NodeType $nodeType
     * @return string
     */
    public function generate(NodeType $nodeType)
    {
        $nodeTypeOptions = $nodeType->getOptions();
        $optionsKey = 'componentName';
        $rendererType = null;
        if (array_key_exists($optionsKey, $nodeTypeOptions) && is_string($nodeTypeOptions[$optionsKey])) {
            $rendererType = $nodeTypeOptions[$optionsKey];
        }
        $this->indentationLevel = 0;
        $output = '';
        $this->addLine($output, sprintf(
            'prototype(%s) < prototype(%s) {',
            $nodeType->getName(), $this->prototypeSuperType
        ));
        $output .= $this->generateRenderer($nodeType, $rendererType);
        $this->addLine($output, '}');
        return $output;
    }

    /**
     * Helper function adding indentation and line-breaks
     *
     * @param string $output
     * @param string $line
     */
    protected function addLine(&$output, $line)
    {
        if ($line === '}') {
            $this->indentationLevel--;
        }
        $indent = $this->indentationLevel > 0 ? str_repeat(' ', $this->indentationLevel * 4) : '';
        $output .= $indent . $line . chr(10);
        $lastChar = $line[strlen($line) - 1];
        if ($lastChar === '{') {
            $this->indentationLevel++;
        }
    }

    /**
     * If no explicit rendererType is given, it will create a case object trying all prototype-patterns
     * defined in the Setting 'Mindscreen.NodeTypes.prototypeGenerator.componentPatterns'
     *
     * @param NodeType $nodeType
     * @param string $rendererType
     * @return string
     */
    protected function generateRenderer(NodeType $nodeType, $rendererType = null)
    {
        $useRenderer = $rendererType === null;
        if ($rendererType === null) {
            $rendererType = 'Neos.Fusion:Renderer';
        }
        $output = '';
        $this->addLine($output, 'renderer = ' . $rendererType . ' {');
        if ($useRenderer) {
            $this->addLine($output, 'type = Neos.Fusion:Case {');
            foreach ($this->prototypePatterns as $i => $prototypePattern) {
                list($package, $nodeTypeName) = explode(':', $nodeType->getName());
                $prototypeName = str_replace(
                    ['<package>', '<nodetype>'],
                    [$package, $nodeTypeName],
                    $prototypePattern);
                $this->addLine($output, 'pattern' . $i . ' {');
                $this->addLine($output, 'condition = Neos.Fusion:CanRender {');
                $this->addLine($output, 'type = \'' . $prototypeName  . '\'');
                $this->addLine($output, '}');
                $this->addLine($output, 'renderer = \'' . $prototypeName . '\'');
                $this->addLine($output, '}');
            }
            $this->addLine($output, 'default {');
            $this->addLine($output, 'condition = ${true}');
            $this->addLine($output, 'renderer = \'Neos.Neos:FallbackNode\'');
            $this->addLine($output, '}');
            $this->addLine($output, '}');
            $this->addLine($output, 'element {');
            $output .= $this->generatePropertyMapping($nodeType);
            $output .= $this->generateChildNodeMapping($nodeType);
            $this->addLine($output, '}');
        } else {
            $output .= $this->generatePropertyMapping($nodeType);
            $output .= $this->generateChildNodeMapping($nodeType);
        }
        $this->addLine($output, '}');
        return $output;
    }

    /**
     * @param NodeType $nodeType
     * @return string
     */
    protected function generatePropertyMapping(NodeType $nodeType)
    {
        $output = '';
        $propertiesConfiguration = $nodeType->getProperties();
        foreach ($propertiesConfiguration as $propertyName => $propertyConfiguration) {
            if (!isset($propertyName[0]) || $propertyName[0] === '_') {
                continue;
            }
            $propertyType = $nodeType->getConfiguration('properties.' . $propertyName . '.type');
            $inlineEditable = $nodeType->getConfiguration('properties.' . $propertyName . '.ui.inlineEditable');
            $inlineOptions = $nodeType->getConfiguration('properties.' . $propertyName . '.ui.inline.editorOptions');
            $mappingGenerated = false;
            if ($propertyType === 'string') {
                if ($inlineEditable === true) {
                    $this->addLine($output, $propertyName . ' = Neos.Neos:Editable {');
                    $this->addLine($output, 'property = \'' . $propertyName . '\'');
                    if (!is_array($inlineOptions) || !array_key_exists('multiLine', $inlineOptions) || $inlineOptions['multiLine'] !== true) {
                        $this->addLine($output, 'block = false');
                    }
                    $this->addLine($output, '}');
                    $mappingGenerated = true;
                }
            }
            elseif ($propertyType === 'Neos\Media\Domain\Model\ImageInterface') {
                $this->addLine($output, $propertyName . 'Asset = ${q(node).property("' . $propertyName . '")}');
                $this->addLine($output, $propertyName . 'Uri = Neos.Neos:ImageUri {');
                $this->addLine($output, 'asset = ${q(node).property("' . $propertyName . '")}');
                $this->addLine($output, '}');
                $mappingGenerated = true;
            }
            if (!$mappingGenerated) {
                $this->addLine($output, $propertyName . ' = ${q(node).property("' . $propertyName . '")}');
                if ($propertyType === 'string') {
                    $this->addLine($output, $propertyName . '.@process.convertUris = Neos.Neos:ConvertUris');
                }
            }
        }
        return $output;
    }

    /**
     * @param NodeType $nodeType
     * @return string
     */
    protected function generateChildNodeMapping(NodeType $nodeType)
    {
        $output = '';
        $nodeTypeOptions = $nodeType->getOptions();
        if ($nodeType->isOfType('Neos.Neos:ContentCollection')) {
            $nodeTypeOptionsMappingName = ObjectAccess::getPropertyPath(
                $nodeTypeOptions,
                'componentMapping.childNodes.this'
            );
            if (!is_string($nodeTypeOptionsMappingName)) {
                $nodeTypeOptionsMappingName = 'content';
            }
            $this->addLine($output, $nodeTypeOptionsMappingName . ' = Neos.Neos:ContentCollection {');
            $this->addLine($output, 'nodePath = \'.\'');
            $this->addLine($output, '}');
        }
        if (!isset($nodeType->getFullConfiguration()['childNodes']) || !is_array($nodeType->getFullConfiguration()['childNodes'])) {
            return $output;
        }
        foreach ($nodeType->getFullConfiguration()['childNodes'] as $childNodeName => $childNodeConfiguration) {
            if (!isset($childNodeConfiguration['type'])) {
                continue;
            }
            $childNodeType = $childNodeConfiguration['type'];
            $nodeTypeOptionsMappingName = ObjectAccess::getPropertyPath(
                $nodeTypeOptions,
                'componentMapping.childNodes.' . $childNodeName
            );
            $mappingName = $nodeTypeOptionsMappingName ? $nodeTypeOptionsMappingName : $childNodeName;
            $this->addLine($output, $mappingName . ' = ' . $childNodeType . ' {');
            if ($childNodeType === 'Neos.Neos:ContentCollection') {
                $this->addLine($output, 'nodePath = \'' . $childNodeName . '\'');
            } else {
                $this->addLine($output, '@context.node = ${q(node).find(\'' . $childNodeName . '\').get(0)}');
            }
            $this->addLine($output, '}');
        }
        return $output;
    }
}
