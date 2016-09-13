<?php
/**
 * Prodder plugin for Craft CMS
 * This plugin sends you a friendly email reminder about stagnating channels.
 * --snip--
 * Craft plugins are very much like little applications in and of themselves. We’ve made it as simple as we can,
 * but the training wheels are off. A little prior knowledge is going to be required to write a plugin.
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL, as well as some semi-
 * advanced concepts like object-oriented programming and PHP namespaces.
 * https://craftcms.com/docs/plugins/introduction
 * --snip--
 *
 * @author    Billy Fagan
 * @copyright Copyright (c) 2016 Billy Fagan
 * @link      https://billyfagan.co.uk
 * @package   Prodder
 * @since     1.0.0
 */

namespace Craft;

use Carbon\Carbon;

class ProdderPlugin extends BasePlugin
{
    /**
     * Called after the plugin class is instantiated; do any one-time initialization here such as hooks and events:
     * craft()->on('entries.saveEntry', function(Event $event) {
     *    // ...
     * });
     * or loading any third party Composer packages via:
     * require_once __DIR__ . '/vendor/autoload.php';
     *
     * @return mixed
     */
    public function init()
    {
        require_once __DIR__ . '/vendor/autoload.php';
        craft()->prodder->prepChannels();
        craft()->prodder->sendProdEmails();
    }

    /**
     * All the settings stuff, like email messages, come from this translation
     *
     * @return string
     */
    public function getSourceLanguage()
    {
        return 'en';
    }

    /**
     * Returns the user-facing name.
     *
     * @return mixed
     */
    public function getName()
    {
        return Craft::t('Prodder');
    }

    /**
     * Plugins can have descriptions of themselves displayed on the Plugins page by adding a getDescription() method
     * on the primary plugin class:
     *
     * @return mixed
     */
    public function getDescription()
    {
        return Craft::t('This plugin gives you a friendly alert about stagnating channels.');
    }

    /**
     * Plugins can have links to their documentation on the Plugins page by adding a getDocumentationUrl() method on
     * the primary plugin class:
     *
     * @return string
     */
    public function getDocumentationUrl()
    {
        return 'https://github.com/billythekid/prodder/blob/master/README.md';
    }

    /**
     * Plugins can now take part in Craft’s update notifications, and display release notes on the Updates page, by
     * providing a JSON feed that describes new releases, and adding a getReleaseFeedUrl() method on the primary
     * plugin class.
     *
     * @return string
     */
    public function getReleaseFeedUrl()
    {
        return 'https://raw.githubusercontent.com/billythekid/prodder/master/releases.json';
    }

    /**
     * Returns the version number.
     *
     * @return string
     */
    public function getVersion()
    {
        return '1.0.3';
    }

    /**
     * As of Craft 2.5, Craft no longer takes the whole site down every time a plugin’s version number changes, in
     * case there are any new migrations that need to be run. Instead plugins must explicitly tell Craft that they
     * have new migrations by returning a new (higher) schema version number with a getSchemaVersion() method on
     * their primary plugin class:
     *
     * @return string
     */
    public function getSchemaVersion()
    {
        return '1.0.1';
    }

    /**
     * Returns the developer’s name.
     *
     * @return string
     */
    public function getDeveloper()
    {
        return 'Billy Fagan';
    }

    /**
     * Returns the developer’s website URL.
     *
     * @return string
     */
    public function getDeveloperUrl()
    {
        return 'https://billyfagan.co.uk';
    }

    /**
     * Returns whether the plugin should get its own tab in the CP header.
     *
     * @return bool
     */
    public function hasCpSection()
    {
        return false;
    }

    /**
     * Called right before your plugin’s row gets stored in the plugins database table, and tables have been created
     * for it based on its records.
     */
    public function onBeforeInstall()
    {
    }

    /**
     * Called right after your plugin’s row has been stored in the plugins database table, and tables have been
     * created for it based on its records.
     */
    public function onAfterInstall()
    {
    }

    /**
     * Called right before your plugin’s record-based tables have been deleted, and its row in the plugins table
     * has been deleted.
     */
    public function onBeforeUninstall()
    {
    }

    /**
     * Called right after your plugin’s record-based tables have been deleted, and its row in the plugins table
     * has been deleted.
     */
    public function onAfterUninstall()
    {
    }

    /**
     * Defines the attributes that model your plugin’s available settings.
     *
     * @return array
     */
    protected function defineSettings()
    {
        return array(
            'sendToAuthors' => AttributeType::Mixed, // array of user IDs to prod
            'active'        => AttributeType::Mixed, // array of booleans
            'prodDelay'     => AttributeType::Mixed, // array of integers (days since posting)
        );
    }

    /**
     * Returns the HTML that displays your plugin’s settings.
     *
     * @return mixed
     */
    public function getSettingsHtml()
    {
        $editableSections = array();
        $allSections      = craft()->sections->getAllSections();

        foreach ($allSections as $section)
        {
            if ($section->type == 'channel')
            {
                $editableSections[$section->handle] = array('section' => $section);
            }
        }

        foreach ($editableSections as $handle => $value)
        {
            // If we're running on Client Edition, add both accounts.
            if (craft()->getEdition() == Craft::Client)
            {
                $defaultAuthorOptionCriteria = craft()->elements->getCriteria(ElementType::User);
                $sendToOptions               = $defaultAuthorOptionCriteria->find();
            } else if (craft()->getEdition() == Craft::Pro)
            {
                $defaultAuthorOptionCriteria      = craft()->elements->getCriteria(ElementType::User);
                $defaultAuthorOptionCriteria->can = 'createEntries:' . $value['section']->id;
                $sendToOptions                    = $defaultAuthorOptionCriteria->find();
            } else
            {
                // 2.x on Personal Edition.
                $sendToOptions = array(craft()->userSession->getUser());
            }
            foreach ($sendToOptions as $key => $authorOption)
            {
                $authorLabel    = $authorOption->username;
                $authorFullName = $authorOption->getFullName();
                if ($authorFullName)
                {
                    $authorLabel .= ' (' . $authorFullName . ')';
                }
                $sendToOptions[$key] = array('label' => $authorLabel, 'value' => $authorOption->id);
            }
            array_unshift($sendToOptions, array('label' => 'Nobody', 'value' => 'none'));
            $editableSections[$handle] = array_merge($editableSections[$handle], array('sendToOptions' => $sendToOptions));
        }

        return craft()->templates->render('prodder/Prodder_Settings', array(
            'settings'         => $this->getSettings(),
            'editableSections' => $editableSections,
        ));

    }

    /**
     * If you need to do any processing on your settings’ post data before they’re saved to the database, you can
     * do it with the prepSettings() method:
     *
     * @param mixed $settings The Widget's settings
     * @return mixed
     */
    public function prepSettings($settings)
    {
        // Modify $settings here...

        return $settings;
    }

    /**
     * Displays a prompt to the user to get some content into the channel they're supposed to be monitoring
     *
     * @param $path
     * @param $fetch
     * @return array
     */
    public function getCpAlerts($path, $fetch)
    {
        $currentUser = craft()->userSession->user;
        $settings    = $this->getSettings();
        $channels    = craft()->prodder->getProdChannelsForUser($currentUser);

        //check the last entries in each channel
        foreach ($channels as $channel)
        {
            $criteria          = craft()->elements->getCriteria(ElementType::Entry);
            $criteria->section = $channel;
            $criteria->order   = 'postDate desc';
            $criteria->limit   = 1;
            $entry             = $criteria->first();
            if ($entry)
            {
                $diff = Carbon::now()->diffInDays(Carbon::createFromTimestamp($entry->postDate->getTimestamp()));

                // let's just squawk about one channel at a time for performance and to stop nagging the guy
                // who wants to write a ton of content at a time? not him that's who.
                if ($diff >= $settings->prodDelay[$channel])
                {
                    $message = "<p>This is a gentle prod to get some new content in the <strong>{$channel}</strong> channel.</p>";
                    $message .= "<p>You're supposed to update {$channel} within {$settings->prodDelay[$channel]} days of the last entry, and it's been {$diff} days.</p>";

                    return array($message);
                }
            }
        }

        return null;
    }


    /**
     * Tells Craft we have email messages that the user can modify.
     *
     * @return array
     */
    public function registerEmailMessages()
    {
        return array(
            'Prod',
        );
    }
}