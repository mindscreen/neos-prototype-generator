<?php

namespace Mindscreen\Neos\PrototypeGenerator\Tests\Functional\Domain\Service;


use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Neos\Domain\Service\FusionService;
use Symfony\Component\Yaml\Parser as YamlParser;

class PrototypeGeneratorTest extends FunctionalTestCase
{
    /**
     * @var NodeTypeManager
     */
    protected $originalNodeTypeManager;

    /**
     * @var NodeTypeManager
     */
    protected $mockNodeTypeManager;

    /**
     * @var YamlParser
     */
    protected $yamlParser;

    /**
     * @var FusionService
     */
    protected $fusionService;

    public function setUp()
    {
        parent::setUp();

        $this->fusionService = $this->objectManager->get(FusionService::class);
        $this->yamlParser = $this->objectManager->get(YamlParser::class);
        $this->originalNodeTypeManager = $this->objectManager->get(NodeTypeManager::class);
        $this->mockNodeTypeManager = clone($this->originalNodeTypeManager);
        $nodeTypesFixtureContent = $this->yamlParser->parse(file_get_contents(
            __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures/NodeTypes.yaml'
        ));
        $this->mockNodeTypeManager->overrideNodeTypes($nodeTypesFixtureContent);
        $this->objectManager->setInstance(NodeTypeManager::class, $this->mockNodeTypeManager);
    }

    public function tearDown()
    {
        $this->objectManager->setInstance(NodeTypeManager::class, $this->originalNodeTypeManager);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function expectCustomPrototype()
    {
        $customPrototypeFusion = $this->generateFusionForNodeType('Acme.Demo:CustomPrototype');
        $expectedFusion = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures/expectCustomPrototype.fusion');
        self::assertSame($expectedFusion, $customPrototypeFusion);
    }

    /**
     * @test
     */
    public function expectDefaultPrototype()
    {
        $defaultPrototypeFusion = $this->generateFusionForNodeType('Acme.Demo:DefaultPrototype');
        $expectedFusion = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures/expectDefaultPrototype.fusion');
        self::assertSame($expectedFusion, $defaultPrototypeFusion);
    }

    /**
     * @test
     */
    public function expectPropertyMapping()
    {
        $propertyMappingFusion = $this->generateFusionForNodeType('Acme.Demo:PropertyMapping');
        $expectedFusion = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures/expectPropertyMapping.fusion');
        self::assertSame($expectedFusion, $propertyMappingFusion);
    }

    protected function generateFusionForNodeType($nodeTypeName)
    {
        $method = new \ReflectionMethod(
            FusionService::class, 'generateFusionForNodeType'
        );
        $method->setAccessible(true);
        $nodeType = $this->mockNodeTypeManager->getNodeType($nodeTypeName);
        return $method->invoke($this->fusionService, $nodeType);
    }
}
