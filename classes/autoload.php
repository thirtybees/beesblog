<?php
/**
 * 2017 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 *  @author    thirty bees <modules@thirtybees.com>
 *  @copyright 2017 thirty bees
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

 class AutoLoad
 {
     protected static $instance = null;

     public static function getInstance() {

         if (self::$instance === null)
             self::$instance = new AutoLoad();
         return self::$instance;
     }

     public function load($classname) {

         $currentDir = dirname(__FILE__);
         $path = array(
             $currentDir.'/..', /* root */
             $currentDir, /* classes */
             $currentDir.'/../controllers/admin/',
             $currentDir.'/../controllers/front/'
         );

         foreach ($path as $dir) {

             if (file_exists($dir.'/'.$classname.'.php'))
                 require_once($dir.'/'.$classname.'.php');
             elseif (file_exists($dir.'/'.strtolower($classname).'.php'))
                 require_once($dir.'/'.strtolower($classname).'.php');
         }

     }
 }
