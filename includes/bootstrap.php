<?php
/**
 * 公共引导文件
 * 各页面/接口可通过 require 引入
 */

$configPath = dirname(__DIR__) . '/config/config.php';
$config = file_exists($configPath)
    ? require $configPath
    : require dirname(__DIR__) . '/config/config.example.php';
