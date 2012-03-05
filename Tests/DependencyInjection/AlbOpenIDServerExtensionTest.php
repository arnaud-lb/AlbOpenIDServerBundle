<?php

namespace Alb\OpenIDServerBundle\Tests\DependencyInjection;

use Alb\OpenIDServerBundle\DependencyInjection\AlbOpenIDServerExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AlbOpenIDServerExtensionTest extends \PHPUnit_Framework_TestCase
{
    protected $container;
    protected $extension;

    public function setUp()
    {
        $this->container = new ContainerBuilder;
        $this->extension = new AlbOpenIDServerExtension();
    }

    public function tearDown()
    {
        $this->container = null;
        $this->extension = null;
    }

    public function testAdapterServiceHasADefaultValue()
    {
        $config = $this->getMinimalConfig();
        $this->extension->load(array($config), $this->container);

        $alias = $this->container->getAlias('alb_open_id_server.adapter');
        $this->assertSame('alb_open_id_server.default_adapter', (string) $alias);
    }

    public function testAdapterServiceCanBeChanged()
    {
        $config = $this->getMinimalConfig();
        $config['service']['adapter'] = 'custom';
        $this->extension->load(array($config), $this->container);

        $alias = $this->container->getAlias('alb_open_id_server.adapter');
        $this->assertSame('custom', (string) $alias);
    }

    /**
     * @expectedException Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The path "alb_open_id_server.service.adapter" cannot contain an empty value, but got null.
     */
    public function testAdapterServiceCanNotBeEmpty()
    {
        $config = $this->getMinimalConfig();
        $config['service']['adapter'] = null;
        $this->extension->load(array($config), $this->container);
    }

    protected function getMinimalConfig()
    {
        return array();
    }
}

