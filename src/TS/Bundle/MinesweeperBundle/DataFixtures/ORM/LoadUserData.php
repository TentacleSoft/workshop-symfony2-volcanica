<?php

namespace TS\Bundle\MinesweeperBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use TS\Bundle\MinesweeperBundle\Entity\User;

class LoadUserData extends AbstractFixture implements OrderedFixtureInterface
{
    private $userData = array(
        array(
            'id' => 1,
            'name' => 'Generated User 1',
            'username' => 'genUser1',
            'password' => '1234',
            'email' => 'user1@volcanica.cat',
        ),
        array(
            'id' => 2,
            'name' => 'Generated User 2',
            'username' => 'genUser2',
            'password' => '1234',
            'email' => 'user2@volcanica.cat',
        )
    );

    public function load(ObjectManager $manager)
    {

    }

    public function getOrder()
    {
        return 1;
    }
}
