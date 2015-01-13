<?php
namespace SmtpValidatorEmail\Configs;

use \Symfony\Component\Yaml\Yaml;

/**
 * Class ConfigReader reads configs
 * @package SmtpValidatorEmail\Configs
 */
class ConfigReader {
    public static function readConfigs($file){
        $yaml = new Yaml();
        return $yaml->parse(file_get_contents($file));
    }
}