<?php
namespace app\Index\controller;

use starsutil\Fibonacci;
use starsutil\Reward;
use starsutil\Convert;
use starsutil\Particle;
use starsutil\Idwork;

class Index 
{
    public function index()
    {
        echo 'index/inex/index';
    }

    public function hello($name = 'ThinkPHP6')
    {
        return 'hello,' . $name;
    }
}
