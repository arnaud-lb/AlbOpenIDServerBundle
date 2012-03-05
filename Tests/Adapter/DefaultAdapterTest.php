<?php

namespace vendor\bundles\Alb\OpenIDServerBundle\Tests\Adapter;

use Alb\OpenIDServerBundle\Adapter\DefaultAdapter;

class DefaultAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected $adapter;

    public function setUp()
    {
        $this->adapter = new DefaultAdapter;
    }

    public function testGetUserUniqueReturnsUsername()
    {
        $user = $this->getMock('Symfony\Component\Security\Core\User\UserInterface');
        $user->expects($this->once())
            ->method('getUsername')
            ->will($this->returnValue('x'));

        $result = $this->adapter->getUserUnique($user);

        $this->assertSame('x', $result);
    }

    /** @dataProvider getGetUserUniqueWithWrongTypeData */
    public function testGetUserUniqueWithWrongTypeThrowsException($input, $type)
    {
        try {
            $this->adapter->getUserUnique($input);
        } catch(\InvalidArgumentException $e) {
            $this->assertSame($e->getMessage(), sprintf('Expected a Symfony\Component\Security\Core\User\UserInterface, got a %s', $type));
            return;
        }

        $this->fail('Expected InvalidArgumentException was not thrown');
    }

    public function getGetUserUniqueWithWrongTypeData()
    {
        return array(
            array(new \stdClass, 'stdClass'),
            array(1, 'integer'),
            array("foo", 'string'),
        );
    }
}

