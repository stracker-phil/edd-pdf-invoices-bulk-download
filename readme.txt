=== EDD - PDF Invoice Bulk Downloader ===
Contributors: strackerphil-1
Tags: bulk download, digital downloads, easy digital downloads, edd, pdf invoices, edd invoice, edd invoices
Requires at least: 4.0
Tested up to: 5.7.2
Requires PHP: 5.6
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a bulk action to download multiple PDF Invoices in Easy Digital Downloads.

*Required "Easy Digital Downloads" and "Easy Digital Downloads - PDF Invoices"*

== Description ==

Adds a bulk download function to the (https://easydigitaldownloads.com/downloads/pdf-invoices/)[Easy Digital Downloads - PDF Invoice] plugin.

After activating the plugin, you can instantly see the new bulk action "Download PDF Invoices" in the "Payment History" page of EDD.

**Requirements**

To use this plugin, your WordPress website needs to meet the following conditions:

1. The plugin [**Easy Digital Downloads**](https://de.wordpress.org/plugins/easy-digital-downloads/) is required.
1. The plugin [**Easy Digital Downloads - PDF Invoices**](https://easydigitaldownloads.com/downloads/pdf-invoices/) is required.
1. Your webserver needs the **zip extension**.

**Third-Party Extension**

Please note that this is an unofficial extension for Easy Digital Downloads.

Therefore it's not supported by Easy Digital Downloads. The author of this plugin is not associated with Sandhills Development, LLC (the creators of EDD).

-----

Want to report a bug or submit a pull request? This plugin is maintained on GitHub:

https://github.com/stracker-phil/edd-pdf-invoices-bulk-download/

== Screenshots ==

1. The new bulk action, added by this plugin
2. Demo of the bulk action

== Frequently Asked Questions ==

= I cannot see the "Download PDF Invoices" action =

Make sure that you have activated all required plugins ("[Easy Digital Downloads](https://de.wordpress.org/plugins/easy-digital-downloads/)", and "[Easy Digital Downloads - PDF Invoices](https://easydigitaldownloads.com/downloads/pdf-invoices/)") as well as this plugin.

If all three plugins are active on your website and you still cannot see the "Download PDF Invoices" action, then your webserver does not support the zip-extension. This extension is required to generate a zip archive containing all invoices. Therefore, the new bulk action is only added, when the zip-extension is available.

= Does my website support the zip-extension? =

There are good chances, that your website supports the zip extension - almost all professional web hosts include it today.

You can check, if your website is ready by following those simple steps:

1. Open up wp-admin | Tools | Site Health
2. Check the "Recommended Improvement" section for the message "*The optional module, %s, is not installed, or has been disabled*"
3. If that message is not displayed, then you are all set!

= Where can I get support? =

Please use the [WordPress.org support forums](https://wordpress.org/support/plugin/edd-pdf-invoices-bulk-download/), or the [forum in the GitHub repository](https://github.com/stracker-phil/edd-pdf-invoices-bulk-download/issues).

Do not contact Easy Digital Downloads staff with questions about this plugin, as it's not maintained by them.

== Changelog ==

= 1.0.0 =
* Initial Release
