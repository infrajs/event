<?php
namespace infrajs\access;
use infrajs\event\Event;
use infrajs\infra\Infra;
use infrajs\path\Path;

$conf=&Infra::config('event');
Event::$conf=array_merge(Event::$conf, $conf);
$conf=Event::$conf;

$conf['debug']=Access::test();