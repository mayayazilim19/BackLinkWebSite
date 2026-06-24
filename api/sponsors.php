<?php
header('Content-Type: application/json; charset=utf-8');
$file = dirname(__DIR__) . '/data/sponsors.json';
echo file_exists($file) ? file_get_contents($file) : '[]';
