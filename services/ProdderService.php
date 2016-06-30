<?php
/**
 * Prodder plugin for Craft CMS
 * Prodder Service
 * --snip--
 * All of your plugin’s business logic should go in services, including saving data, retrieving data, etc. They
 * provide APIs that your controllers, template variables, and other plugins can interact with.
 * https://craftcms.com/docs/plugins/services
 * --snip--
 *
 * @author    Billy Fagan
 * @copyright Copyright (c) 2016 Billy Fagan
 * @link      https://billyfagan.co.uk
 * @package   Prodder
 * @since     1.0.0
 */

namespace Craft;

class ProdderService extends BaseApplicationComponent
{
    /**
     * This function can literally be anything you want, and you can have as many service functions as you want
     * From any other plugin file, call it like this:
     *     craft()->prodder->exampleService()
     */
    public function exampleService()
    {
    }

}