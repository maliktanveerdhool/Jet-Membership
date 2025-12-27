# Jet Membership

A lightweight, professional membership management plugin for WordPress, designed with a "Jet-native" philosophy for seamless integration with **JetFormBuilder** and **JetEngine**.

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/wordress-6.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/php-7.4%2B-orange.svg)

## 🚀 Overview

Jet Membership provides a streamlined way to manage user subscriptions, restrict content, and automate membership assignments through custom forms. It avoids the bloat of traditional membership plugins, focusing instead on performance and dynamic data.

## ✨ Key Features

*   **Custom Membership Levels**: Create unlimited tiers with custom pricing, icons, and badge colors.
*   **Flexible Durations**: Support for Fixed Expiry dates, Days-based duration, or Lifetime access.
*   **Content Restriction**: Lock entire posts/pages via meta boxes or specific sections using shortcodes.
*   **JetFormBuilder Integration**: Automatically assign membership levels to users upon registration through JFB forms.
*   **Member Management**: Dedicated admin dashboard to view, activate, or deactivate members.
*   **Live Countdowns**: Dynamic JavaScript timers showing users exactly how much time they have left.
*   **Professional UI**: Premium-styled restriction messages and pricing tables.
*   **Developer Friendly**: Clean OOP architecture and standard WordPress metadata usage.

## 🛠 Installation

1.  Download or clone this repository into your `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Navigate to **Jet Membership** in your admin sidebar to start creating levels.

## 📖 How to Use

### 1. Create a Membership Level
Go to **Jet Membership > Add New Level**. Define your price, duration (in days), and pick a badge color/icon. Note the **Level ID** shown in the sidebar.

### 2. Restrict Content
*   **Global**: On any Post or Page, use the "Jet Content Restriction" sidebar box to select which level can access the content.
*   **Partial**: Wrap any content in the `[mtd_is_member]` shortcode.

### 3. JetFormBuilder Integration
1.  Create a registration form in JetFormBuilder.
2.  In **Post Submit Actions**, add/edit the **Register User** action.
3.  You will see a new **Membership Level** dropdown—select the level you want to assign to this specific form.
4.  Optionally, map `_mtd_level_id` in the user meta section if you want dynamic assignments.

## 🔢 Shortcodes Reference

| Shortcode | Attributes | Description |
| :--- | :--- | :--- |
| `[mtd_is_member]` | `level`, `message` | Shows content only to members of a specific level. |
| `[mtd_not_member]` | `level` | Shows content only to visitors who are NOT members. |
| `[mtd_member_info]` | `field`, `format` | Displays user info like `level_name`, `expiry_date`, `status_badge`, etc. |
| `[mtd_countdown]` | `format`, `expired_text` | Shows a live timer until membership expires. |
| `[mtd_pricing_table]` | `levels`, `columns` | Renders a beautiful pricing comparison table. |
| `[mtd_login_link]` | `text`, `redirect` | Displays a login link if the user is a guest. |
| `[mtd_account_link]` | `login_text`, `account_text`| Smart link: shows Login for guests, Account for members. |

## 💻 Developer Info

### Custom Post Types
*   `mtd_level`: Stores membership level definitions.

### User Metadata
*   `_mtd_level_id`: ID of the assigned level.
*   `_mtd_expiry_date`: Unix timestamp of expiry.
*   `_mtd_account_status`: `active` \| `deactivated`.

### Hooks
*   `mtd_daily_deactivation_check`: Daily cron action for processing expirations.
*   `jet-form-builder/actions/after-do-action/register_user`: Used for JFB integration.

---

## 👤 Credits
**Author:** Malik Tanveer  
**Website:** [MTD Technologies](https://mtdtechnologies.com/)

---
*Disclaimer: This plugin is intended to supplement the JetEngine/JetFormBuilder ecosystem and is not an official Crocoblock product.*
