=== myEasyCompta ===
Contributors: mizou1255
Tags: accounting, quotes, invoices, expenses, freelancers
Requires at least: 6.2
Tested up to: 6.6.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

myEasyCompta is a comprehensive and modern accounting solution for WordPress, specifically designed for freelancers and small businesses.

== Description ==

myEasyCompta is a comprehensive and modern accounting solution for WordPress, specifically designed for freelancers and small businesses. Built with Vue.js and TailwindCSS, myEasyCompta offers a sleek, responsive, and user-friendly interface, ensuring that managing your finances is both efficient and enjoyable.

📊 **Dashboard**: Get clear statistics on quotes, invoices, payments, and expenses.  
👥 **Client Management**: Easily add, edit, and delete client information.  
💼 **Professional Quotes**: Create customizable professional quotes with your company logo and details.  
📄 **Invoice Management**: Generate, track the status (sent, paid, overdue), and create PDF invoices.  
💰 **Payment Tracking**: Record received payments, manage pending invoices, and generate payment reports.  
💸 **Expense Tracking**: Categorize business expenses and generate detailed reports to control costs and optimize your budget.  
⚙️ **Settings Management**: Customize myEasyCompta to fit your needs: add a logo, configure client emails, and personalize quote and invoice templates.  


🚀 **myEasyCompta PRO - Coming Soon!**

Get ready for the launch of myEasyCompta PRO, which will include powerful additional features:

🗓️ **Planning**: Manage and schedule your tasks, appointments, and deadlines.  
🎫 **Ticket Support**: Provide support to your clients with an integrated ticketing system.  
🧾 **Automatic Invoice Creation**: Automatically generate invoices for WooCommerce orders, saving you time and reducing manual work.  
📊 **Data Export**: Export your data (clients, invoices, quotes, etc.) for easy backup and reporting.  
📈 **Clients Frontend Dashboard**: Provide your clients with a dedicated frontend dashboard where they can view their quotes, invoices, and other relevant information.  

Stay tuned for more updates and get ready to elevate your accounting experience with myEasyCompta PRO!

== Key Features ==

* **Quotes Management**: Easily create and manage quotes for your clients. Track the status of each quote and convert accepted quotes into invoices with a single click.
* **Invoices Management**: Generate professional invoices quickly and effortlessly. Customize invoice templates to match your branding, and keep track of payments and overdue invoices.
* **Expenses Tracking**: Record and categorize your business expenses to keep a clear overview of your financial outgoings. Attach receipts and other documents for comprehensive expense records.
* **Reports**: Generate detailed financial reports to gain insights into your business performance. Reports can be filtered by date, client, and category to provide the information you need.
* **Secure and Reliable**: All AJAX requests are protected with nonce verification to ensure security. Data integrity and confidentiality are our top priorities.
* **User-Friendly Interface**: Thanks to Vue.js and TailwindCSS, myEasyCompta provides a modern and intuitive user experience. The interface is responsive, ensuring that you can manage your finances on any device.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/my-easy-compta` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to the myEasyCompta settings page to configure the plugin and start managing your quotes, invoices, and expenses.

== Frequently Asked Questions ==

= What are the minimum requirements for myEasyCompta? =

- WordPress 6.2 or higher.
- PHP 7.4 or higher.

= How do I manage my quotes and invoices? =

After activating the plugin, go to the myEasyCompta section in the WordPress admin panel. You can create and manage quotes, invoices, and other accounting elements from there.

= Is myEasyCompta compatible with my theme? =

myEasyCompta is designed to work with most WordPress themes. If you encounter any issues, please contact our support team.

= How can I contribute to myEasyCompta? =

We welcome contributions from the community! You can contribute by submitting a pull request on our [GitHub repository](https://github.com/mizou1255/my-easy-compta).

== Screenshots ==

1. **Dashboard** - Overview of your financial activities.
2. **Clients Management** - Create and manage your clients list.
2. **Quotes Management** - Create and manage quotes for your clients.
3. **Invoices Management** - Generate and track invoices.
4. **Expenses Tracking** - Record and categorize your business expenses.
5. **Settings** - Settings page.

== Changelog ==

= 1.0 =
* Initial release of myEasyCompta.

== Upgrade Notice ==

= 1.0 =
* Initial release.

== License ==

myEasyCompta is licensed under the GPLv2 or later. For more information, see [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html).

== External Services ==

This plugin uses external services to enhance its functionality and provide a better user experience. Below are the details of the external services used:

### License Verification Service

- **Service URL:** [https://myeasycompta.com/wp-json/mlz-license/v1/check](https://myeasycompta.com/wp-json/mlz-license/v1/check)
- **Description:** This service is used to verify the validity of the license key provided by the user. The request includes the license key and the domain of the site.
- **Terms of Use:** [myEasyCompta Terms of Service](https://myeasycompta.com/cgv/)
- **Privacy Policy:** [myEasyCompta Privacy Policy](https://myeasycompta.com/mentions-legales/)

### Update Check Service

- **Service URL:** [https://myeasycompta.com/wp-json/mlz-license/v1/check-update](https://myeasycompta.com/wp-json/mlz-license/v1/check-update)
- **Description:** This service is used to check for updates for the plugin. The request includes the plugin slug and the current version.
- **Terms of Use:** [myEasyCompta Terms of Service](https://myeasycompta.com/cgv/)
- **Privacy Policy:** [myEasyCompta Privacy Policy](https://myeasycompta.com/mentions-legales/)

### Update Download Service

- **Service URL:** [https://myeasycompta.com/wp-json/mlz-license/v1/download-update](https://myeasycompta.com/wp-json/mlz-license/v1/download-update)
- **Description:** This service is used to download updates for the plugin. The request includes the plugin slug and other relevant data.
- **Terms of Use:** [myEasyCompta Terms of Service](https://myeasycompta.com/cgv/)
- **Privacy Policy:** [myEasyCompta Privacy Policy](https://myeasycompta.com/mentions-legales/)

By using this plugin, you acknowledge and agree to the terms and conditions of these external services.

== Source Code ==

The non-compressed source code for JavaScript and CSS files is available in the following directories:

- JavaScript Source: `/src/api/`, `/src/apps/`, `/src/components/`, `/src/js/`
- CSS Source: `/src/css/`

Please refer to these directories for the human-readable version of the code.

The non-compressed source code is also available on our public repository:

- GitHub Repository: [https://github.com/mizou1255/myeasycompta](https://github.com/mizou1255/myeasycompta)

== Build Tools ==

This plugin uses npm and Webpack to manage and build the source code. To install and use these tools:

1. Navigate to the plugin's directory.
2. Run `npm install` to install dependencies.
3. Use `npm run build` to generate the compressed files in the `assets/dist` directories.

For more details, refer to the `package.json` file in the root directory.

== Contributing ==

If you would like to contribute to the development of myEasyCompta, please visit our [GitHub repository](https://github.com/mizou1255) and submit a pull request.

== Credits ==

* **Author:** Moez BETTOUMI
* **Website:** [https://moezbettoumi.fr](https://moezbettoumi.fr)
* **Donate:** [https://buymeacoffee.com/mizou1253](https://buymeacoffee.com/mizou1253)
