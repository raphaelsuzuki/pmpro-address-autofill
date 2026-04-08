# Address Autofill for Paid Memberships Pro

[![Codacy Badge](https://app.codacy.com/project/badge/Grade/14016903be604f87a4c016ebb8ae7121)](https://app.codacy.com/gh/raphaelsuzuki/pmpro-address-autofill/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_grade)
[![Last Updated](https://img.shields.io/github/last-commit/raphaelsuzuki/pmpro-address-autofill?label=Last%20Updated)](https://github.com/raphaelsuzuki/pmpro-address-autofill/commits/main)

[![License: GPL v2](https://img.shields.io/badge/License-GPLv2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![PHP 7.2+](https://img.shields.io/badge/PHP-7.2%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![WordPress 5.0+](https://img.shields.io/badge/WordPress-5.0%2B-blue?logo=wordpress)](https://wordpress.org/)

Enhance the membership checkout experience by allowing returning members to automatically populate their billing address fields from their last successful order—reducing friction and increasing conversion rates.

Perfect for membership sites with high renewal rates or multiple membership levels where users frequently return to the checkout page.

**Notice:** This plugin is designed for Paid Memberships Pro and is an unofficial, third‑party add‑on not directly affiliated with or endorsed by Paid Memberships Pro.




---

## Key Features

- **Intelligent Autofill**: Detects returning members and offers a one-click population of billing fields.
- **User-Controlled Preferences**: Users can toggle "Always autofill" to automate the process for future checkouts.
- **First-Time Subscriber Support**: New users are given the option to save their address for a smoother experience next time.
- **Triple-Layer Data Fallback**: Prioritizes standard PMPro profile meta, then falls back to the user's last successful order history.
- **Security-First Architecture**: Implements strict ownership checks, whitelisting, and deep sanitization to protect customer data.
- **Native UI Integration**: Injects seamlessly into the PMPro checkout card actions for a professionally integrated look.

---

## Quick Start

### Installation

1. Upload the `pmpro-address-autofill` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. The autofill options will automatically appear on the membership checkout page for eligible users.

### Requirements

- **Paid Memberships Pro** (v3.0 or higher recommended)
- **WordPress** (v5.6 or higher recommended)

---

## Security & Privacy

This plugin is designed with security as a top priority:
- **Strict Access Control**: A user can only retrieve their own address data.
- **Deep Sanitization**: All data is cleaned using WordPress standards before being output to the browser.
- **No External Dependencies**: Operates entirely within your WordPress environment for maximum privacy.

## Support

- **Issues:** [GitHub Issues](https://github.com/raphaelsuzuki/pmpro-address-autofill/issues)
- **Updates:** Automatic via Git Updater

## Contributing

Contributions are welcome! Please submit pull requests or open issues on GitHub.

- **Repository:** https://github.com/raphaelsuzuki/pmpro-address-autofill
- **Pull Requests:** Follow WordPress Coding Standards

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## Disclaimer

This repository and its documentation were created with the assistance of AI. While efforts have been made to ensure accuracy and completeness, no guarantee is provided. Use at your own risk. Always test in a safe environment before deploying to production.
