# Prodder plugin for Craft CMS

This plugin gives you a friendly alert about stagnating channels.

![Screenshot](resources/screenshots/ss.png)

## Installation

To install Prodder, follow these steps:

1. Download & unzip the file and place the `prodder` directory into your `craft/plugins` directory
2.  -OR- do a `git clone https://github.com/billythekid/prodder.git` directly into your `craft/plugins` folder.  You can then update it with `git pull`
3. Install plugin in the Craft Control Panel under Settings > Plugins
4. The plugin folder should be named `prodder` for Craft to see it.  GitHub recently started appending `-master` (the branch name) to the name of the folder for zip file downloads.

Prodder works on Craft 2.4.x and Craft 2.5.x.

## Prodder Overview

Prodder is designed to give you a slightly annoying but not too intrusive warning that your content isn't as fresh as you'd like it.
Content is king right? Nobody wants a smelly old king. We like out kings fragrant and fresh.

## Configuring Prodder

Click on Prodder's settings button from the plugin installation page or from the settings page (2.5+). In here you will see any channels listed (Prodder only checks channels, no singles or structures) where you can set up your "prod" criteria.
* Send prod to: Select who should see the alert when the channel is overdue an entry.
* Prod if last entry older than…?: Choose the number of days before this channel is considered stale.
* Activate prods for this channel?: Whether to include this channel when checking if you need a prod.

## Using Prodder

Just use the CMS. If prodder sees one of your channels is stale and that you are the person who needs to know that, it'll tell you. It won't go away until you've updated the channel.

## Prodder Roadmap

* Daily customisable email notifications

## Prodder Changelog

### 1.0.0 -- 2016.06.29

* Initial release

Brought to you by [Billy Fagan](https://billyfagan.co.uk)