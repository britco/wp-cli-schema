<?php
namespace WP_CLI_Schema;

use WP_CLI_Command, WP_CLI, ReflectionMethod, ReflectionFunction;

/*
Plugin Name: WP-CLI Schema
Plugin URI:
Description: Adds 'schema' commands to wp-cli
Author: Paul Dufour
Version:
Author URI: http://www.brit.co
*/

if (!defined('WP_CLI') || !WP_CLI) {
  return;
}

class Schema extends WP_CLI_Command {
  /**
   * Extract relevant data from a reflection method / function
   * @return array Data about the refleciton
   */
  protected function get_reflection_info($reflection) {
    $filename = $reflection->getFileName();
    if(strpos($filename, ABSPATH) === 0) {
        $filename = substr($filename, strlen(ABSPATH));
    }
    $filename .= ':' .  $reflection->getStartLine();
        
    if($reflection instanceof \ReflectionMethod) {
      $name = $reflection->getDeclaringClass()->getName() . '\\' . $reflection->getName();
    } else {
      $name = $reflection->getName();
    }
    
    return array(
      'filename' => $filename,
      'name' => $name
    );
  }
  
  /**
   * Upgrade function. Runs all functions attatched to the "schema_upgrade" hook.
   * @param  array $args       [description]
   * @param  array  $assoc_args [description]
   */
  public function upgrade($args, $assoc_args=array()) {
    global $wp_filter;
    
    if(!array_key_exists('schema_upgrade', $wp_filter)) {
        WP_CLI::error("No schema upgrade hooks found");
        return;
    }
    WP_CLI::log(sprintf("Executing schema upgrade"));
    WP_CLI::log(sprintf("│ "));

    // Wrap all the hooks in a function that logs execution time
    foreach($wp_filter['schema_upgrade'] as $priority => &$sub_hooks) {
      foreach($sub_hooks as $key => &$hook) {
        $function = $hook['function'];
        
        if(is_array($function)) {
          $reflection = new ReflectionMethod($function[0], $function[1]);
        } else {
          $reflection = new ReflectionFunction($function);
        }
        
        $info = $this->get_reflection_info($reflection);
        
        $_this = $this;
        $hook['function'] = function() use ($_this, $info, $function, $sub_hooks) {
          $before = microtime(true);
          $pipe = $hook === $sub_hooks[0] ? "├─┬" : "├──";
          WP_CLI::log(sprintf("{$pipe} Executing %s", $info['filename']));
          WP_CLI::log(sprintf("│ ├── Function name: %s", $info['name']));
          
          $result = call_user_func_array($function, func_get_args());
          
          if($result === false) {
            WP_CLI::log(sprintf("│ └── Skipping, result returned false"));
          } else {
            $after = microtime(true);
            $diff = number_format($after - $before, 5);
            $pipe = $hook === end($sub_hooks) ? "└──" : "│ ├──";
            WP_CLI::log(sprintf("│ └── Completed in %Fs", $diff));
          }
          
          WP_CLI::log(sprintf("│ "));
        };
      }
    }
    
    // Run all associated now that debug information has been added
    do_action('schema_upgrade');
    
    WP_CLI::log(sprintf("└── Schema upgrade complete"));
  }
}

WP_CLI::add_command('schema', __NAMESPACE__ . '\Schema');
