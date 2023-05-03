# How to use

Install this as a WordPress plugin.

You must have a proxy that you control (a squid proxy server is pretty good). You will need to *extract* certain cookies from Webtoon.xyz every week since they configured cookies to expire every week on CloudFlare. You can either do this through automation or manually.

![What you need](https://i.imgur.com/nwJXPkP.png)

This needs to be extracted while you are connected to your proxy! CloudFlare ties these to the IP address. Make sure the proxy IP is not completely dirty (NO ColoCrossing IPs, RackNerd, Virmach, etc.), pay a little more for an actually decent server from SpeedyPage, Clouvider, etc.

In `libs/simplehtmldom_1_5/simple_html_dom.php`,
on line 73 replace the `cf_clearance` value (NOT KEY!) with the your own cf_clearance value. 
On line 86, replace the `CURLOPT_USERAGENT` value with your own user agent value.
On line 102, replace the `CURLOPT_PROXY` with your own proxy address.

Once again, Squid proxy is easy to set up and maintain. I would not recommend installing it on the server with your WordPress instance though. Lastly, to repeat, YOU WILL NEED TO CHANGE THESE VALUES (excluding your proxy) EVERY WEEK (168 HOURS EXPIRY)!

Also, on some installations, a bug occurs where the crawler does not do auto crawling. You will need to go to your database and change the meta value for the plugin settings and change the `active` and `update` keys to 1 (or true, might be a boolean value I can't remember; you may find it helpful to unserialize the array to edit it and serialize it again to save it). Afterwards, if the cronjob was not automatically added, you'll need to add `wt_do_crawl`, `wt_check_updates`, and `wt_fetch_queue` yourself using [WP Control](https://wordpress.org/plugins/wp-crontrol/). 3 minutes is a pretty good interval for `wt_check_updates`/`wt_fetch_queue` if your WordPress instance is not hosted on a fossil. 30 minutes is the optimal interval for `wt_do_crawl`.