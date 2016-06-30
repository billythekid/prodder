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

use Carbon\Carbon;

class ProdderService extends BaseApplicationComponent
{

    /**
     * Checks if any prod emails are to be sent and sends them.
     * @throws Exception
     */
    public function sendProdEmails()
    {
        $settings = craft()->plugins->getPlugin('prodder')->getSettings();
        $allUsers = craft()->elements->getCriteria(ElementType::User)->find();
        foreach ($allUsers as $user)
        {
            $channels = $this->getProdChannelsForUser($user);
            if (!empty($channels))
            {
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

                        if ($diff >= $settings->prodDelay[$channel])
                        {
                            //only send an email once a day
                            if ($this->isChannelDue($channel))
                            {
                                $this->sendEmail($user, $channel, $settings, $diff);
                            }
                        }
                    }
                }
            }
        }

    }

    /**
     * Returns an array of channel handles (slugs) that the given user should be
     * checking for stale content
     *
     * @param UserModel $user
     * @return array
     */
    public function getProdChannelsForUser(UserModel $user)
    {
        $settings = craft()->plugins->getPlugin('prodder')->getSettings();

        // get all channels we want to look through
        $active = array_keys(
            array_filter($settings->active, function ($value)
            {
                return !empty($value);
            })
        );

        // get all the channels that this user is to be prodded about
        $users = array_keys(
            array_filter($settings->sendToAuthors, function ($value) use ($user)
            {
                return $value == $user->id;
            })
        );

        // get an array of channels(handles) we should check now, these are active and for this user.
        return array_intersect($active, $users);
    }

    /**
     * @param $channel
     * @return bool
     */
    private function isChannelDue($channel)
    {
        $channelsDue = $this->getChannelsDue();

        return !empty(array_filter($channelsDue, function ($record) use ($channel)
        {
            return $record->channel == $channel;
        }));
    }


    /**
     * Returns all channels that haven't had an email reminder sent in the past day.
     *
     * @return array
     */
    private function getChannelsDue()
    {
        $yesterday = Carbon::now()->subDay()->toDateTimeString();

        return ProdderRecord::model()->findAll("lastProd <= '$yesterday'");
    }

    private function updateChannelLastProd($channel)
    {
        $record           = ProdderRecord::model()->find("channel = '{$channel}'");
        $record->lastProd = Carbon::now();
        $record->save();
    }

    /**
     * Sends the user the email and updates the lastProd time
     *
     * @param $user
     * @param $channel
     * @param $settings
     * @param $diff
     */
    private function sendEmail($user, $channel, $settings, $diff)
    {
        $message = array(
            'user'       => $user,
            'channel'    => $channel, //TODO add this
            'days'       => $settings->prodDelay[$channel],
            'difference' => $diff,
        );
        craft()->email->sendEmailByKey($user, 'Prod', $message);
        $this->updateChannelLastProd($channel);
    }


    /**
     * This makes sure all current channels exist in the prodder database.
     * If a channel is not in the prodder database it adds it and sets its lastProd
     * time to unix epoch so other comparisons can be done later.
     */
    public function prepChannels()
    {
        $allSections = craft()->sections->getAllSections();
        foreach ($allSections as $section)
        {
            if ($section->type == 'channel')
            {
                if (!ProdderRecord::model()->exists("channel = '{$section->handle}'"))
                {
                    $record           = new ProdderRecord;
                    $record->channel  = $section->handle;
                    $record->lastProd = 0;
                    $record->save();
                }
            }
        }
    }
}